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
const COALESCE_MS = 1200;    // jeda menunggu pesan beruntun (ketik cepat)
const PRESENCE_TTL_MS = 20000;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Sapaan singkat yang bukan pertanyaan baru: jawaban yang sedang jalan tetap dikirim.
// Dicek per kata (bukan regex utuh) supaya "ok makasih", "terima kasih ya kak",
// dan emoji doang tetap kena, sementara "kak ip 13 ready?" tetap dianggap pertanyaan.
const ACK_WORDS = new Set([
    'ok', 'oke', 'okay', 'sip', 'sipp', 'siap', 'ya', 'iya', 'yes', 'nah', 'good', 'mantap',
    'mksh', 'makasih', 'terima', 'kasih', 'terimaksih', 'thanks', 'thank', 'you', 'banyak',
    'ntar', 'tunggu', 'wait', 'haha', 'hihi', 'hehe', 'wkwk', 'betul', 'benar',
    'kak', 'kakak', 'sih', 'dong', 'dear',
]);
const isAck = (text) => {
    const raw = String(text || '').trim();
    if (!raw || raw.length > 40) return false;
    const words = raw.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim().split(/\s+/).filter(Boolean);
    if (!words.length) return true;   // cuma emoji, mis. "🙏"
    if (words.length > 5) return false;
    return words.every((w) => ACK_WORDS.has(w));
};

// Auth middleware for REST API
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers['authorization'] || req.headers['x-api-key'];
    if (!authHeader || (authHeader !== API_KEY && authHeader !== `Bearer ${API_KEY}`)) {
        return res.status(401).json({ status: false, message: 'Unauthorized. Invalid API Key.' });
    }
    next();
};

const formatToJid = (phone) => {
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
            if (!msg.message || msg.key.fromMe) continue;

            const sender = msg.key.remoteJid;
            const text = msg.message.conversation ||
                         msg.message.extendedTextMessage?.text ||
                         msg.message.imageMessage?.caption ||
                         '';

            const trimmedText = text.trim();
            const lowerText = trimmedText.toLowerCase();

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

            // Jika dari Grup WhatsApp (@g.us), hanya proses perintah admin khusus
            // ATAU jika dari grup report internal dan bot di-tag (@mention)
            const isGroup = sender.endsWith('@g.us');
            if (isGroup) {
                const isAdminCommand = lowerText.startsWith('/rentspacesettings') || lowerText.startsWith('/broadcast');
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
                            timeout: 60000
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
            }



            if (!text.trim()) continue;

            console.log(`[RentSpace WA Bot] Pesan masuk dari ${pushName} (${isGroup ? 'Group: ' + sender : 'Phone: ' + (actualPhone || 'LID: ' + senderNumber)}): "${text}"`);

            enqueueCustomerMessage(sender, senderNumber, actualPhone, pushName, text.trim(), msg);
        }
    });
}

/**
 * Masukkan pesan ke antrean chat, lalu jalankan worker-nya kalau belum jalan.
 *
 * Aturannya:
 * - Satu chat hanya boleh punya satu permintaan AI yang jalan (tidak paralel).
 * - Pesan yang masuk saat AI masih berpikir TIDAK langsung diproses; dia ditunggu
 *   dan digabung dengan pesan beruntun berikutnya.
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
            // Tunggu dulu: orang yang ngetik cepat sering kirim 2-3 pesan beruntun
            // ("ip 12" lalu "ready kapan?"). Dijawab satu kali, bukan dua kali.
            await sleep(COALESCE_MS);
            const batch = state.pending.splice(0, state.pending.length);
            const last = batch[batch.length - 1];

            // Guard: kalau selagi menjawab ada pertanyaan baru yang masuk, jawaban
            // ini sudah basi -> jangan dikirim, biar tidak terlihat "nge-lag".
            const guard = () => {
                const pending = (chatQueues.get(sender)?.pending || []);
                return !pending.some((m) => !isAck(m.text));
            };

            const merged = mergeBatchText(batch);

            try {
                await sock.sendPresenceUpdate('composing', sender, PRESENCE_TTL_MS);
            } catch (_) { /* presence bukan hal kritis */ }

            try {
                await handleIncomingCustomerMessage(
                    last.sender, last.senderNumber, last.actualPhone, last.pushName, merged, last.rawMsg, guard
                );
            } catch (err) {
                console.error('[RentSpace WA Bot] Error saat membalas:', err.message);
            }

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

/**
 * Gabung beberapa pesan beruntun jadi satu pertanyaan.
 *
 * Kebanyakan orang yang ngetik cepat mengirim potongan ("ip 12" lalu "yang pro
 * max" lalu "harga berapa?"), jadi lebih baik dirangkai utuh daripada diambil
 * satu. Kalau gabungannya sudah panjang, pakai pesan terakhir yang memang
 * pertanyaannya supaya tidak dijawab dua kali.
 */
function mergeBatchText(batch) {
    if (batch.length === 1) return batch[0].text;

    const joined = batch.map((m) => m.text).join(' ');
    if (joined.length <= 80) return joined;

    for (let i = batch.length - 1; i >= 0; i--) {
        if (/[?？]\s*$/.test(batch[i].text) || /\b(kapan|berapa|harga|mana|ready|ada|tersedia|boleh|bisa)\b/i.test(batch[i].text)) {
            return batch[i].text;
        }
    }
    return joined;
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
        await sock.sendMessage(sender, content);
        return true;
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

    // 1. Menu Bantuan / Halo
    if (['halo', 'hai', 'hi', 'p', 'menu', 'bantuan', 'start', 'info'].includes(lower)) {
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
    if (['4', 'admin', 'cs', 'bantuan admin', 'hubungi admin', 'kontak admin'].includes(lower)) {
        const reply = `Mohon tunggu sebentar ya Kak *${pushName}*, pesan Kakak sudah kami teruskan ke Admin Rent Space. Admin kami akan segera menghubungi atau merespon chat Kakak di nomor ini. 🙏`;
        await send({ text: reply });

        // Forward notifikasi ke webhook Laravel agar bisa memberitahu admin sekunder/tim admin
        try {
            await axios.post(LARAVEL_WEBHOOK_URL, {
                action: 'forward_admin',
                sender_jid: sender,
                phone: actualPhone || '',
                name: pushName,
                text: text
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

        if (res.data && res.data.reply) {
            await send({ text: res.data.reply });
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
        const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>RentSpace WA Bot - Connected</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background: #0f172a; color: white; margin: 0; }
                .card { background: #1e293b; padding: 2.5rem; border-radius: 1.25rem; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 400px; width: 90%; }
                .badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 8px 16px; border-radius: 9999px; font-weight: 600; font-size: 0.95rem; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.4); }
                .btn { display: inline-block; margin-top: 1.5rem; padding: 10px 20px; background: #0284c7; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="badge">
                    <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
                    WhatsApp Bot Terhubung!
                </div>
                <h2>Bot Sedang Aktif</h2>
                <p style="color: #94a3b8; font-size: 0.9rem; line-height: 1.5;">Akun WhatsApp resmi Rent Space sudah berhasil terhubung dan siap membalas pesan secara otomatis.</p>
                <a href="/admin/settings?tab=whatsapp" class="btn">Kembali ke Dashboard</a>
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
