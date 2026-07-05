# DOKUMEN PANDUAN PENGGUNAAN SISTEM INFORMASI PENYEWAAN RUANGAN (RENTSPACE)
### Dokumen Panduan Operasional Sistem bagi Administrator, Afiliator, dan Pelanggan
*Dokumen Pendukung Pengajuan Sertifikat Hak Kekayaan Intelektual (HKI) - Kekayaan Intelektual Aplikasi Komputer*

---

## DAFTAR ISI
* **BAB I: PENDAHULUAN**
  - 1.1 Latar Belakang Aplikasi RentSpace
  - 1.2 Keunggulan Sistem & Arsitektur Perangkat Lunak
  - 1.3 Kebutuhan Sistem & Matriks Hak Akses Pengguna (ACL)
* **BAB II: PANDUAN PELANGGAN (CUSTOMER)**
  - 2.1 Mengakses Halaman Utama & Landasan Antarmuka
  - 2.2 Melihat Ketersediaan Ruangan (Booking Timeline)
  - 2.3 Melakukan Reservasi (Booking Form & Validasi Input)
  - 2.4 Pembayaran Transaksi (Payment Gateway Midtrans & Manual)
  - 2.5 Melacak Status Pesanan (Check Order & Invoice)
  - 2.6 Manajemen Akun Member & Autentikasi
  - 2.7 Memberikan Ulasan (Rating & Feedback)
* **BAB III: PANDUAN AFILIATOR (AFFILIATE PARTNER)**
  - 3.1 Registrasi Program Afiliasi & Dokumen Legalitas
  - 3.2 Login Dashboard Afiliator
  - 3.3 Mekanisme Link Referral & Kode Promo Afiliasi
  - 3.4 Monitoring Saldo Komisi & Transaksi Referral
  - 3.5 Pengajuan Pencairan Dana (Payout Request)
* **BAB IV: PANDUAN ADMINISTRATOR (ADMIN / STAFF)**
  - 4.1 Login Dashboard Administrator (Secure Auth)
  - 4.2 Navigasi Dashboard Utama & Statistik Kerja
  - 4.3 Mengelola Kategori & Unit Sewa (Unit Manager)
  - 4.4 Mengelola Aturan Harga & Promo (Pricing Rules & Promo)
  - 4.5 Pengumuman Banner & Informasi Promo (Campaign Manager)
  - 4.6 Monitoring Live Status Sewa Unit (Radar & Monitoring)
  - 4.7 Manajemen Transaksi & Alur Status Pembayaran
  - 4.8 Proses Validasi Instan dengan Quick Scan (QR Code)
  - 4.9 Manajemen Pelanggan & Loyalty Tier
  - 4.10 Manajemen Afiliasi (Validasi Payout & Afiliator)
  - 4.11 Pengaturan Sistem & Integrasi Gateway (Settings)
  - 4.12 Monitoring Riwayat Aktivitas Karyawan (Staff Logs)
  - 4.13 Mengelola Penilaian & Ulasan (Rating Manager)
  - 4.14 Mengunduh & Mencetak Laporan Keuangan (Reports)
* **BAB V: PANDUAN PEMECAHAN MASALAH (TROUBLESHOOTING)**

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang Aplikasi RentSpace
RentSpace adalah sebuah platform berbasis web yang dikembangkan menggunakan framework Laravel dan pustaka Livewire untuk melayani kebutuhan pengelolaan penyewaan ruang secara digital, transparan, dan efisien. Sistem ini menghubungkan pemilik unit/ruangan dengan pelanggan secara langsung, lengkap dengan fitur pembayaran otomatis (Payment Gateway), pelacakan berbasis QR Code, sistem afiliasi untuk pemasaran terdesentralisasi, serta monitoring perangkat sensor secara terintegrasi.

### 1.2 Keunggulan Sistem & Arsitektur Perangkat Lunak
Sistem RentSpace dirancang dengan arsitektur modern untuk menjamin keandalan dan skalabilitas:
* **Sistem Pemesanan Real-time**: Menggunakan kalender timeline dinamis guna menghindari bentrok jadwal (double-booking).
* **Integrasi Pembayaran Digital**: Mendukung pembayaran multi-channel otomatis melalui API Midtrans (Virtual Account, E-Wallet, Kartu Kredit).
* **Pemasaran Afiliasi Terintegrasi**: Memungkinkan pihak ketiga mempromosikan unit dengan skema bagi hasil komisi otomatis.
* **Keamanan Validasi QR Code**: Dilengkapi modul pemindaian cepat (Quick Scan) untuk meminimalkan waktu check-in/check-out.
* **Monitoring Aktivitas & Sistem**: Fitur staff logs serta integrasi radar eksternal untuk pengawasan operasional secara komprehensif.

### 1.3 Kebutuhan Sistem & Matriks Hak Akses Pengguna (ACL)
Sistem membedakan pengguna menjadi tiga level utama melalui Access Control List (ACL) yang ketat:

| Fitur / Modul | Administrator | Afiliator | Pelanggan |
| :--- | :--- | :--- | :--- |
| **Mengakses Katalog & Booking** | Baru / Edit / Hapus | Lihat Saja | Akses Penuh / Transaksi |
| **Link Referral & Tarik Komisi** | Validasi Finansial | Akses Penuh | Tidak Ada |
| **Manajemen Konfigurasi & API** | Akses Penuh | Tidak Ada | Tidak Ada |
| **Riwayat Log Aktivitas Staf** | Akses Penuh | Tidak Ada | Tidak Ada |

---

## BAB II: PANDUAN PELANGGAN (CUSTOMER)

### 2.1 Mengakses Halaman Utama & Landasan Antarmuka
1. Buka penjelajah web (browser) Anda (Google Chrome, Mozilla Firefox, Safari, atau Microsoft Edge).
2. Ketikkan URL domain utama website RentSpace (contoh: `https://rentspace.com` atau domain lokal yang Anda gunakan).
3. Pengguna akan diarahkan ke halaman beranda utama (Welcome Page) yang menyajikan ringkasan tentang unit sewa terbaik, promosi yang berjalan, dan navigasi utama sistem.

### 2.2 Melihat Ketersediaan Ruangan (Booking Timeline)
1. Pada menu navigasi atas, klik tombol **"Sewa"** atau **"Jadwal"**.
2. Anda akan diarahkan ke halaman Timeline Pemesanan (`/sewa`).
3. Di halaman ini, Anda dapat melihat garis waktu (kalender baris) pemesanan dari seluruh unit yang tersedia. Warna atau penanda tertentu mewakili jadwal yang sudah terisi dan jadwal yang masih kosong.
4. Anda dapat menyaring ruangan berdasarkan Kategori atau memilih Tanggal tertentu untuk merencanakan masa penyewaan.

### 2.3 Melakukan Reservasi (Booking Form & Validasi Input)
1. Pilih unit yang diinginkan dari daftar unit, lalu klik tombol **"Booking Sekarang"**.
2. Isi **Formulir Pemesanan** (`/booking`) dengan data-data berikut secara lengkap:
   * **Informasi Pribadi**: Nama Lengkap, Nomor Identitas KTP (NIK), Alamat Email Aktif, Nomor Telepon, serta Akun Sosial Media (opsional, untuk validasi).
   * **Tanggal & Waktu Sewa**: Tentukan tanggal check-in (mulai) dan check-out (selesai).
   * **Layanan Tambahan (Upsell)**: Pilih fasilitas ekstra jika disediakan (seperti konsumsi tambahan, sewa proyektor, penambahan kapasitas daya listrik, dll.).
   * **Kode Promo/Afiliasi**: Masukkan kode promo jika Anda memilikinya untuk mendapatkan potongan harga secara instan.
3. Klik tombol **"Lanjutkan ke Pembayaran"** setelah memeriksa kembali ringkasan rincian total tagihan.

### 2.4 Pembayaran Transaksi (Payment Gateway Midtrans & Manual)
1. Setelah formulir dikirim, sistem akan membuat pesanan baru dan mengalihkan Anda ke halaman pembayaran (`/payment/{booking_code}`).
2. Apabila menggunakan **Midtrans Payment Gateway**:
   * Klik tombol **"Bayar Sekarang"** untuk membuka jendela interaktif Midtrans.
   * Pilih metode pembayaran yang dikehendaki (Transfer Bank, QRIS, GoPay, ShopeePay, Alfamart, dll.).
   * Salin kode pembayaran (Nomor VA) atau pindai kode QRIS yang tampil di layar.
   * Lakukan pembayaran sebelum batas waktu kedaluwarsa berakhir.
   * Setelah transaksi sukses, sistem akan mendeteksi pembayaran secara otomatis dan mengubah status pesanan menjadi *"Paid"* atau *"Confirmed"*. Pengguna juga akan menerima bukti email konfirmasi pemesanan.
3. Apabila diatur menggunakan pembayaran manual, ikuti instruksi transfer rekening bank pengelola dan unggah bukti transfer pada formulir yang disediakan.

### 2.5 Melacak Status Pesanan (Check Order & Invoice)
1. Untuk mengetahui status terkini dari pesanan, klik menu **"Cek Pesanan"** (`/cek-pesanan`) di navigasi atas.
2. Masukkan **Kode Booking** (contoh: `VZXCUBEIOIASD`) yang Anda dapatkan saat reservasi.
3. Klik **"Cari Pesanan"**.
4. Halaman akan menampilkan riwayat rinci, mencakup status pembayaran (*Pending / Paid / Cancelled*), rincian unit, masa sewa, status penyerahan kunci (*Renting / Handed Over*), hingga denda keterlambatan/kerusakan jika ada.

### 2.6 Manajemen Akun Member & Autentikasi
1. Pelanggan dapat membuat akun keanggotaan agar tidak perlu mengetik ulang informasi identitas setiap melakukan sewa.
2. Klik tombol **"Masuk"** (`/masuk`) di pojok kanan atas.
3. Masukkan Email dan Password Anda. Jika belum terdaftar, klik tautan registrasi terlebih dahulu.
4. Dengan masuk sebagai member, Anda berhak mendapatkan poin loyalitas atau tergolong ke dalam loyalty tier tertentu yang memberikan diskon khusus secara berkala.
5. Untuk keluar dari sistem, klik menu **"Keluar"** (`/keluar`).

### 2.7 Memberikan Ulasan (Rating & Feedback)
1. Setelah periode penyewaan Anda berakhir (status transaksi dinyatakan Selesai oleh Admin), Anda akan menerima akses untuk memberikan penilaian di halaman pelacakan pesanan.
2. Berikan bintang penilaian (skala 1 sampai 5) dan tuliskan ulasan/feedback tertulis mengenai pengalaman Anda menyewa unit tersebut.
3. Kirim ulasan Anda. Penilaian Anda membantu admin meningkatkan kualitas pelayanan.

---

## BAB III: PANDUAN AFILIATOR (AFFILIATE PARTNER)

### 3.1 Registrasi Program Afiliasi & Dokumen Legalitas
1. Akses halaman pendaftaran mitra afiliasi melalui tautan `/affiliate/register`.
2. Isi formulir pendaftaran yang meliputi: Nama, Alamat Email, Nomor WhatsApp, NIK, Password, serta Informasi Rekening Bank (untuk tujuan pencairan komisi).
3. Setelah klik **"Daftar"**, status akun Anda akan masuk dalam daftar tunggu (*Pending*). Akun Anda baru dapat digunakan setelah ditinjau dan disetujui oleh Administrator RentSpace.

### 3.2 Login Dashboard Afiliator
1. Jika akun Anda telah aktif, buka halaman `/affiliate/login`.
2. Masukkan Alamat Email dan Password yang telah Anda daftarkan.
3. Klik **"Login"**. Anda akan diarahkan ke halaman **Dashboard Afiliasi** (`/affiliate/dashboard`).

### 3.3 Mekanisme Link Referral & Kode Promo Afiliasi
1. Di halaman utama dashboard afiliasi, Anda akan melihat **Kode Referral / Kode Promo Khusus** Anda.
2. Salin link referral yang tertera.
3. Sebarkan tautan ini atau bagikan kode promo tersebut melalui media sosial, blog, atau pesan instan kepada calon penyewa.
4. Setiap transaksi yang berhasil terbayar menggunakan tautan atau kode promo Anda akan dikonversi menjadi komisi bagi hasil dengan persentase sesuai kebijakan admin.

### 3.4 Monitoring Saldo Komisi & Transaksi Referral
1. Di dalam Dashboard Afiliasi, Anda disajikan metrik real-time:
   * **Total Akumulasi Pendapatan**: Total seluruh komisi yang pernah didapat.
   * **Saldo Saat Ini**: Saldo aktif yang belum dicairkan.
   * **Jumlah Transaksi Sukses**: Berapa kali kode/link Anda berhasil menghasilkan pemesanan.
2. Di bagian bawah dashboard, terdapat tabel riwayat referral yang merinci tanggal transaksi, kode booking pelanggan, nominal sewa, serta nilai komisi yang Anda peroleh.

### 3.5 Pengajuan Pencairan Dana (Payout Request)
1. Buka menu **"Penarikan Dana"** (`/affiliate/payout`).
2. Tinjau syarat batas minimal penarikan yang berlaku.
3. Input jumlah nominal saldo yang ingin dicairkan ke rekening bank Anda.
4. Klik **"Kirim Pengajuan"**.
5. Permintaan Anda akan masuk ke daftar peninjauan admin. Admin akan mentransfer dana ke rekening terdaftar Anda dan mengubah status pengajuan menjadi *"Approved"* setelah proses transfer selesai dilakukan.

---

## BAB IV: PANDUAN ADMINISTRATOR (ADMIN / STAFF)

### 4.1 Login Dashboard Administrator (Secure Auth)
1. Buka halaman utama admin di URL: `domain/login`.
2. Masukkan alamat email admin dan kata sandi Anda.
3. Klik **"Log In"**. Jika data benar, Anda akan diarahkan ke Dashboard Utama Administrator.

### 4.2 Navigasi Dashboard Utama & Statistik Kerja
Dashboard Admin (`/admin/dashboard`) menampilkan rangkuman grafik serta kartu metrik operasional:
* **Statistik Utama**: Total unit terisi hari ini, grafik omset bulanan, jumlah pelanggan aktif, dan rasio okupansi ruang.
* **Tindakan Cepat**: Pintasan ke fitur scan QR, input transaksi baru, dan monitoring perangkat.
* **Pemberitahuan Sistem**: Daftar pesanan masuk yang memerlukan konfirmasi segera atau pengembalian unit yang terlambat.

### 4.3 Mengelola Kategori & Unit Sewa (Unit Manager)
Untuk menambah atau merubah daftar ruangan/kamar yang Anda sewakan:
1. Buka menu **"Manajemen Unit"** (`/admin/units`).
2. **Kategori Unit**: Tambahkan kategori unit terlebih dahulu (misalnya: *Meeting Room, Coworking Desk, Event Hall*). Tentukan karakteristik unik atau *custom fields* per kategori.
3. **Menambah Unit**:
   * Klik tombol **"Tambah Unit Baru"**.
   * Lengkapi formulir: Nama Unit, Kategori, Lokasi Unit, Harga Sewa (Per Jam / Per Hari), dan Deskripsi Detail Ruangan.
   * Unggah foto-foto unit sewa melalui kolom berkas gambar.
   * Aktifkan layanan tambahan atau upsell (seperti sewa sound system, konsumsi) beserta tarifnya.
   * Tentukan aturan nominal denda kerusakan standard untuk unit tersebut.
   * Klik **"Simpan"**.
4. **Mengubah/Menghapus Unit**: Cari unit dari tabel, klik tombol **"Edit"** untuk memperbarui data, atau klik **"Hapus"** (sistem menggunakan Soft Delete sehingga data transaksi lampau tidak rusak).

### 4.4 Mengelola Aturan Harga & Promo (Pricing Rules & Promo)
1. Buka menu **"Promo & Pricing Rules"** (`/admin/promo`).
2. Fitur ini berguna untuk memberikan potongan harga otomatis berdasarkan kriteria tertentu atau kode kupon.
3. **Membuat Aturan Baru**:
   * Klik **"Tambah Aturan Harga"**.
   * Isi kolom: Nama Aturan, Tipe (Diskon Persentase atau Nominal Tetap), Nilai Diskon, Batas Periode Tanggal Berlaku, serta Jumlah Limit Penggunaan Kupon.
   * Pilih tipe target: diskon umum, diskon tier keanggotaan tertentu, atau diskon khusus yang terikat kode afiliasi.
   * Klik **"Simpan"**.

### 4.5 Pengumuman Banner & Informasi Promo (Campaign Manager)
1. Pilih menu **"Campaign Manager"** (`/admin/campaign`).
2. Klik **"Tambah Baru"** untuk menyiarkan informasi promosi atau pengumuman penting di beranda utama website pelanggan.
3. Isi judul promosi, deskripsi singkat, dan unggah gambar spanduk (banner).
4. Tentukan masa aktif penayangan spanduk, kemudian klik **"Publish"**.

### 4.6 Monitoring Live Status Sewa Unit (Radar & Monitoring)
1. Buka menu **"Monitoring"** (`/admin/monitoring`) atau **"Radar"** (`/admin/radar`).
2. Halaman ini menyediakan representasi visual status penggunaan unit saat ini.
3. Unit yang sedang digunakan akan bertanda merah/terkunci beserta detail penyewanya, sedangkan unit siap sewa berwarna hijau.
4. Anda dapat mengawasi durasi sewa yang berjalan secara real-time guna mengantisipasi keterlambatan serah terima ruangan berikutnya.

### 4.7 Manajemen Transaksi & Alur Status Pembayaran
1. Akses menu **"Transaksi & Reservasi"** (`/admin/transactions`).
2. Di sini tercatat seluruh log sewa dari status *Pending*, *Paid*, *Renting*, *Completed*, hingga *Overdue/Late*.
3. **Konfirmasi Manual**: Jika pelanggan membayar dengan metode manual, Admin dapat memverifikasi bukti transfer dan mengeklik **"Konfirmasi Pembayaran"** untuk merubah status menjadi *Paid*.
4. **Penyerahan Kunci/Ruang**: Saat pelanggan datang di hari H, klik **"Set Handover"** untuk mengubah status transaksi menjadi *Renting* (waktu sewa resmi berjalan).
5. **Proses Pengembalian (Check-out)**: Ketika waktu sewa habis dan ruangan diserahkan kembali, klik **"Set Complete"**.
   * Jika ada keterlambatan, sistem secara otomatis mengkalkulasikan denda waktu berdasarkan aturan unit.
   * Jika ada kerusakan fisik pada fasilitas, masukkan nilai denda kerusakan di kolom **"Denda Kerusakan"** dan tambahkan deskripsi kejadian di bagian catatan kerusakan.

### 4.8 Proses Validasi Instan dengan Quick Scan (QR Code)
1. Pelanggan yang telah melunasi sewa akan memiliki QR Code di halaman invoice mereka.
2. Buka menu **"Quick Scan"** (`/admin/scan`) di perangkat tablet atau smartphone admin (atau menggunakan kamera webcam laptop).
3. Arahkan kamera ke QR Code milik pelanggan.
4. Sistem akan secara instan mengenali transaksi tersebut, memvalidasi identitas penyewa, dan menampilkan tombol cepat untuk melakukan proses *Check-in / Handover* maupun *Check-out / Complete* tanpa perlu mencari data transaksi secara manual di tabel.

### 4.9 Manajemen Pelanggan & Loyalty Tier
1. Masuk ke menu **"Pelanggan"** (`/admin/customers`).
2. Di halaman ini Anda dapat memantau seluruh profil pengguna yang terdaftar sebagai member.
3. Anda dapat melihat riwayat transaksi masing-masing pelanggan, memberikan catatan khusus, memblokir akun yang melanggar aturan sewa, atau meningkatkan tier loyalitas mereka secara manual untuk mendapatkan prioritas promo.

### 4.10 Manajemen Afiliasi (Validasi Payout & Afiliator)
1. Masuk ke menu **"Manajemen Afiliasi"** (`/admin/affiliate`).
2. **Persetujuan Afiliator**: Tinjau pengajuan pendaftaran afiliasi baru pada tab *Registrasi Pending*. Klik **"Approve"** untuk mengaktifkan kemitraan mereka atau **"Reject"** jika data dirasa tidak valid.
3. **Persetujuan Payout**: Pada tab *Permintaan Payout*, Anda dapat meninjau permintaan pencairan saldo komisi afiliator.
   * Transfer nominal ke nomor rekening afiliator secara manual melalui e-banking Anda.
   * Kembali ke sistem dan klik **"Approve Payout"** untuk mencatat bahwa komisi telah berhasil dicairkan.

### 4.11 Pengaturan Sistem & Integrasi Gateway (Settings)
1. Pilih menu **"Pengaturan"** (`/admin/settings`).
2. Anda dapat mengubah data profil usaha: Nama Perusahaan, Alamat, Logo, Sosial Media, dan Informasi Rekening Bank Utama.
3. **Pengaturan Midtrans**: Isi konfigurasi Merchant ID, Client Key, dan Server Key sesuai kredensial dari dashboard akun Midtrans Anda agar fitur pembayaran otomatis berfungsi.
4. **Pengaturan Email (SMTP)**: Konfigurasikan SMTP Mailer (Host, Port, Username, Password, Sender Address) agar pengiriman email invoice otomatis, pengingat jadwal sewa (reminder), dan notifikasi jatuh tempo (overdue) dapat terkirim lancar ke email pelanggan.

### 4.12 Monitoring Riwayat Aktivitas Karyawan (Staff Logs)
1. Buka menu **"Staff Logs"** (`/admin/stafflogs`).
2. Halaman ini mencatat kronologis aktivitas seluruh administrator dan staf: mencakup waktu login, penambahan unit baru, penghapusan transaksi, atau pengubahan konfigurasi sistem.
3. Ini merupakan instrumen audit penting untuk melacak pihak yang bertanggung jawab atas setiap aksi modifikasi data penting di dalam platform.

### 4.13 Mengelola Penilaian & Ulasan (Rating Manager)
1. Akses menu **"Rating Manager"** (`/admin/ratings`).
2. Di sini, Anda dapat meninjau feedback tertulis dan skor bintang dari pelanggan.
3. Anda dapat memilih untuk menampilkan ulasan tersebut di halaman depan website (Approved) agar dibaca oleh calon konsumen baru, menyembunyikannya, atau merespons keluhan secara internal.

### 4.14 Mengunduh & Mencetak Laporan Keuangan (Reports)
Sistem menyediakan dokumen PDF siap cetak untuk laporan pembukuan usaha:
1. **Laporan Bulanan**: Buka tautan `/admin/report/monthly?month=MM&year=YYYY` untuk menghasilkan lembaran rekapitulasi omset, jumlah transaksi sewa, total denda, dan jumlah komisi afiliasi yang terbayar di bulan tersebut.
2. **Laporan Tahunan**: Buka tautan `/admin/report/yearly?year=YYYY` untuk mencetak laporan performa bisnis tahunan.
3. Gunakan pintasan tombol Print di browser Anda atau simpan langsung ke berkas file PDF.

---

## BAB V: PANDUAN PEMECAHAN MASALAH (TROUBLESHOOTING)

* **Email Notifikasi Tidak Terkirim ke Pelanggan**:
  * Periksa kembali isian SMTP Mailer di menu **Settings**. Pastikan port dan protokol enkripsi (SSL/TLS) sudah benar.
  * Uji pengiriman email menggunakan rute preview `/admin/email-preview/invoice` untuk memeriksa error detail.
* **Pembayaran Midtrans Tidak Terverifikasi Otomatis**:
  * Pastikan URL Webhook di akun dashboard Midtrans Anda telah diarahkan dengan benar ke: `https://domain-anda.com/official-midtrans-callback`.
  * Pastikan status Server Key di file konfigurasi `.env` telah sesuai dengan mode Midtrans yang sedang berjalan (Sandbox / Production).
* **Ingin Membersihkan Cache Web Server secara Cepat**:
  * Administrator dapat mengunjungi URL `domain/clear-cache` untuk membersihkan penumpukan cache sistem tanpa perlu masuk ke terminal SSH server hosting.
