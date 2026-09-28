# WhatsApp Bot & Gateway Service (Rent Space)
Bot WhatsApp mandiri berbasis **Node.js + @whiskeysockets/baileys** (ringan, tanpa browser Puppeteer/Chromium).

---

## 🚀 Cara Menjalankan Bot

### 1. Masuk ke folder bot dan install dependencies (jika baru pertama kali):
```bash
cd whatsapp-bot
npm install
```

### 2. Jalankan bot:
```bash
npm start
```
*(Untuk auto-restart saat development: `npm run dev`)*

---

## 📲 Cara Menghubungkan WhatsApp (Scan QR)
Setelah `npm start` dijalankan:
1. QR Code akan muncul langsung di **Terminal**, ATAU
2. Buka browser: `http://localhost:3001/qr` untuk scan dari layar browser yang rapi.
3. Buka WhatsApp di HP Anda > **Perangkat Tertaut (Linked Devices)** > **Tautkan Perangkat** > Scan QR tersebut.
4. Setelah terhubung, sesi akan tersimpan di folder `whatsapp-bot/auth_info_baileys/`. Anda tidak perlu scan ulang saat bot direstart.

---

## ⚡ Fitur Otomatis

### 1. Notifikasi Otomatis ke Customer
- **Saat Booking Dibuat**: Customer menerima detail sewa, ringkasan harga, dan tautan pembayaran.
- **Saat Pembayaran Lunas**: Customer menerima konfirmasi pembayaran lunas dan tautan invoice digital.

### 2. Chatbot Auto-Reply Customer
Customer bisa mengirim pesan berikut ke nomor WhatsApp bot:
- **`MENU` / `HALO`**: Menampilkan daftar menu bantuan.
- **`1` atau `KATALOG`**: Menampilkan daftar unit dan harga sewa terkini langsung dari database Laravel.
- **`2` atau `CEK [KODE_BOOKING]`**: Menampilkan status real-time pesanan (misal: *Menunggu Pembayaran*, *Paid*, dll).
- **`3` atau `WEB`**: Link langsung ke halaman booking website.
- **`4` atau `ADMIN`**: Permintaan bantuan langsung ke admin.

---

## ⚙️ Menjalankan di Server Production (PM2)
Agar bot tetap berjalan di latar belakang (daemon) di server:
```bash
npm install -g pm2
cd whatsapp-bot
pm2 start index.js --name "rentspace-wa-bot"
pm2 save
pm2 startup
```
