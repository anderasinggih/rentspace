const {
    default: makeWASocket,
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    makeInMemoryStore,
    downloadMediaMessage
} = require('@whiskeysockets/baileys');
const express = require('express');
const cors = require('cors');
const qrcodeTerminal = require('qrcode-terminal');
const pino = require('pino');
const axios = require('axios');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

const {
    BUBBLES,
    isAck,
    isGreetingOnly,
    mergeBatchText,
    randomBetween,
    sleep,
    splitForChat,
    waitForQuietPeriod,
} = require('./lib/chat-humanizer');

const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || 'rentspace_secret_wa_token_2026';
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://localhost:8000/api/v1/wa/webhook';

let sock = null;
let qrCodeRaw = null;
let connectionStatus = 'connecting'; // 'connecting' | 'open' | 'close'
let botUser = null;

// Laravel + AI butuh 10-25 detik untuk menjawab. Kalau pesan customer diproses
// paralel, jawabannya arrive telat dan terlihat seperti bot "bales pesan
// sebelumnya". Jadi satu chat = satu giliran AI pada satu waktu.
const chatQueues = new Map(); // jid -> { running, pending: [] }

// Kata pemicu "minta bantuan admin" (jalur forward manual customer).
const ADMIN_REQ_WORDS = ['4', 'admin', 'cs', 'bantuan admin', 'hubungi admin', 'kontak admin'];
const ADMIN_REQ_SET = new Set(ADMIN_REQ_WORDS);

// Pesan customer terakhir per chat (bukan pemicu admin). Dipakai supaya saat
// customer ngetik "ADMIN", yang diteruskan ke admin adalah pertanyaan aslinya,
// bukan cuma kata "ADMIN" yang tidak menjelaskan apa-apa.
const lastCustomerMessage = new Map(); // jid -> text

// Human Takeover: jika admin mengetik/membalas manual dari HP di nomor customer,
// bot diam selama jeda dinamis (5 menit sejak chat terakhir admin).
// Jeda reset tiap kali admin kirim chat baru.
const humanTakeoverMap = new Map(); // jid -> timestampMs
const HUMAN_TAKEOVER_TIMEOUT_MS = 5 * 60 * 1000; // 5 menit

// Cache Initial Promo Data
let cachedInitialPromo = null;
const customerGreetedSet = new Set(); // nomor customer yang sudah menerima sapaan/promo awal sesi ini

async function fetchInitialPromo() {
    try {
        const syncUrl = LARAVEL_WEBHOOK_URL.replace('/wa/webhook', '/wa/initial-promo');
        const res = await axios.get(syncUrl, {
            headers: { 'X-API-KEY': API_KEY },
            timeout: 5000
        });
        if (res.data && res.data.status && res.data.data) {
            cachedInitialPromo = res.data.data;
            console.log('[RentSpace WA Bot] 📦 Initial promo data loaded successfully.');
        } else {
            cachedInitialPromo = null;
        }
    } catch (e) {
        // Abaikan jika Laravel belum siap
    }
}

// Contacts Cache: Menyimpan nama kontak yang di-save admin di HP/Google Kontak
// Map: jid/phone -> { name, notify, verifiedName }
const contactsMap = new Map();

function getSavedContactName(jid, phone) {
    if (!jid && !phone) return null;
    const cleanPhone = (phone || '').replace(/[^0-9]/g, '');
    const jidClean = (jid || '').split('@')[0].split(':')[0];

    const contact = contactsMap.get(jid)
        || contactsMap.get(`${cleanPhone}@s.whatsapp.net`)
        || contactsMap.get(jidClean)
        || contactsMap.get(cleanPhone);

    return contact?.name || contact?.notify || contact?.verifiedName || null;
}

// Cache pesan terkini untuk resolve pesan yang di-PIN di grup
// Map: messageId -> { text, senderPhone, senderName, groupJid, timestamp }
const messageCache = new Map();
function cacheGroupMessage(msgId, data) {
    if (!msgId) return;
    messageCache.set(msgId, data);
    if (messageCache.size > 1000) {
        const first = messageCache.keys().next().value;
        messageCache.delete(first);
    }
}

async function syncPinnedMessage({ messageId, groupJid, isPinned, text = '', senderPhone = '', senderName = '' }) {
    if (!messageId) return;
    try {
        console.log(`[RentSpace WA Bot] 📌 Syncing PIN status: msgId=${messageId}, isPinned=${isPinned}, text="${text.substring(0, 30)}..."`);
        await axios.post(LARAVEL_WEBHOOK_URL, {
            action: 'sync_pinned_message',
            message_id: messageId,
            group_jid: groupJid,
            is_pinned: isPinned,
            text: text,
            sender_phone: senderPhone,
            sender_name: senderName
        }, {
            headers: { 'X-API-KEY': API_KEY },
            timeout: 10000
        });
    } catch (err) {
        console.error('[RentSpace WA Bot] Gagal sync pinned message:', err.message);
    }
}

// Track ID pesan yang dikirim oleh bot sendiri agar tidak dianggap sebagai chat manual admin
const botSentMsgIds = new Set();
function markBotSent(msgId) {
    if (!msgId) return;
    botSentMsgIds.add(msgId);
    if (botSentMsgIds.size > 2000) {
        const first = botSentMsgIds.values().next().value;
        botSentMsgIds.delete(first);
    }
}

function isHumanHandling(jid) {
    const until = humanTakeoverMap.get(jid);
    if (!until) return false;
    if (Date.now() < until) return true;
    humanTakeoverMap.delete(jid);
    return false;
}

// "Sedang mengetik" di WhatsApp punya masa berlaku sendiri, jadi harus
// disegarkan berkala selama menunggu maupun selama AI berpikir. Jeda quiet
// period-nya sendiri sudah diatur di lib/chat-humanizer.js.
const PRESENCE_REFRESH_MS = 9000;
const TYPING_ON_DELAY_MIN_MS = 700;
const TYPING_ON_DELAY_MAX_MS = 1600;

/**
 * Tampilkan "sedang mengetik" sampai fungsi yang dikembalikan dipanggil.
 *
 * Presence WhatsApp hilang kalau tidak disegarkan, jadi diulang tiap
 * PRESENCE_REFRESH_MS selama bot masih menunggu atau AI masih berpikir.
 * Kalau bot lama diam saja, customer tidak tahu pesannya sudah dibaca.
 */
function startTyping(jid) {
    let stopped = false;

    const loop = async () => {
        while (!stopped) {
            try {
                await sock.sendPresenceUpdate('composing', jid, 'text');
            } catch (_) { /* presence bukan hal kritis */ }
            if (stopped) break;
            await sleep(PRESENCE_REFRESH_MS);
        }
    };
    loop().catch((_) => { /* diabaikan */ });

    return () => { stopped = true; };
}

/**
 * Kirim balasan chat dengan gaya orang: dipecah beberapa bubble, jeda acak
 * di antaranya, dan "sedang mengetik" tetap aktif selama jeda.
 *
 * Kalau selagi jeda ada pertanyaan baru, sisa pecahan dibuang supaya customer
 * tidak menerima jawaban yang sudah basi.
 *
 * @returns {Promise<{delivered: boolean, stale: boolean}>}
 */
async function deliverChatText(jid, text, { guard = null, onStale = null } = {}) {
    const chunks = splitForChat(text);
    if (!chunks.length) return { delivered: false, stale: false };

    let delivered = false;
    for (let i = 0; i < chunks.length; i++) {
        if (i > 0) await sleep(randomBetween(BUBBLES.gapMinMs, BUBBLES.gapMaxMs));
        if (guard && !guard()) {
            if (onStale) onStale();
            return { delivered, stale: true };
        }
        if (isHumanHandling(jid)) {
            console.log(`[RentSpace WA Bot] Pengiriman bubble AI dibatalkan untuk ${jid}: Admin sudah membalas di HP.`);
            if (onStale) onStale();
            return { delivered, stale: true };
        }
        const sentResult = await sock.sendMessage(jid, { text: chunks[i] });
        if (sentResult?.key?.id) {
            markBotSent(sentResult.key.id);
        }
        delivered = true;
    }

    return { delivered, stale: false };
}

/**
 * Teruskan chat customer ke admin (dikirim ke nomor admin sekunder dan WA admin
 * utama oleh Laravel).
 */
async function forwardToAdmin(sender, phone, pushName, text, reason) {
    try {
        await axios.post(LARAVEL_WEBHOOK_URL, {
            action: 'forward_admin',
            sender_jid: sender,
            phone: phone,
            name: pushName,
            text: text,
            reason: reason
        }, {
            headers: { 'X-API-KEY': API_KEY },
            timeout: 8000
        });
    } catch (err) {
        console.error('[RentSpace WA Bot] Forward Admin Error:', err.message);
    }
}

// Auth middleware for REST API
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers['authorization'] || req.headers['x-api-key'];
    if (!authHeader || (authHeader !== API_KEY && authHeader !== `Bearer ${API_KEY}`)) {
        return res.status(401).json({ status: false, message: 'Unauthorized. Invalid API Key.' });
    }
    next();
};

const formatToJid = (phone) => {
    if (typeof phone === 'string' && (phone.endsWith('@g.us') || phone.endsWith('@s.whatsapp.net'))) {
        return phone;
    }
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('+62')) {
        clean = clean.replace('+', '');
    }
    return `${clean}@s.whatsapp.net`;
};

async function connectToWhatsApp() {
    const sessionDir = path.join(__dirname, 'auth_info_baileys');
    if (!fs.existsSync(sessionDir)) {
        fs.mkdirSync(sessionDir, { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(sessionDir);
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`[RentSpace WA Bot] Using Baileys v${version.join('.')}, isLatest: ${isLatest}`);

    sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        auth: state,
        browser: ['RentSpace Purwokerto', 'Chrome', '1.0.0']
    });

    sock.ev.on('creds.update', saveCreds);

    // Sync Contacts (Nama Kontak yang di-save di HP / Google Kontak)
    sock.ev.on('contacts.upsert', (contacts) => {
        for (const contact of contacts) {
            if (contact.id) {
                const existing = contactsMap.get(contact.id) || {};
                contactsMap.set(contact.id, { ...existing, ...contact });
                const clean = contact.id.split('@')[0].split(':')[0];
                contactsMap.set(clean, { ...existing, ...contact });
            }
        }
    });

    sock.ev.on('contacts.update', (updates) => {
        for (const update of updates) {
            if (update.id) {
                const existing = contactsMap.get(update.id) || {};
                contactsMap.set(update.id, { ...existing, ...update });
                const clean = update.id.split('@')[0].split(':')[0];
                contactsMap.set(clean, { ...existing, ...update });
            }
        }
    });

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            qrCodeRaw = qr;
            connectionStatus = 'qr_ready';
            console.log('\n[RentSpace WA Bot] SCAN QR CODE DI BAWAH INI:');
            qrcodeTerminal.generate(qr, { small: true });
        }

        if (connection === 'close') {
            connectionStatus = 'close';
            qrCodeRaw = null;
            botUser = null;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log(`[RentSpace WA Bot] Connection closed (code: ${statusCode}). Reconnecting: ${shouldReconnect}`);

            if (shouldReconnect) {
                setTimeout(connectToWhatsApp, 5000);
            } else {
                console.log('[RentSpace WA Bot] Logged out. Session cleared, please restart to re-scan.');
                fs.rmSync(sessionDir, { recursive: true, force: true });
            }
        } else if (connection === 'open') {
            connectionStatus = 'open';
            qrCodeRaw = null;
            botUser = sock.user;
            console.log(`[RentSpace WA Bot] ✅ WHATSAPP BOT CONNECTED! Logged in as: ${botUser?.name || botUser?.id}`);
        }
    });

    // Handle Incoming Messages
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const msg of messages) {
            if (!msg.message) continue;

            const sender = msg.key.remoteJid;
            const text = msg.message.conversation ||
                         msg.message.extendedTextMessage?.text ||
                         msg.message.imageMessage?.caption ||
                         '';
            const trimmedText = text.trim();
            const lowerText = trimmedText.toLowerCase();

            // 1. Deteksi Chat Keluar dari HP Admin (Human Takeover)
            if (msg.key.fromMe) {
                // Abaikan jika pesan ini dikirim oleh bot sendiri
                const msgId = msg.key.id;
                if (botSentMsgIds.has(msgId)) {
                    continue;
                }

                // Jika admin mengirim pesan di chat personal customer (bukan grup)
                if (sender && !sender.endsWith('@g.us')) {
                    // Cek perintah manual admin di chat tersebut
                    if (lowerText === '!bot' || lowerText === '!on' || lowerText === '!unmute') {
                        humanTakeoverMap.delete(sender);
                        console.log(`[RentSpace WA Bot] 🤖 Bot diaktifkan kembali untuk ${sender} oleh admin.`);
                        continue;
                    }
                    if (lowerText === '!off' || lowerText === '!stop' || lowerText === '!mute') {
                        // Mute 24 jam (manual mute)
                        humanTakeoverMap.set(sender, Date.now() + 24 * 60 * 60 * 1000);
                        console.log(`[RentSpace WA Bot] 🔇 Bot dimatikan manual untuk ${sender} selama 24 jam.`);
                        continue;
                    }

                    // Admin mengetik chat normal: set takeover 5 menit sejak pesan ini
                    humanTakeoverMap.set(sender, Date.now() + HUMAN_TAKEOVER_TIMEOUT_MS);
                    console.log(`[RentSpace WA Bot] 👤 Admin membalas di HP untuk ${sender}. Bot OFF selama 5 menit.`);

                    // Jika bot sedang punya antrean pending untuk customer ini, batalkan
                    const queueState = chatQueues.get(sender);
                    if (queueState) {
                        queueState.pending = [];
                    }
                }
                continue;
            }

            // Abaikan WhatsApp Story / Status Broadcast / Pesan Broadcast
            if (!sender || sender === 'status@broadcast' || sender.endsWith('@broadcast') || msg.broadcast) {
                continue;
            }

            // 0. Perintah universal !getid / /getid (Untuk cek ID User atau ID Grup WA)
            if (lowerText === '!getid' || lowerText === '/getid') {
                const chatType = sender.endsWith('@g.us') ? 'Grup WhatsApp' : 'Akun Pribadi';
                await sock.sendMessage(sender, {
                    text: `🆔 *ID ${chatType}:*\n\`${sender}\`\n\n_Salin ID di atas untuk didaftarkan pada Pengaturan Bot di Web Admin Rent Space._`
                });
                continue;
            }

            // Resolve identitas pengirim SEBELUM blok grup (dipakai juga di grup)
            let actualPhone = '';
            if (sender.endsWith('@s.whatsapp.net')) {
                actualPhone = sender.replace('@s.whatsapp.net', '');
            } else if (msg.key.participant && msg.key.participant.endsWith('@s.whatsapp.net')) {
                actualPhone = msg.key.participant.replace('@s.whatsapp.net', '');
            } else {
                actualPhone = (msg.key.senderPn || '').replace('@s.whatsapp.net', '') || '';
            }

            const senderNumber = actualPhone || sender.replace('@s.whatsapp.net', '').replace('@lid', '');
            const pushName = msg.pushName || 'Kak';

            // Deteksi Event PinInChat bawaan WhatsApp
            const pinInChat = msg.message?.pinInChatMessage;
            if (pinInChat) {
                const targetKey = pinInChat.key;
                const pinType = pinInChat.type; // 1 = PIN_FOR_ALL, 2 = UNPIN_FOR_ALL
                const isPinned = pinType === 1 || pinType === 'PIN_FOR_ALL';
                const targetMsgId = targetKey?.id;
                const groupJid = sender;

                console.log(`[RentSpace WA Bot] 📌 Deteksi pinInChatMessage: targetId=${targetMsgId}, type=${pinType}, group=${groupJid}`);

                const cached = messageCache.get(targetMsgId);
                await syncPinnedMessage({
                    messageId: targetMsgId,
                    groupJid: groupJid,
                    isPinned: isPinned,
                    text: cached?.text || '',
                    senderPhone: cached?.senderPhone || '',
                    senderName: cached?.senderName || ''
                });
                continue;
            }

            // Jika pesan berada di dalam grup, simpan ke cache agar bisa di-resolve saat di-PIN
            const isGroup = sender.endsWith('@g.us');
            if (isGroup && trimmedText) {
                cacheGroupMessage(msg.key.id, {
                    text: trimmedText,
                    senderPhone: senderNumber,
                    senderName: pushName,
                    groupJid: sender,
                    timestamp: msg.messageTimestamp
                });
            }

            if (isGroup) {
                const isAdminCommand = lowerText.startsWith('/rentspacesettings') || lowerText.startsWith('/broadcast') || lowerText.startsWith('/initialchat');
                // Perintah memori AI tidak perlu @mention (dipetakan ke grup report di sisi Laravel)
                const isMemoryCommand = lowerText.startsWith('/memori')
                    || lowerText.startsWith('/ingat')
                    || lowerText.startsWith('/lupa');

                // mentionedJid bisa ada di berbagai tipe pesan, dan pada WA modern
                // identitas LID pun dipakai — karena itu kumpulkan dari semua contextInfo.
                const contextInfo =
                    msg.message?.extendedTextMessage?.contextInfo
                    || msg.message?.imageMessage?.contextInfo
                    || msg.message?.videoMessage?.contextInfo
                    || msg.message?.documentMessage?.contextInfo
                    || msg.message?.audioMessage?.contextInfo
                    || msg.message?.stickerMessage?.contextInfo
                    || msg.message?.buttonsMessage?.contextInfo
                    || msg.message?.listMessage?.contextInfo
                    || msg.message?.ephemeralMessage?.message?.extendedTextMessage?.contextInfo
                    || {};
                const mentionedJids = contextInfo?.mentionedJid || [];

                // Bandingkan user bot pada kedua format: PN (nomor HP) dan LID.
                const stripJid = (jid) => (jid || '').split(':')[0].split('@')[0];
                const botIds = [stripJid(sock.user?.id), stripJid(sock.user?.lid)]
                    .filter((v) => v && v !== '0');
                const isBotMentioned = botIds.length > 0
                    && mentionedJids.some((jid) => botIds.includes(stripJid(jid)));

                console.log(`[RentSpace WA Bot] Grup pesan: isAdminCmd=${isAdminCommand}, isMemoryCmd=${isMemoryCommand}, isMentioned=${isBotMentioned}, botIds=${JSON.stringify(botIds)}, mentions=${JSON.stringify(mentionedJids)}`);

                // Perintah cepat di grup: reply pesan dengan /initialchat untuk simpan foto + caption promo
                if (lowerText.startsWith('/initialchat')) {
                    const quotedMsg = msg.message?.extendedTextMessage?.contextInfo?.quotedMessage;
                    const quotedStanzaId = msg.message?.extendedTextMessage?.contextInfo?.stanzaId;
                    const quotedParticipant = msg.message?.extendedTextMessage?.contextInfo?.participant;

                    // Jika membalas pesan yang memiliki media (foto / video / teks)
                    if (quotedMsg) {
                        try {
                            const isImg = !!quotedMsg.imageMessage;
                            const isVid = !!quotedMsg.videoMessage;
                            let mediaBase64 = '';
                            let mediaType = isImg ? 'image' : (isVid ? 'video' : null);
                            let captionText = (isImg ? quotedMsg.imageMessage?.caption : (isVid ? quotedMsg.videoMessage?.caption : '')) ||
                                              quotedMsg.conversation ||
                                              quotedMsg.extendedTextMessage?.text ||
                                              '';

                            // Jika ada teks tambahan di samping perintah /initialchat, gunakan teks tersebut sebagai caption
                            const customCaption = trimmedText.replace(/^\/initialchat\s*/i, '').trim();
                            if (customCaption) {
                                captionText = customCaption;
                            }

                            if (isImg || isVid) {
                                await sock.sendMessage(sender, {
                                    text: '⏳ Sedang mengunduh foto & menyimpan draf *Initial Chat Promo*... Mohon tunggu sebentar.'
                                }, { quoted: msg });

                                const mediaBuffer = await downloadMediaMessage(
                                    {
                                        message: quotedMsg,
                                        key: {
                                            id: quotedStanzaId,
                                            remoteJid: sender,
                                            participant: quotedParticipant
                                        }
                                    },
                                    'buffer',
                                    {}
                                );
                                mediaBase64 = mediaBuffer.toString('base64');
                            }

                            // Kirim ke endpoint Laravel untuk disimpan di Setting
                            const syncUrl = LARAVEL_WEBHOOK_URL.replace('/wa/webhook', '/wa/initial-promo');
                            await axios.post(syncUrl, {
                                caption: captionText,
                                media_base64: mediaBase64,
                                media_type: mediaType || 'text'
                            }, {
                                headers: { 'X-API-KEY': API_KEY },
                                timeout: 15000
                            });

                            // Update local cache
                            cachedInitialPromo = {
                                active: true,
                                caption: captionText,
                                media_base64: mediaBase64,
                                media_type: mediaType || 'text'
                            };

                            const hasMediaTxt = mediaBase64 ? '📸 Foto / Media tersimpan' : '📝 Teks tersimpan';
                            await sock.sendMessage(sender, {
                                text: `✅ *INITIAL CHAT & PROMO BERHASIL DISIMPAN!*\n------------------------------------\n• *Status*: AKTIF 🟢\n• *Tipe*: ${hasMediaTxt}\n• *Caption/Pesan*:\n"${captionText || '-(tanpa teks)-'}"\n\n💡 _Pesan promo ini akan otomatis dikirimkan ke pelanggan yang baru pertama kali chat atau saat menanyakan promo/pricelist._\n\nKetik \`/initialchat off\` jika ingin menonaktifkan sementara.`
                            }, { quoted: msg });
                            continue;
                        } catch (initErr) {
                            console.error('[RentSpace WA Bot] Gagal simpan initialchat:', initErr);
                            await sock.sendMessage(sender, {
                                text: `⚠️ Gagal menyimpan initial chat: ${initErr.message}`
                            }, { quoted: msg });
                            continue;
                        }
                    } else {
                        // Tidak me-reply pesan, teruskan perintah status / on / off / clear ke Laravel
                        try {
                            const res = await axios.post(LARAVEL_WEBHOOK_URL, {
                                sender_jid: sender,
                                phone: senderNumber,
                                actual_phone: actualPhone,
                                name: pushName,
                                text: trimmedText
                            }, {
                                headers: { 'X-API-KEY': API_KEY },
                                timeout: 15000
                            });

                            if (res.data && res.data.reply) {
                                await sock.sendMessage(sender, { text: res.data.reply }, { quoted: msg });
                            }
                            // Refresh cache
                            await fetchInitialPromo();
                        } catch (cmdErr) {
                            console.error('[RentSpace WA Bot] Gagal memproses /initialchat:', cmdErr);
                        }
                        continue;
                    }
                }

                // Perintah cepat di grup: reply pesan dengan !pin untuk langsung pin ke Web Notes
                const isPinCmd = lowerText === '!pin' || lowerText === '/pin';
                const quotedMsg = msg.message?.extendedTextMessage?.contextInfo?.quotedMessage;
                const quotedStanzaId = msg.message?.extendedTextMessage?.contextInfo?.stanzaId;
                const quotedParticipant = msg.message?.extendedTextMessage?.contextInfo?.participant;

                if (isPinCmd && quotedStanzaId) {
                    const quotedText = quotedMsg?.conversation
                        || quotedMsg?.extendedTextMessage?.text
                        || quotedMsg?.imageMessage?.caption
                        || '';
                    const quotedPhone = (quotedParticipant || '').replace(/[^0-9]/g, '');
                    const quotedSavedName = getSavedContactName(quotedParticipant, quotedPhone);

                    await syncPinnedMessage({
                        messageId: quotedStanzaId,
                        groupJid: sender,
                        isPinned: true,
                        text: quotedText,
                        senderPhone: quotedPhone,
                        senderName: quotedSavedName || pushName
                    });

                    await sock.sendMessage(sender, {
                        text: `📌 *Pesan berhasil di-PIN ke Web Admin Rent Space!*\nBisa dilihat di menu *More -> Notes*.`
                    }, { quoted: msg });
                    continue;
                }

                if (!isAdminCommand && !isBotMentioned && !isMemoryCommand) {
                    continue; // abaikan pesan di grup yang tidak di-tag dan bukan perintah admin
                }

                // Jika bot di-mention di grup (atau perintah memori), kirim ke endpoint report grup
                if (!isAdminCommand && (isBotMentioned || isMemoryCommand)) {
                    // Bersihkan mention text (hapus @nomor dari pesan)
                    const rawText = msg.message?.extendedTextMessage?.text
                        || msg.message?.conversation
                        || trimmedText;
                    const cleanText = rawText.replace(/@\d+/g, '').trim();

                    if (!cleanText) continue;

                    console.log(`[RentSpace WA Bot] Report group query dari ${pushName}: "${cleanText}"`);

                    try {
                        const res = await axios.post(LARAVEL_WEBHOOK_URL, {
                            action: 'report_group_query',
                            sender_jid: sender,
                            phone: senderNumber,
                            actual_phone: actualPhone,
                            name: pushName,
                            text: cleanText
                        }, {
                            headers: { 'X-API-KEY': API_KEY },
                            // Assistant data internal boleh dua giliran AI (jawaban
                            // pertama + giliran ulang dengan data tambahan), jadi
                            // timeout di sini harus lebih besar dari total timeout
                            // AI di sisi Laravel.
                            timeout: 95000
                        });

                        if (res.data && res.data.reply) {
                            await sock.sendMessage(sender, { text: res.data.reply });
                        } else {
                            console.log('[RentSpace WA Bot] Report group: no reply from Laravel');
                        }
                    } catch (err) {
                        console.error('[RentSpace WA Bot] Report Group Query Error:', err.message);
                    }
                    continue;
                }

                // Jangan pernah proses chat grup sebagai chat customer
                continue;
            }



            if (!text.trim()) continue;

            const savedName = getSavedContactName(sender, actualPhone || senderNumber);
            const adminFacingName = savedName || pushName;

            console.log(`[RentSpace WA Bot] Pesan masuk dari ${adminFacingName} (push: ${pushName}) (${isGroup ? 'Group: ' + sender : 'Phone: ' + (actualPhone || 'LID: ' + senderNumber)}): "${text}"`);

            // Catat pesan customer terakhir (non-grup, bukan pemicu admin) supaya
            // saat dia ngetik "ADMIN", forward ke admin membawa pertanyaan aslinya.
            if (!isGroup && !ADMIN_REQ_SET.has(lowerText)) {
                lastCustomerMessage.set(sender, text.trim());
            }

            // 1. Forward otomatis chat customer ke HP Admin (Multi Admin) by system (0 token AI)
            // Gunakan savedName jika ada di kontak HP admin, agar terbaca jelas nama kontak di notifikasi forward
            if (!isGroup) {
                forwardToAdmin(sender, actualPhone || senderNumber, adminFacingName, text.trim(), 'chat customer masuk');
            }

            // 2. Cek apakah admin sedang handle chat customer ini (Human Takeover 5 menit)
            if (!isGroup && isHumanHandling(sender)) {
                console.log(`[RentSpace WA Bot] ⏸️ Bot diam untuk ${adminFacingName} (${sender}): Admin sedang menangani chat ini di HP.`);
                continue;
            }

            enqueueCustomerMessage(sender, senderNumber, actualPhone, pushName, text.trim(), msg);
        }
    });
}

/**
 * Masukkan pesan ke antrean chat, lalu jalankan worker-nya kalau belum jalan.
 *
 * Aturannya:
 * - Satu chat hanya boleh punya satu permintaan AI yang jalan (tidak paralel).
 * - Bot tidak langsung menjawab: dia tunggu sampai customer berhenti ngetik
 *   (quiet period) sambil menampilkan "sedang mengetik", supaya pesan beruntun
 *   digabung jadi satu pertanyaan dan tidak memicu beberapa balasan.
 * - Kalau ada pertanyaan baru yang menimpa, jawaban untuk pertanyaan lama
 *   dibuang (lihat guard di handleIncomingCustomerMessage). Customers yang sudah
 *   nanya ulang tidak ALU-aluan dapat jawaban basi.
 */
function enqueueCustomerMessage(sender, senderNumber, actualPhone, pushName, text, rawMsg) {
    let state = chatQueues.get(sender);
    if (!state) {
        state = { running: false, pending: [] };
        chatQueues.set(sender, state);
    }

    state.pending.push({ sender, senderNumber, actualPhone, pushName, text, rawMsg });

    if (!state.running) {
        drainCustomerQueue(sender);
    }
}

async function drainCustomerQueue(sender) {
    const state = chatQueues.get(sender);
    if (!state || state.running) return;
    state.running = true;

    try {
        while (state.pending.length) {
            // Burst pertama yang isinya pertanyaan sungguhan (bukan cuma "ok")
            // bikin bot langsung terlihat "sedang mengetik" seperti orang yang
            // baca pesan lalu mikir sebentar sebelum ngetik balasan.
            const isCommand = state.pending.some((m) => m.text.startsWith('/'));
            const hasRealQuestion = state.pending.some((m) => !isAck(m.text));

            let stopTyping = null;
            if (hasRealQuestion) {
                await sleep(randomBetween(TYPING_ON_DELAY_MIN_MS, TYPING_ON_DELAY_MAX_MS));
                if (state.pending.length) stopTyping = startTyping(sender);
            }

            // Tunggu sampai customer selesai ngetik. Perintah admin tidak
            // ikut menunggu lama, sudah jelas itu bukan customer yang mengetik.
            await waitForQuietPeriod(state, { quick: isCommand });

            const batch = state.pending.splice(0, state.pending.length);
            const last = batch[batch.length - 1];

            // Guard: kalau selagi menjawab ada pertanyaan baru yang masuk, jawaban
            // ini sudah basi -> jangan dikirim, biar tidak terlihat "nge-lag".
            const guard = () => {
                const pending = (chatQueues.get(sender)?.pending || []);
                return !pending.some((m) => !isAck(m.text));
            };

            const merged = mergeBatchText(batch);

            // Tetap nampil "sedang mengetik" selama AI berpikir dan selama
            // jeda antar-bubble, supaya tidak ada selingan kosong yang panjang.
            if (!stopTyping) stopTyping = startTyping(sender);

            try {
                await handleIncomingCustomerMessage(
                    last.sender, last.senderNumber, last.actualPhone, last.pushName, merged, last.rawMsg, guard
                );
            } catch (err) {
                console.error('[RentSpace WA Bot] Error saat membalas:', err.message);
            }

            if (stopTyping) stopTyping();
            try {
                await sock.sendPresenceUpdate('paused', sender);
            } catch (_) { /* abaikan */ }
        }
    } finally {
        state.running = false;
        if (state.pending.length) {
            // Ada pesan yang masuk tepat di detik terakhir while loop selesai.
            // Kalau tidak dijalankan ulang di sini, pesannya nyangkut di antrean
            // tanpa pernah dibalas.
            drainCustomerQueue(sender);
        } else if (chatQueues.get(sender) === state) {
            chatQueues.delete(sender);
        }
    }
}

// Logic Chatbot Auto-Reply
async function handleIncomingCustomerMessage(sender, senderNumber, actualPhone, pushName, text, rawMsg = null, guard = null) {
    const lower = text.toLowerCase();

    // Semua pengiriman melewati sini supaya bisa di-skip kalau jawabannya sudah
    // basi (customer sudah ganti pertanyaan).
    let staleSkipped = 0;
    const send = async (content) => {
        if (guard && !guard()) {
            staleSkipped++;
            console.log(`[RentSpace WA Bot] Jawaban dibuang (sudah ada pertanyaan baru): "${String(content?.text || '').slice(0, 80)}"`);
            return false;
        }
        if (isHumanHandling(sender)) {
            console.log(`[RentSpace WA Bot] Jawaban dibatalkan (admin mulai handle di HP): "${String(content?.text || '').slice(0, 80)}"`);
            return false;
        }
        const sent = await sock.sendMessage(sender, content);
        if (sent?.key?.id) {
            markBotSent(sent.key.id);
        }
        return true;
    };

    // Balasan isi chat (dari AI), dikirim dengan gaya orang: dipecah jadi
    // beberapa bubble kalau panjang, dengan jeda acak di antaranya.
    const sendChatReply = async (replyText) => {
        const result = await deliverChatText(sender, replyText, {
            guard,
            onStale: () => {
                staleSkipped++;
                console.log(`[RentSpace WA Bot] Sisa balasan dibuang (sudah ada pertanyaan baru): "${String(replyText || '').slice(0, 80)}"`);
            },
        });

        return result.delivered;
    };

    // 0. Perintah Khusus Admin: /broadcast send [Grup] from reply (Kirim foto + caption yang di-reply)
    const replyBroadcastMatch = lower.match(/^\/broadcast\s+send\s+(\d+)\s+from\s+reply(?:\s+(.+))?$/i);
    if (replyBroadcastMatch) {
        const groupNum = parseInt(replyBroadcastMatch[1], 10);
        const customCaptionOverride = replyBroadcastMatch[2] ? replyBroadcastMatch[2].trim() : null;

        const quotedMsg = rawMsg?.message?.extendedTextMessage?.contextInfo?.quotedMessage;
        if (!quotedMsg) {
            await sock.sendMessage(sender, {
                text: '⚠️ Pesan ini bukan balasan (reply) ke foto/media.\n\n' +
                      '📌 *Cara Pakai:*\n' +
                      '1. Kirim foto + caption terlebih dahulu ke chat/grup ini.\n' +
                      '2. Geser / Balas (Reply) foto tersebut, lalu ketik:\n' +
                      '   `/broadcast send ' + groupNum + ' from reply`\n' +
                      '3. Bot akan otomatis mengunduh foto & caption tersebut dan mengirimkannya ke seluruh nomor di Grup ' + groupNum + '.'
            });
            return;
        }

        // Cek apakah pesan yang di-reply memiliki imageMessage atau videoMessage
        const imageMessage = quotedMsg.imageMessage;
        const videoMessage = quotedMsg.videoMessage;

        if (!imageMessage && !videoMessage) {
            // Jika yang di-reply hanya teks biasa
            const quotedText = quotedMsg.conversation || quotedMsg.extendedTextMessage?.text || '';
            if (quotedText) {
                // Forward sebagai teks biasa
                text = `/broadcast send ${groupNum} ${customCaptionOverride || quotedText}`;
            } else {
                await sock.sendMessage(sender, {
                    text: '⚠️ Pesan yang Anda reply tidak berisi foto atau teks yang bisa disiarkan.'
                });
                return;
            }
        } else {
            // Pesan yang di-reply adalah media (gambar/video)
            try {
                await sock.sendMessage(sender, {
                    text: `⏳ Sedang mengunduh media dan menyiapkan broadcast ke Grup ${groupNum}... Mohon tunggu sebentar.`
                });

                // Ambil daftar target nomor dari Laravel
                const targetUrl = LARAVEL_WEBHOOK_URL.replace('/wa/webhook', '/wa/broadcast-targets') + `?group=${groupNum}&sender_jid=${encodeURIComponent(sender)}`;
                const targetsRes = await axios.get(targetUrl, {
                    headers: { 'X-API-KEY': API_KEY },
                    timeout: 10000
                });

                if (!targetsRes.data || !targetsRes.data.status) {
                    await sock.sendMessage(sender, {
                        text: `⚠️ Gagal mengambil daftar target: ${targetsRes.data?.message || 'Grup tidak valid.'}`
                    });
                    return;
                }

                const groupName = targetsRes.data.group_name || `Grup ${groupNum}`;
                const numbers = targetsRes.data.numbers || [];

                if (numbers.length === 0) {
                    await sock.sendMessage(sender, {
                        text: `⚠️ Grup *${groupName}* tidak memiliki daftar nomor kontak penerima.`
                    });
                    return;
                }

                // Download media buffer
                const mediaType = imageMessage ? 'image' : 'video';
                const mediaBuffer = await downloadMediaMessage(
                    {
                        message: quotedMsg,
                        key: {
                            id: rawMsg.message.extendedTextMessage.contextInfo.stanzaId,
                            remoteJid: sender,
                            participant: rawMsg.message.extendedTextMessage.contextInfo.participant
                        }
                    },
                    'buffer',
                    {}
                );

                const finalCaption = customCaptionOverride || (imageMessage ? imageMessage.caption : videoMessage.caption) || '';

                let successCount = 0;
                let failCount = 0;
                const total = numbers.length;

                // Salam acak anti-spam
                const greetings = ['Halo Kak! 😊', 'Halo Kak,', 'Hai Kak! ✨', 'Halo Kak, salam hangat dari Rent Space!'];

                for (let i = 0; i < total; i++) {
                    const num = numbers[i];
                    const targetJid = formatToJid(num);

                    let personalizedCaption = finalCaption;
                    if (finalCaption.startsWith('Halo Kak')) {
                        const randomGreeting = greetings[Math.floor(Math.random() * greetings.length)];
                        personalizedCaption = finalCaption.replace(/^Halo Kak(!|,\s*|\s*)/, randomGreeting + ' ');
                    }

                    try {
                        if (mediaType === 'image') {
                            await sock.sendMessage(targetJid, {
                                image: mediaBuffer,
                                caption: personalizedCaption
                            });
                        } else {
                            await sock.sendMessage(targetJid, {
                                video: mediaBuffer,
                                caption: personalizedCaption
                            });
                        }
                        successCount++;
                    } catch (sendErr) {
                        console.error(`[RentSpace WA Bot] Gagal broadcast media ke ${num}:`, sendErr.message);
                        failCount++;
                    }

                    // Anti-banned Humanized Delay (2-4 detik)
                    const delayMs = Math.floor(Math.random() * 2000) + 2000;
                    await new Promise(r => setTimeout(r, delayMs));

                    // Jeda istirahat setiap 10 pesan (5 detik)
                    if ((i + 1) % 10 === 0 && (i + 1) < total) {
                        await new Promise(r => setTimeout(r, 5000));
                    }
                }

                await sock.sendMessage(sender, {
                    text: `🚀 *BROADCAST MEDIA SELESAI DIKIRIM!*\n` +
                          `------------------------------------\n` +
                          `• *Tipe*: ${mediaType.toUpperCase()} + Caption\n` +
                          `• *Target Grup*: ${groupName}\n` +
                          `• *Total Kontak*: ${total}\n` +
                          `• *Berhasil Terkirim*: ${successCount}\n` +
                          `• *Gagal*: ${failCount}\n\n` +
                          `_Semua pesan media telah terkirim dengan jeda aman anti-banned._`
                });
                return;
            } catch (mediaErr) {
                console.error('[RentSpace WA Bot] Media Broadcast Error:', mediaErr);
                await sock.sendMessage(sender, {
                    text: `⚠️ Terjadi kesalahan saat memproses media broadcast: ${mediaErr.message}`
                });
                return;
            }
        }
    }

    // 0b. Perintah Khusus Admin Umum (/rentspacesettings, /broadcast)
    if (lower.startsWith('/rentspacesettings') || lower.startsWith('/broadcast')) {
        try {
            const res = await axios.post(LARAVEL_WEBHOOK_URL, {
                sender_jid: sender,
                phone: senderNumber,
                actual_phone: actualPhone,
                name: pushName,
                text: text
            }, {
                headers: { 'X-API-KEY': API_KEY },
                timeout: 15000
            });

            if (res.data && res.data.reply) {
                await sock.sendMessage(sender, { text: res.data.reply });
                return;
            }
        } catch (err) {
            console.error('[RentSpace WA Bot] Admin Command Error:', err.message);
            await send({ text: '⚠️ Terjadi kesalahan saat memproses perintah admin: ' + err.message });
            return;
        }
    }

    // 1. Cek apakah customer baru pertama kali chat dan ada initial promo yang aktif
    const isFirstTimeCustomer = !customerGreetedSet.has(sender);
    const isGreeting = ['halo', 'hai', 'hi', 'p', 'menu', 'bantuan', 'start', 'info'].includes(lower) || isGreetingOnly(lower);
    const isPromoKeyword = /\b(promo|diskon|potongan|voucher|pricelist|daftar\s*harga|katalog|paket)\b/i.test(lower);

    // Ambil cache initial promo jika belum ada
    if (!cachedInitialPromo) {
        await fetchInitialPromo();
    }

    if (cachedInitialPromo && cachedInitialPromo.active && (isFirstTimeCustomer || isPromoKeyword)) {
        customerGreetedSet.add(sender);

        const caption = cachedInitialPromo.caption || '';
        const mediaBase64 = cachedInitialPromo.media_base64;
        const mediaType = cachedInitialPromo.media_type || 'image';

        if (mediaBase64) {
            try {
                const buffer = Buffer.from(mediaBase64, 'base64');
                if (mediaType === 'video') {
                    await send({ video: buffer, caption: caption });
                } else {
                    await send({ image: buffer, caption: caption });
                }
                return;
            } catch (mediaErr) {
                console.error('[RentSpace WA Bot] Gagal kirim initial promo media:', mediaErr);
                if (caption) {
                    await sendChatReply(caption);
                    return;
                }
            }
        } else if (caption) {
            await sendChatReply(caption);
            return;
        }
    }

    // 1b. Menu Bantuan / Halo (Jika tidak ada initial promo aktif)
    if (isGreeting) {
        customerGreetedSet.add(sender);
        const replyMenu = `Halo Kak *${pushName}*! 👋\n` +
            `Selamat datang di WhatsApp Official *Rent Space Purwokerto* 🎮📷\n\n` +
            `Ada yang bisa kami bantu? Silakan balas dengan angka atau kata kunci:\n\n` +
            `*1* / *KATALOG* : Cek daftar unit & harga sewa\n` +
            `*2* / *CEK [KODE]* : Cek status booking (Contoh: *CEK RS-123456*)\n` +
            `*3* / *WEB* : Kunjungi website & booking online\n` +
            `*4* / *ADMIN* : Hubungkan langsung dengan Admin kami\n\n` +
            `_Ketik pilihan Anda di bawah ini ya!_`;

        await send({ text: replyMenu });
        return;
    }

    // 2. Info Web Booking
    if (lower === '3' || lower === 'web' || lower === 'booking') {
        const reply = `Kakak bisa langsung lihat ketersediaan unit dan booking online melalui website resmi kami:\n\n` +
            `🌐 https://rentspacepurwokerto.my.id/booking\n\n` +
            `Bisa pilih tanggal, durasi sewa, dan metode pembayaran otomatis.`;
        await send({ text: reply });
        return;
    }

    // 3. Hubungi Admin
    if (ADMIN_REQ_SET.has(lower)) {
        const reply = `Mohon tunggu sebentar ya Kak *${pushName}*, pesan Kakak sudah kami teruskan ke Admin Rent Space. Admin kami akan segera menghubungi atau merespon chat Kakak di nomor ini. 🙏`;
        await send({ text: reply });

        const forwardText = (lastCustomerMessage.get(sender) || text.trim()) + ' -admin';
        const savedContactName = getSavedContactName(sender, actualPhone || senderNumber);
        const adminFacingName = savedContactName || pushName;

        try {
            await axios.post(LARAVEL_WEBHOOK_URL, {
                action: 'forward_admin',
                sender_jid: sender,
                phone: actualPhone || '',
                name: adminFacingName,
                text: forwardText
            }, {
                headers: { 'X-API-KEY': API_KEY },
                timeout: 8000
            });
        } catch (err) {
            console.error('[RentSpace WA Bot] Forward Admin Error:', err.message);
        }
        return;
    }

    // 4. Delegasikan query ke Laravel Webhook (AI Gemini: katalog, jadwal, promo, cek status)
    try {
        const res = await axios.post(LARAVEL_WEBHOOK_URL, {
            sender_jid: sender,
            phone: senderNumber,
            actual_phone: actualPhone,
            name: pushName,
            text: text
        }, {
            headers: { 'X-API-KEY': API_KEY },
            // Harus LEBIH LAMA dari timeout AI di sisi Laravel (30 detik), kalau tidak
            // jawaban yang lambat justru dibuang dan customer tidak dapat apa-apa.
            timeout: 45000
        });

        // Chat di luar topik sewa (curhat, tugas, dll): tidak dijawab AI, cuma
        // diarahkan ke admin. Balasan model tidak ikut dikirim.
        if (res.data && res.data.handoff) {
            const reason = res.data.handoff_reason || 'di luar topik sewa';
            await sendChatReply('Maaf kak, untuk yang itu aku teruskan ke admin ya. Tunggu sebentar, admin kami akan nge-chat kakak sendiri 🙏');
            const savedContactName = getSavedContactName(sender, actualPhone || senderNumber);
            await forwardToAdmin(sender, actualPhone || senderNumber, savedContactName || pushName, text, reason);
            return;
        }

        // Jika respons dari Laravel menyertakan media promo
        if (res.data && res.data.media_base64) {
            try {
                const buffer = Buffer.from(res.data.media_base64, 'base64');
                const caption = res.data.reply || '';
                const mType = res.data.media_type || 'image';
                if (mType === 'video') {
                    await send({ video: buffer, caption: caption });
                } else {
                    await send({ image: buffer, caption: caption });
                }
                return;
            } catch (mErr) {
                console.error('[RentSpace WA Bot] Gagal mengirim media balasan:', mErr);
            }
        }

        if (res.data && res.data.reply) {
            await sendChatReply(res.data.reply);
            return;
        }
    } catch (err) {
        console.error('[RentSpace WA Bot] Laravel Webhook Error:', err.message);
    }

    // Kalau AI tidak mengembalikan apa-apa, jangan diam. Satu kalimat saja,
    // bukan template panjang yang justru makin kelihatan bot.
    if (staleSkipped === 0) {
        const defaultReply = `Maaf kak, untuk itu aku cekin dulu ya. Balas *ADMIN* kalau mau langsung ngobrol sama admin.`;
        await send({ text: defaultReply });
    }
}

// REST Endpoints
app.get('/status', (req, res) => {
    res.json({
        status: true,
        connection: connectionStatus,
        bot_user: botUser,
        qr_available: !!qrCodeRaw
    });
});

app.get('/qr', async (req, res) => {
    if (connectionStatus === 'open') {
        const botName = botUser?.name || 'Rent Space Bot';
        const botPhone = (botUser?.id || '').split(':')[0].split('@')[0] || '-';

        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>RentSpace WA Bot - Connected</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background: #0b1120; color: white; margin: 0; padding: 1.5rem; box-sizing: border-box; }
                .card { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); padding: 2.25rem 2rem; border-radius: 1.25rem; text-align: center; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.6); max-width: 440px; width: 100%; box-sizing: border-box; }
                .badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 0.85rem; margin-bottom: 1.25rem; border: 1px solid rgba(16, 185, 129, 0.35); }
                .badge-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981; }
                h2 { margin: 0 0 8px 0; font-size: 1.35rem; font-weight: 700; color: #f8fafc; }
                p.desc { color: #94a3b8; font-size: 0.85rem; line-height: 1.5; margin: 0 0 1.5rem 0; }
                .info-box { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; text-align: left; font-size: 0.82rem; }
                .info-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }
                .info-label { color: #64748b; }
                .info-value { font-weight: 600; color: #f1f5f9; font-family: monospace; }
                .btn-group { display: flex; flex-direction: column; gap: 0.65rem; }
                .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 11px 16px; border-radius: 9px; font-weight: 600; font-size: 0.875rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.15s ease; box-sizing: border-box; }
                .btn-primary { background: #0284c7; color: white; }
                .btn-primary:hover { background: #0369a1; }
                .btn-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
                .btn-warning:hover { background: rgba(245, 158, 11, 0.25); }
                .btn-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
                .btn-danger:hover { background: rgba(239, 68, 68, 0.25); color: #ef4444; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="badge">
                    <span class="badge-dot"></span>
                    WhatsApp Bot Terhubung
                </div>
                <h2>Bot Sedang Aktif</h2>
                <p class="desc">Akun WhatsApp resmi Rent Space sudah berhasil terhubung dan siap merespons chat pelanggan & staf.</p>
                
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Nama Perangkat:</span>
                        <span class="info-value">${botName}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nomor WhatsApp:</span>
                        <span class="info-value">+${botPhone}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status Koneksi:</span>
                        <span class="info-value" style="color: #34d399;">ONLINE</span>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="/admin/settings?tab=whatsapp" class="btn btn-primary">
                        Kembali ke Dashboard
                    </a>
                    
                    <form method="POST" action="/restart-bot" style="margin: 0;" onsubmit="return confirm('Restart koneksi bot sekarang?');">
                        <button type="submit" class="btn btn-warning">
                            🔄 Restart Koneksi Bot
                        </button>
                    </form>

                    <form method="POST" action="/logout-bot" style="margin: 0;" onsubmit="return confirm('YAKIN INGIN LOGOUT?\\n\\nSesi WhatsApp ini akan diputus dan dihapus dari server. Anda harus scan QR ulang dari HP untuk menghubungkan kembali.');">
                        <button type="submit" class="btn btn-danger">
                            🚪 Putuskan Sesi & Logout
                        </button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        `;
        return res.send(html);
    }

    if (!qrCodeRaw) {
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Menyiapkan QR Code...</title>
            <meta http-equiv="refresh" content="3">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background: #0f172a; color: white; margin: 0; }
                .card { background: #1e293b; padding: 2rem; border-radius: 1rem; text-align: center; }
            </style>
        </head>
        <body>
            <div class="card">
                <h3>Sedang membuat QR Code baru...</h3>
                <p style="color: #94a3b8;">Halaman akan otomatis refresh dalam 3 detik</p>
            </div>
        </body>
        </html>
        `;
        return res.send(html);
    }

    try {
        const QRCode = require('qrcode');
        const qrImage = await QRCode.toDataURL(qrCodeRaw, {
            width: 320,
            margin: 2,
            color: { dark: '#000000', light: '#ffffff' }
        });

        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Scan WhatsApp QR Code</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background: #0f172a; color: white; margin: 0; }
                .card { background: #1e293b; padding: 2.5rem; border-radius: 1.25rem; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 420px; width: 90%; }
                .qr-box { background: white; padding: 12px; border-radius: 12px; display: inline-block; margin: 1.5rem 0; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
                .qr-box img { display: block; max-width: 100%; height: auto; }
            </style>
            <script>
                setTimeout(() => { location.reload(); }, 15000);
            </script>
        </head>
        <body>
            <div class="card">
                <h2 style="margin: 0 0 8px 0; font-size: 1.35rem;">Scan WhatsApp QR Code</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">Buka WhatsApp &gt; Perangkat Tertaut &gt; Tautkan Perangkat</p>
                <div class="qr-box">
                    <img src="${qrImage}" alt="Scan WhatsApp QR Code" width="280" height="280">
                </div>
                <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Halaman otomatis refresh setiap 15 detik</p>
            </div>
        </body>
        </html>
        `;
        res.send(html);
    } catch (e) {
        res.status(500).send('Gagal membuat gambar QR: ' + e.message);
    }
});

// Endpoint untuk Logout & Putuskan Sesi WhatsApp dari UI Web
app.post('/logout-bot', async (req, res) => {
    try {
        console.log('[RentSpace WA Bot] 🚪 Menerima permintaan Logout dari UI Web...');
        connectionStatus = 'close';
        qrCodeRaw = null;
        botUser = null;

        if (sock) {
            try {
                await sock.logout();
            } catch (_) {
                try { sock.end(new Error('Manual Web Logout')); } catch (__) {}
            }
        }

        // Hapus folder sesi autentikasi
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
            console.log('[RentSpace WA Bot] 🗑️ Sesi auth_info_baileys berhasil dihapus.');
        }

        setTimeout(() => {
            connectToWhatsApp();
        }, 1500);

        return res.redirect('/qr');
    } catch (err) {
        console.error('[RentSpace WA Bot] Logout error:', err);
        return res.status(500).send('Gagal logout: ' + err.message);
    }
});

// Endpoint untuk Restart Koneksi Bot
app.post('/restart-bot', async (req, res) => {
    try {
        console.log('[RentSpace WA Bot] 🔄 Menerima permintaan Restart dari UI Web...');
        if (sock) {
            try { sock.end(new Error('Manual Web Restart')); } catch (_) {}
        }
        setTimeout(() => {
            connectToWhatsApp();
        }, 1500);
        return res.redirect('/qr');
    } catch (err) {
        console.error('[RentSpace WA Bot] Restart error:', err);
        return res.status(500).send('Gagal restart: ' + err.message);
    }
});

// Endpoint untuk kirim pesan WhatsApp dari Laravel
app.post('/send-message', authMiddleware, async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ status: false, message: 'Parameter "phone" dan "message" wajib diisi.' });
    }

    if (connectionStatus !== 'open' || !sock) {
        return res.status(503).json({ status: false, message: 'WhatsApp bot sedang tidak terhubung (disconnected).' });
    }

    try {
        const jid = formatToJid(phone);
        const sent = await sock.sendMessage(jid, { text: message });
        return res.json({ status: true, message: 'Pesan berhasil dikirim.', message_id: sent.key.id });
    } catch (error) {
        console.error('[RentSpace WA Bot] Gagal mengirim pesan:', error);
        return res.status(500).json({ status: false, message: 'Gagal mengirim pesan: ' + error.message });
    }
});

// Jalankan bot & server
app.listen(PORT, () => {
    console.log(`[RentSpace WA Bot API] Server running on http://localhost:${PORT}`);
    connectToWhatsApp();
});
