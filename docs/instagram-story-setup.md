# Panduan Instagram Story (Graph API)

Panel admin bisa merender dan menayangkan Story Instagram untuk unit rental.
Semua publish melewati **Instagram Graph API v21.0+**, dan hanya akun
tipe **Business** atau **Creator** yang bisa melakukannya.

---

## 1. Syarat akun

| Syarat | Kenapa |
| --- | --- |
| Akun IG tipe **Business** atau **Creator** | Akun personal tidak punya endpoint publish story sama sekali. |
| Akun tertaut ke sebuah **Facebook Page** | Graph API mengambil data lewat Page, bukan lewat akun IG langsung. |
| `APP_URL` = domain **HTTPS publik** | Instagram menarik `image_url` dari server Meta, bukan dari browser kita. |
| Permission `instagram_business_content_publish` | Permission-nya di-*attach* ke token, bukan ticked manual di dashboard. |

Kalau langkah di atas masih gagal dengan `media fetch error`, hampir selalu karena
`APP_URL` masih menunjuk ke `localhost` atau domain belum pakai HTTPS.

---

## 2. Mengambil IG User ID

IG User ID itu **angka panjang**, bukan username dan bukan ID Facebook Page.
Cara termudah dari browser:

1. Buka profil akun IG yang mau dipakai.
2. Buka tab **Settings → Dasar** di Meta Business Suite, atau:
3. Cek dari endpoint mana pun yang sudah diberi token:

```
curl -s "https://graph.facebook.com/v21.0/me?fields=id,username&access_token=TOKEN"
```

Untuk akun yang tertaut ke Page, cari juga lewat:

```
curl -s "https://graph.facebook.com/v21.0/PAGE_ID/connected_instagram_accounts?fields=id,username&access_token=TOKEN"
```

Masukkan angka hasilnya ke **Pengaturan → Instagram → IG User ID**.

---

## 3. Mengisi pengaturan

Buka **Admin → Pengaturan → tab Instagram** (ikon kamera ungu/pink).

| Field | Keterangan |
| --- | --- |
| `IG User ID` | Angka dari langkah 2. Kosongkan kalau mau pakai fallback dari `.env`. |
| `Access Token` | Disimpan di tabel `settings`, tidak pernah ditampilkan lagi setelah disimpan. Input kosong = pertahankan token lama. |
| `Versi Graph API` | Default `v21.0`. Meta mematikan versi lama secara berkala, jadi field ini sengaja bisa diubah tanpa deploy. |
| `Warna Tema` | Warna dasar kanvas story, format hex `#rrggbb`. |
| `Template Caption` | Teks yang ikut di luar gambar, maks 2.200 karakter. |
| `Template Teks di Gambar` | Teks yang ditulis di atas foto, maks 4 baris. |

Tekan **Simpan Pengaturan**, lalu **Tes Koneksi** untuk memastikan token dan
IG User ID benar-benar bisa dipakai. Tes koneksi menyimpan dulu nilai terbaru,
lalu memanggil `GET /{ig-user-id}?fields=id,username,name,followers_count`.

### Fallback dari `.env`

Kalau server tidak punya akses ke UI admin, isi di `.env`:

```dotenv
INSTAGRAM_ACCESS_TOKEN=EAAG...
INSTAGRAM_USER_ID=17841400000000000
INSTAGRAM_GRAPH_VERSION=v21.0
```

Tabel `settings` selalu menang kalau isinya tidak kosong.

---

## 4. Placeholder template

Placeholder yang bisa dipakai di **Caption** maupun **Teks di Gambar**:

| Placeholder | Isinya |
| --- | --- |
| `{nama}` | Seri unit, mis. `iPhone 13` |
| `{nama_lengkap}` | Nama lengkap unit (seri + atribut) |
| `{kategori}` | Nama kategori, mis. `iPhone` |
| `{spesifikasi}` | Warna + memori, mis. `Malam 256GB` |
| `{harga_hari}` | `Rp 90.000/hari`, kosong kalau harga 0 |
| `{harga_jam}` | `Rp 15.000/jam`, kosong kalau harga 0 |
| `{lokasi}` | Dari setting `admin_address` |
| `{link}` | `{APP_URL}/` |
| `{ig}` | `@username` dari setting `social_ig_name` |
| `{tanggal}` | Tanggal hari ini, format `d M Y` |

Placeholder yang **tidak dikenal dibiarkan apa adanya** supaya salah ketik
langsung kelihatan di preview, bukan hilang diam-diam.

---

## 5. Cara pakai

1. **Admin → Instagram Story**.
2. Pilih unit. Caption terisi otomatis dari template.
3. Tekan **Preview** untuk merender gambar 1080×1920 tanpa mengirim apa pun.
4. Tekan **Publish**.

Publish sengaja butuh klik manual: story yang sudah tayang **tidak bisa
dihapus lewat API**, jadi keputusan publish tetap di tangan admin.

### Ukuran gambar

| Aturan | Nilai |
| --- | --- |
| Kanvas | 1080 × 1920 (9:16) |
| Format | PNG (yang dihasilkan composer) |
| Batas | 8 MB |

Foto unit disimpan di `public/uploads/unit/` pada saat unit disimpan, lalu
dipotong rasio 9:16 dengan titik tengah dan diskalakan. Kalau file foto
rusak atau tidak ada, story tetap tayang dengan teks saja — upload foto tidak
menjadi syarat publish.

---

## 6. Cara kerja di belakang layar

Publish selalu dua langkah dan tidak boleh dilewati:

```
POST /v21.0/{ig-user-id}/media          -> dapat creation_id (masih DRAFT)
POST /v21.0/{ig-user-id}/media_publish  -> creation_id dipromosikan jadi story
```

Setiap langkah mengirim `access_token` di query string.

**Pengecekan URL dilakukan sebelum langkah pertama.** Host `localhost`,
`127.0.0.1`, `*.local`, `*.test`, dan URL tanpa HTTPS ditolak dengan pesan yang
spesifik. Ini penting karena Meta hanya membalas `media fetch error` yang
tidak menjelaskan apa pun, dan percobaan tetap menghabiskan kuota publish.

Kalau langkah 1 sukses tapi langkah 2 gagal, container itu akan kedaluwarsa
sendiri setelah 24 jam. Karena itu **setiap percobaan tetap dicatat** di tabel
`instagram_story_posts` dengan status `published` atau `failed`, lengkap dengan
`image_path`, `media_id`, dan pesan error — supaya ada yang menggantung tetap
bisa ditelusuri.

### Reach story

`refreshInsights()` mengambil `impressions` dan `reach`. Angka ini baru
tersedia sekitar 24 jam setelah story ditonton, jadi tidak bisa langsung diambil
saat publish. Kegagalan insights **tidak** menggagalkan publish — fungsi ini
mengembalikan array kosong kalau Meta menolak.

---

## 7. Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| `URL gambar masih menunjuk ke localhost` | Set `APP_URL` ke domain HTTPS publik. |
| `URL gambar harus memakai HTTPS` | Paket SSL belum aktif, atau `APP_URL` masih `http://`. |
| `media fetch error` | File tidak bisa diambil Meta. Cek URL story terbuka publik tanpa login, dan tidak di-backup `hotlink protection`/auth. |
| `Invalid OAuth access token` | Token kedaluwarsa atau dicabut. Buat token baru. |
| `Unsupported get request` | Akun bukan Business/Creator, atau belum tertaut ke Facebook Page. |
| `Permission ... _content_publish` | Permission tidak ter-*attach*. Cabut token lama, generate ulang dengan permission yang benar. |
| Story tidak muncul di akun | Keduanya berhasil (container + publish) tapi check Insights untuk memastikan `impressions` naik. |

---

## 8. Tests

```bash
./vendor/bin/phpunit --filter InstagramStoryTest
```

Tes memalsukan seluruh panggilan Graph API, jadi tidak menyentuh akun asli.
Cakupannya: renderer 1080×1920, substitusi placeholder, dua langkah publish,
urutan pemanggilan/token, penolakan URL non-publik, pencatatan kegagalan,
Tombol tes koneksi, dan alur upload/hapus foto unit.
