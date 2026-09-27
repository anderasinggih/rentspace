const {
    default: makeWASocket,
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    makeInMemoryStore
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
            // Ignore group messages for customer private bot
            if (sender.endsWith('@g.us')) continue;

            const text = msg.message.conversation ||
                         msg.message.extendedTextMessage?.text ||
                         msg.message.imageMessage?.caption ||
                         '';

            let actualPhone = '';
            // Jika remoteJid adalah normal WhatsApp user (@s.whatsapp.net)
            if (sender.endsWith('@s.whatsapp.net')) {
                actualPhone = sender.replace('@s.whatsapp.net', '');
            } else if (msg.key.participant && msg.key.participant.endsWith('@s.whatsapp.net')) {
                actualPhone = msg.key.participant.replace('@s.whatsapp.net', '');
            } else {
                // Jika dari LID, coba cek sender pn jika ada
                actualPhone = (msg.key.senderPn || '').replace('@s.whatsapp.net', '') || '';
            }

            const senderNumber = actualPhone || sender.replace('@s.whatsapp.net', '').replace('@lid', '');
            const pushName = msg.pushName || 'Kak';

            if (!text.trim()) continue;

            console.log(`[RentSpace WA Bot] Pesan masuk dari ${pushName} (Phone: ${actualPhone || 'LID: ' + senderNumber}): "${text}"`);

            await handleIncomingCustomerMessage(sender, senderNumber, actualPhone, pushName, text.trim());
        }
    });
}

// Logic Chatbot Auto-Reply
async function handleIncomingCustomerMessage(sender, senderNumber, actualPhone, pushName, text) {
    const lower = text.toLowerCase();

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

        await sock.sendMessage(sender, { text: replyMenu });
        return;
    }

    // 2. Info Web Booking
    if (lower === '3' || lower === 'web' || lower === 'booking') {
        const reply = `Kakak bisa langsung lihat ketersediaan unit dan booking online melalui website resmi kami:\n\n` +
            `🌐 https://rentspacepurwokerto.my.id/booking\n\n` +
            `Bisa pilih tanggal, durasi sewa, dan metode pembayaran otomatis.`;
        await sock.sendMessage(sender, { text: reply });
        return;
    }

    // 3. Hubungi Admin
    if (['4', 'admin', 'cs', 'bantuan admin', 'hubungi admin', 'kontak admin'].includes(lower)) {
        const reply = `Mohon tunggu sebentar ya Kak *${pushName}*, pesan Kakak sudah kami teruskan ke Admin Rent Space. Admin kami akan segera menghubungi atau merespon chat Kakak di nomor ini. 🙏`;
        await sock.sendMessage(sender, { text: reply });

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

    // 4. Delegasikan query ke Laravel Webhook (untuk cek status booking atau katalog langsung dari DB)
        const res = await axios.post(LARAVEL_WEBHOOK_URL, {
            sender_jid: sender,
            phone: senderNumber,
            actual_phone: actualPhone,
            name: pushName,
            text: text
        }, {
            headers: { 'X-API-KEY': API_KEY },
            timeout: 10000
        });

        if (res.data && res.data.reply) {
            await sock.sendMessage(sender, { text: res.data.reply });
            return;
        }
    } catch (err) {
        console.error('[RentSpace WA Bot] Laravel Webhook Error:', err.message);
    }

    // Fallback pesan default jika tidak dikenali
    const defaultReply = `Terima kasih sudah menghubungi *Rent Space Purwokerto*! 🙏\n\n` +
        `Ketik *MENU* untuk melihat opsi layanan, atau tunggu sebentar tim Admin kami akan segera merespon chat Kakak.`;
    await sock.sendMessage(sender, { text: defaultReply });
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
