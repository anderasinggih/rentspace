<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    /**
     * Generate jawaban AI untuk customer chat WhatsApp
     */
    /**
     * Generate jawaban AI untuk customer chat WhatsApp dengan konteks percakapan & database monitoring
     */
    public static function reply(string $userMessage, string $customerName = 'Kak', ?string $senderJid = null, ?string $customerPhone = null): ?string
    {
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key'));

        if (!$apiKey) {
            Log::info('GeminiAIService: API Key belum diisi di Pengaturan.');
            return null;
        }

        $model = Setting::getVal('chatbot_model', 'gemini-3.5-flash-lite');
        // Jika model masih berisi model lama yang sudah deprecated, sesuaikan ke gemini-3.5-flash-lite
        if (in_array($model, ['gemini-2.0-flash-lite', 'gemini-1.5-flash-8b', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            $model = 'gemini-3.5-flash-lite';
        }

        // 1. Ambil session percakapan sebelumnya untuk user ini (Conversation Memory)
        $sessionKey = 'wa_chat_history_' . md5($senderJid ?: ($customerPhone ?: $customerName));
        $history = \Illuminate\Support\Facades\Cache::get($sessionKey, []);

        // 2. Data Master Unit
        $units = Unit::where('is_active', true)->with('category')->get();
        $unitListText = "";
        foreach ($units as $u) {
            $cat = $u->category ? $u->category->name : 'Unit';
            $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24 jam' : '-';
            $p12 = $u->harga_per_jam ? 'Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : '-';
            $unitListText .= "- [ID: {$u->id}] {$u->nama_lengkap} ({$cat}, {$p24} / {$p12})\n";
        }

        // 3. Konteks Monitoring Rental Saat Ini & Jadwal Terisi (Hingga 7 Hari ke Depan)
        // Ambil rental aktif (renting/paid) mulai dari hari ini
        $now = now();
        $nextWeek = now()->addDays(7);
        $currentTimeStr = $now->translatedFormat('l, d F Y H:i') . ' WIB';
        $activeRentals = \App\Models\Rental::with('units')
            ->whereIn('status', ['paid', 'renting', 'pending_confirmation'])
            ->where('waktu_selesai', '>=', $now)
            ->where('waktu_mulai', '<=', $nextWeek)
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        $scheduleText = "";
        if ($activeRentals->isNotEmpty()) {
            foreach ($activeRentals as $r) {
                $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
                $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M H:i') : '-';
                $endStr = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M H:i') : '-';
                $statusIndo = $r->status === 'renting' ? 'Sedang Dipakai' : 'Sudah Dibooking';
                $scheduleText .= "- {$uNames} ({$statusIndo} dari {$startStr} s/d {$endStr} WIB)\n";
            }
        } else {
            $scheduleText = "Semua unit saat ini belum ada booking terjadwal (bebas disewa).\n";
        }

        $address = Setting::getVal('admin_address', 'Purwokerto');
        $adminWa = Setting::getVal('admin_wa', '0881082411878');

        // 4. Ambil Memori / Aturan Khusus Tambahan dari Database (chatbot_custom_knowledge)
        $rawMemories = Setting::getVal('chatbot_custom_knowledge', '[]');
        $customMemories = json_decode($rawMemories, true) ?: [];
        $memoryText = "";
        if (!empty($customMemories)) {
            $memoryText .= "\nATURAN KHUSUS & KNOWLEDGE TAMBAHAN TOKO (PENTING):\n";
            foreach ($customMemories as $index => $mem) {
                $k = is_array($mem) ? ($mem['key'] ?? '') : '';
                $v = is_array($mem) ? ($mem['value'] ?? '') : (string) $mem;
                if ($k && $v) {
                    $memoryText .= "- {$k}: {$v}\n";
                } elseif ($v) {
                    $memoryText .= "- {$v}\n";
                }
            }
        }

        // 5. Promo & Diskon Aktif dari Database
        $promoText = "";
        try {
            $activePromos = \App\Models\PricingRule::where('is_active', true)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $now->toDateString());
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $now->toDateString());
                })
                ->whereNull('affiliate_code') // promo publik saja, bukan kode affiliasi
                ->get();

            if ($activePromos->isNotEmpty()) {
                $promoText = "\nPROMO & DISKON AKTIF SAAT INI (dari database):\n";
                foreach ($activePromos as $p) {
                    $tipeLabel = match($p->tipe) {
                        'diskon_persen' => "Diskon " . (int)$p->value . "%",
                        'hari_gratis'   => (int)$p->value . " Hari Gratis",
                        'jam_gratis'    => (int)$p->value . " Jam Gratis",
                        'fix_price'     => "Harga Spesial Rp " . number_format($p->value, 0, ',', '.'),
                        default         => $p->tipe . " (" . $p->value . ")",
                    };
                    $syarat = $p->syarat_minimal_durasi ? "minimal sewa {$p->syarat_minimal_durasi} {$p->syarat_tipe_durasi}" : "tanpa syarat durasi";
                    $validStr = ($p->start_date && $p->end_date)
                        ? " (berlaku " . \Carbon\Carbon::parse($p->start_date)->translatedFormat('d M') . " s/d " . \Carbon\Carbon::parse($p->end_date)->translatedFormat('d M Y') . ")"
                        : "";
                    $codeStr = !empty($p->code) ? " | Kode: {$p->code}" : "";
                    $promoText .= "- {$p->nama_promo}: {$tipeLabel}, {$syarat}{$validStr}{$codeStr}\n";
                }
            } else {
                $promoText = "\nPROMO AKTIF: Tidak ada promo khusus yang berjalan saat ini.\n";
            }
        } catch (\Throwable $e) {
            $promoText = "";
        }

        // 6. Announcement aktif
        $announcementText = "";
        try {
            $announcements = \App\Models\Announcement::active()->get();
            if ($announcements->isNotEmpty()) {
                $announcementText = "\nPENGUMUMAN / INFO TERKINI DARI TOKO:\n";
                foreach ($announcements as $ann) {
                    $announcementText .= "- [{$ann->type}] {$ann->title}: {$ann->message}\n";
                }
            }
        } catch (\Throwable $e) {
            $announcementText = "";
        }


        $systemPrompt = "Kamu adalah Customer Service WhatsApp di 'Rent Space Purwokerto' (rental iPhone, gadget, kamera di Purwokerto).
Waktu saat ini: {$currentTimeStr}.
Customer yang sedang chat bernama: {$customerName}.

INFORMASI RENT SPACE:
- Lokasi Toko: {$address}
- WhatsApp Admin: {$adminWa}
- Website Booking Online: https://rentspacepurwokerto.my.id/booking

CARA PESAN / BOOKING (berikan panduan ini jika customer tanya cara order/pesan/beli):
1. Buka website: https://rentspacepurwokerto.my.id/booking
2. Pilih tanggal mulai dan selesai sewa
3. Pilih unit iPhone / gadget yang tersedia
4. Isi data diri: Nama, NIK, No. WA, Alamat
5. Pilih metode pembayaran (QRIS, Transfer Bank, atau Cash)
6. Klik Pesan Sekarang → muncul halaman invoice
7. Bayar sesuai instruksi → upload bukti transfer jika perlu
8. Admin konfirmasi → unit siap diambil di toko
(Kode promo bisa diinput di halaman booking jika ada)

DAFTAR UNIT TOKO:
{$unitListText}
STATUS JADWAL UNIT YANG SEDANG DISEWA / SUDAH DIBOOKING (REAL-TIME):
{$scheduleText}{$promoText}{$announcementText}{$memoryText}
PANDUAN MENJAWAB (SANGAT PENTING):
1. GAYA BAHASA CS MANUSIA ASLI:
   - Jawab santai, ramah, to the point layaknya admin toko WA asli. Panggil 'Kak {$customerName}' atau 'Kak'.
   - JANGAN LEBAY! Maksimal 1 emoji saja, JANGAN tabur banyak emoticon (hindari 😊✨🙏 sekaligus).
   - JANGAN PERNAH menyertakan kalimat penutup template seperti 'Jika ada yang ditanyakan lagi hubungi admin...' atau 'Ada yang bisa dibantu lagi?'. Cukup jawab pertanyaannya secara solutif.
2. INGAT PERCAKAPAN SEBELUMNYA:
   - Jika customer bertanya pertanyaan lanjutan seperti 'jam berapa?', 'kapan?', 'caranya?', LIHAT riwayat percakapan sebelumnya. Pahami konteks yang sedang dibicarakan!
3. KETEPATAN JADWAL & MONITORING:
   - Periksa STATUS JADWAL UNIT. Jika ada booking di tanggal/jam yang ditanyakan, infokan jujur kapan unit baru bebas.
   - Jika kosong, katakan ready dan arahkan ke https://rentspacepurwokerto.my.id/booking.
4. PROMO & DISKON:
   - Jika ada promo aktif (lihat data promo di atas), sebutkan jika customer tanya soal harga, promo, atau diskon.
   - Jika ada kode promo, info cara pakai: masukkan kode promo di halaman booking website.
5. PANDUAN CARA PESAN:
   - Jika customer bingung cara order, berikan ringkasan 8 langkah cara booking di atas.
6. FALLBACK KE ADMIN:
   - Jika kamu tidak tahu atau tidak yakin dengan jawabannya (negosiasi harga, masalah teknis, pertanyaan yang tidak ada di data), sarankan customer balas 'ADMIN'.
   - Contoh: 'Untuk ini lebih baik langsung ke admin ya Kak, balas ADMIN biar aku sampaikan.'
7. Jawab singkat (2-4 kalimat), kecuali panduan cara pesan yang perlu langkah-langkah.";

        try {
            // Bangun percakapan multi-turn dengan riwayat sebelumnya
            $contents = [];
            // Masukkan pesan sistem dan riwayat (maksimal 6 percakapan terakhir)
            $recentHistory = array_slice($history, -6);

            // Turn pertama diawali prompt sistem
            $firstMessageText = $systemPrompt;
            if (!empty($recentHistory)) {
                $firstTurn = true;
                foreach ($recentHistory as $turn) {
                    if ($firstTurn) {
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $systemPrompt . "\n\nPesan Customer: \"" . $turn['user'] . "\""]
                            ]
                        ];
                        $contents[] = [
                            'role' => 'model',
                            'parts' => [
                                ['text' => $turn['model']]
                            ]
                        ];
                        $firstTurn = false;
                    } else {
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $turn['user']]
                            ]
                        ];
                        $contents[] = [
                            'role' => 'model',
                            'parts' => [
                                ['text' => $turn['model']]
                            ]
                        ];
                    }
                }
                // Tambahkan pertanyaan saat ini
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ];
            } else {
                // Percakapan baru
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nPesan Customer: \"" . $userMessage . "\"\n\nJawaban CS:"]
                    ]
                ];
            }

            $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.6,
                    'maxOutputTokens' => 200,
                ]
            ]);

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    $text = trim($candidates[0]['content']['parts'][0]['text']);
                    $text = preg_replace('/^#+\s*/m', '', $text);

                    // Simpan ke riwayat session (berlaku 2 jam)
                    $history[] = [
                        'user' => $userMessage,
                        'model' => $text,
                        'time' => now()->toDateTimeString()
                    ];
                    // Simpan maksimal 10 riwayat
                    if (count($history) > 10) {
                        $history = array_slice($history, -10);
                    }
                    \Illuminate\Support\Facades\Cache::put($sessionKey, $history, 7200);

                    return self::formatForWhatsApp($text);
                }
            } else {
                Log::warning('GeminiAIService Error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('GeminiAIService Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate jawaban AI untuk grup internal tim (akses penuh database)
     * Dipanggil HANYA ketika bot di-tag (@mention) di grup report terdaftar
     */
    public static function replyInternal(string $userMessage, string $askerName = 'Tim'): ?string
    {
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key'));
        if (!$apiKey) return null;

        $model = Setting::getVal('chatbot_model', 'gemini-3.5-flash-lite');
        if (in_array($model, ['gemini-2.0-flash-lite', 'gemini-1.5-flash-8b', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            $model = 'gemini-3.5-flash-lite';
        }

        $now = \Carbon\Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        // --- DATA RENTAL AKTIF (sedang disewa / sudah booking) ---
        $activeRentals = \App\Models\Rental::with(['units'])
            ->whereIn('status', ['renting', 'paid', 'pending_confirmation'])
            ->where('waktu_selesai', '>=', $now)
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        $rentingText = "";
        $lateText = "";
        $returnTodayText = "";
        $pickupTodayText = "";

        foreach ($activeRentals as $r) {
            $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
            $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M H:i') : '-';
            $endStr   = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M H:i') : '-';
            $total    = 'Rp ' . number_format($r->grand_total ?: $r->subtotal_harga, 0, ',', '.');
            $phone    = $r->no_wa ?: '-';
            $alamat   = $r->alamat ?: '-';
            $nama     = $r->nama ?: 'Pelanggan';
            $kode     = $r->booking_code ?: '-';

            $line = "• {$uNames} | Penyewa: {$nama} | WA: {$phone} | Alamat: {$alamat} | Kode: {$kode} | Mulai: {$startStr} | Selesai: {$endStr} | Total: {$total}\n";

            if ($r->status === 'renting') {
                $rentingText .= $line;

                // Cek telat: sudah melewati waktu selesai
                if ($r->waktu_selesai && \Carbon\Carbon::parse($r->waktu_selesai)->isPast()) {
                    $mnt = $now->diffInMinutes(\Carbon\Carbon::parse($r->waktu_selesai));
                    $lateText .= "• {$uNames} | {$nama} | WA: {$phone} | Terlambat {$mnt} menit (Selesai: {$endStr})\n";
                }

                // Kembali hari ini
                if ($r->waktu_selesai && \Carbon\Carbon::parse($r->waktu_selesai)->betweenIncluded($todayStart, $todayEnd)) {
                    $returnTodayText .= $line;
                }
            }

            // Ambil (pickup) hari ini
            if ($r->waktu_mulai && \Carbon\Carbon::parse($r->waktu_mulai)->betweenIncluded($todayStart, $todayEnd)) {
                $pickupTodayText .= $line;
            }
        }

        if (empty($rentingText)) $rentingText = "Tidak ada unit yang sedang disewa saat ini.\n";
        if (empty($lateText)) $lateText = "Tidak ada penyewa yang terlambat.\n";
        if (empty($returnTodayText)) $returnTodayText = "Tidak ada pengembalian yang dijadwalkan hari ini.\n";
        if (empty($pickupTodayText)) $pickupTodayText = "Tidak ada pengambilan yang dijadwalkan hari ini.\n";

        // --- BOOKING MENUNGGU (status pending: sudah bayar, belum ambil) ---
        $pendingText = "";
        $pendingBookings = \App\Models\Rental::with(['units'])
            ->where('status', 'pending')
            ->orderBy('waktu_mulai', 'asc')
            ->limit(40)
            ->get();
        foreach ($pendingBookings as $r) {
            $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
            $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
            $endStr   = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
            $pendingText .= "• {$uNames} | Penyewa: " . ($r->nama ?: '-') . " | WA: " . ($r->no_wa ?: '-')
                . " | Ambil: {$startStr} | Selesai: {$endStr} | Kode: " . ($r->booking_code ?: '-') . "\n";
        }
        if (empty($pendingText)) $pendingText = "Tidak ada booking yang menunggu pengambilan.\n";

        // --- RIWAYAT: PERNAH KENA DENDA ---
        $fineText = "";
        $finedRentals = \App\Models\Rental::with(['units'])
            ->where('denda', '>', 0)
            ->orderByDesc('denda')
            ->limit(40)
            ->get();
        foreach ($finedRentals as $r) {
            $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
            $fineText .= "• " . ($r->nama ?: '-') . " | Unit: {$uNames} | Denda: Rp "
                . number_format($r->denda, 0, ',', '.')
                . " | Alasan: " . ($r->catatan_kerusakan ?: '-')
                . " | Kode: " . ($r->booking_code ?: '-') . "\n";
        }
        if (empty($fineText)) $fineText = "Belum ada penyewa yang pernah dikenakan denda.\n";

        // --- RIWAYAT: PERNAH TERLAMBAT MENGEMBALIKAN ---
        $lateHistoryText = "";
        $lateHistory = \App\Models\Rental::with(['units'])
            ->whereNotNull('handed_over_at')
            ->whereNotNull('waktu_selesai')
            ->whereColumn('handed_over_at', '>', 'waktu_selesai')
            ->orderByDesc('handed_over_at')
            ->limit(40)
            ->get();
        foreach ($lateHistory as $r) {
            $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
            $telat = \Carbon\Carbon::parse($r->waktu_selesai)->diffInMinutes(\Carbon\Carbon::parse($r->handed_over_at));
            $lateHistoryText .= "• " . ($r->nama ?: '-') . " | Unit: {$uNames} | Telat: {$telat} menit"
                . " | Jadwal: " . \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M Y H:i')
                . " | Aktual: " . \Carbon\Carbon::parse($r->handed_over_at)->translatedFormat('d M Y H:i')
                . " | Kode: " . ($r->booking_code ?: '-') . "\n";
        }
        if (empty($lateHistoryText)) $lateHistoryText = "Belum ada riwayat penyewa yang terlambat mengembalikan unit.\n";

        // --- PENCARIAN DATA PENYEWA BERDASARKAN NAMA DI PERTANYAAN ---
        $lookupText = self::lookupRentalsByName($userMessage);

        // --- PROFIT / PENDAPATAN ---
        // Hari ini
        $profitToday = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->whereBetween('waktu_mulai', [$todayStart, $todayEnd])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));

        // Bulan ini
        $profitMonth = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->whereYear('waktu_mulai', $now->year)
            ->whereMonth('waktu_mulai', $now->month)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));

        // Total semua waktu
        $profitAllTime = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));

        $profitText = "- Hari ini: Rp " . number_format($profitToday, 0, ',', '.') . "\n";
        $profitText .= "- Bulan ini (" . $now->translatedFormat('F Y') . "): Rp " . number_format($profitMonth, 0, ',', '.') . "\n";
        $profitText .= "- Total keseluruhan: Rp " . number_format($profitAllTime, 0, ',', '.') . "\n";

        // --- SEMUA UNIT ---
        $units = \App\Models\Unit::where('is_active', true)->with('category')->get();
        $unitListText = "";
        foreach ($units as $u) {
            $cat = $u->category ? $u->category->name : 'Unit';
            $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24jam' : '-';
            $unitListText .= "- [ID:{$u->id}] {$u->nama_lengkap} ({$cat}, {$p24})\n";
        }

        $currentTimeStr = $now->translatedFormat('l, d F Y H:i') . ' WIB';
        $address = Setting::getVal('admin_address', 'Purwokerto');

        $systemPrompt = "Kamu adalah asisten internal tim *Rent Space Purwokerto* yang super pintar dan memiliki AKSES PENUH ke semua data bisnis.
Waktu saat ini: {$currentTimeStr}.
Penanya dari dalam tim: {$askerName}.
Lokasi Toko: {$address}.

ATURAN PENTING (WAJIB DIPAATUHI):
1. DATA DI BAWAH INI ADALAH KEBENARAN. Jawab HANYA dari data tersebut. Jangan mengarang nama, nomor, atau angka.
2. Kalau ada pertanyaan soal SEORANG PENYEWA, cek dulu bagian \"PENCARIAN DATA PENYEWA\" dan \"SELURUH TRANSAKSI PENYEWA\". Data di situ lebih lengkap daripada ringkasan lain.
3. JANGAN pernah menjawab \"tidak ada data\" sebelum sections yang relevan benar-benar dicek. Banyak transaksi berstatus pending / completed / cancelled yang TIDAK muncul di ringkasan hari ini, tapi tetap ada di riwayat.
4. Kalau ditanya \"hari ini\" atau \"minggu ini\", pakai bagian JADWAL PENGAMBILAN/PENGEMBALIAN HARI INI. Jangan menebak.
5. Bahasa gaul dan singkatan tim (mis. \"cuk\" = customer, \"yg\" = yang, \"trs/trus\" = terus, \"ngambil\" = mengambil, \"telat\" = terlambat, \"denda\", \"omset\", \"cod\") harus dipahami sebagai pertanyaan bisnis sungguhan, lalu dijawab dengan data.
6. Kalau memang tidak ada yang cocok, sebutkan apa yang ADA yang mendekati (mis. \"yang paling mendekati: ...\"), jangan langsung menyerah.
7. Jawab langsung to the point seperti laporan internal. Boleh tampilkan nama, nomor WA, alamat karena ini internal.
8. Format WA: pakai *tebal* (satu bintang) untuk judul, dan bullet -. Jangan pakai markdown lain.

DATA UNIT TOKO:
{$unitListText}

SELURUH TRANSAKSI PENYEWA (WAJIB DIBACA untuk pertanyaan soal orang tertentu):
{$lookupText}

UNIT YANG SEDANG DISEWA / AKTIF SAAT INI:
{$rentingText}

BOOKING MENUNGGU PENGAMBILAN (status pending, sudah bayar belum ambil):
{$pendingText}

JADWAL PENGAMBILAN HARI INI:
{$pickupTodayText}

JADWAL PENGEMBALIAN HARI INI:
{$returnTodayText}

PENYEWA TERLAMBAT MENGEMBALIKAN (sedang berjalan):
{$lateText}

RIWAYAT PERNAH KENA DENDA:
{$fineText}

RIWAYAT PERNAH TERLAMBAT MENGEMBALIKAN (selesai):
{$lateHistoryText}

DATA PENDAPATAN / PROFIT:
{$profitText}

Pertanyaan tim: \"{$userMessage}\"
Jawab sebagai asisten data internal:";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(45)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $systemPrompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 900,
                    ]
                ]
            );

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    $text = trim($candidates[0]['content']['parts'][0]['text']);
                    $text = preg_replace('/^#+\s*/m', '', $text);
                    return self::formatForWhatsApp($text);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('GeminiAIService::replyInternal Error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GeminiAIService::replyInternal Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Kata umum/singkatan tim yang TIDAK boleh dipakai sebagai kata kunci nama.
     */
    private const NAME_STOPWORDS = [
        'yang', 'atas', 'nama', 'itu', 'ini', 'kapan', 'trus', 'trs', 'yg', 'ada', 'cuk', 'sih', 'dong',
        'kok', 'oke', 'saya', 'kita', 'tadi', 'kemarin', 'lusa', 'bulan', 'minggu', 'tahun', 'omset',
        'rupiah', 'unit', 'sewa', 'sewaan', 'penyewa', 'untuk', 'utk', 'dari', 'pada', 'dengan',
        'dalam', 'sudah', 'belum', 'tidak', 'gak', 'nggak', 'belom', 'tolong', 'please', 'sekarang',
        'lagi', 'masih', 'juga', 'aja', 'doang', 'gitu', 'gini', 'sip', 'siap', 'makasih', 'terima',
        'kasih', 'nih', 'tuh', 'hari', 'kemana', 'siapa', 'berapa', 'berapa', 'berapa', 'mau',
        'ambil', 'ngambil', 'mengambil', 'kembalikan', 'balikin', 'telat', 'terlambat', 'denda',
        'lapor', 'laporan', 'report', 'data', 'cek', 'lihat', 'tampilkan', 'info', 'keterangan',
        'yang', 'orang', 'customer', 'pelanggan', 'transaksi', 'rental', 'sewa', 'status', 'kode',
    ];

    /**
     * Cari transaksi berdasarkan kata kunci nama yang muncul di pertanyaan tim.
     *
     * Tujuannya agar pertanyaan seperti "adi haryanto ambil kapan" dijawab dari
     * data nyata, bukan ditebak AI. Hanya rental yang namanya mengandung kata
     * kunci tersebut yang ditampilkan.
     */
    private static function lookupRentalsByName(string $userMessage): string
    {
        try {
            $normalized = mb_strtolower(self::stripInvisible($userMessage));
            $tokens = preg_split('/[^a-z0-9]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            // Kumpulkan kata kunci: panjang >= 4 dan bukan kata umum.
            $keywords = [];
            foreach ($tokens as $t) {
                if (mb_strlen($t) < 4) continue;
                if (in_array($t, self::NAME_STOPWORDS, true)) continue;
                $keywords[$t] = mb_strlen($t);
            }
            if (empty($keywords)) {
                return "Tidak ada kata kunci nama yang terdeteksi di pertanyaan ini.\n";
            }

            // Urutkan dari kata kunci terpanjang (lebih spesifik).
            arsort($keywords);
            $keywords = array_slice($keywords, 0, 4, true);

            $scored = [];
            foreach (array_keys($keywords) as $kw) {
                $hits = \App\Models\Rental::with(['units'])
                    ->whereRaw('LOWER(nama) LIKE ?', ['%' . $kw . '%'])
                    ->orderByDesc('waktu_mulai')
                    ->limit(25)
                    ->get();
                foreach ($hits as $r) {
                    $score = mb_strlen($kw);
                    $key = $r->id;
                    if (!isset($scored[$key]) || $scored[$key]['score'] < $score) {
                        $scored[$key] = ['score' => $score, 'rental' => $r];
                    }
                }
            }

            if (empty($scored)) {
                return "Tidak ada transaksi yang cocok dengan nama tersebut.\n";
            }

            // Urutkan: skor nama lebih tinggi dulu, lalu transaksi terbaru.
            usort($scored, function ($a, $b) {
                if ($a['score'] !== $b['score']) return $b['score'] <=> $a['score'];
                return $b['rental']->waktu_mulai <=> $a['rental']->waktu_mulai;
            });

            $out = "Ditemukan " . count($scored) . " transaksi yang cocok:\n";
            $i = 0;
            foreach ($scored as $entry) {
                if ($i++ >= 12) {
                    $out .= "• ...dan " . (count($scored) - 12) . " transaksi lain.\n";
                    break;
                }
                $r = $entry['rental'];
                $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
                $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
                $endStr   = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
                $total = 'Rp ' . number_format($r->grand_total ?: $r->subtotal_harga, 0, ',', '.');
                $out .= "• " . ($r->nama ?: '-') . " | Status: " . $r->status . " | Unit: {$uNames}"
                    . " | Ambil: {$startStr} | Selesai: {$endStr}"
                    . " | WA: " . ($r->no_wa ?: '-') . " | Total: {$total}"
                    . " | Denda: Rp " . number_format($r->denda ?? 0, 0, ',', '.')
                    . " | Kode: " . ($r->booking_code ?: '-') . "\n";
            }
            return $out;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('lookupRentalsByName gagal: ' . $e->getMessage());
            return "Pencarian nama gagal dijalankan.\n";
        }
    }

    /**
     * Buang karakter tak terlihat (zero-width, word joiner, bidi, BOM) yang
     * kadang muncul dari output AI dan merusak rendering WhatsApp.
     */
    private static function stripInvisible(string $text): string
    {
        $clean = preg_replace(
            '/[\x{00AD}\x{180E}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}]/u',
            '',
            $text
        );
        return $clean ?? $text;
    }

    /**
     * Ubah output AI (markdown) menjadi format yang didukung WhatsApp.
     *
     * WhatsApp hanya memahami *tebal*, _miring_, ~coret~, bukan **tebal**
     * dan bukan backtick. Tanpa konversi ini bintang/backtick tampil mentah.
     */
    private static function formatForWhatsApp(string $text): string
    {
        $text = self::stripInvisible($text);

        // **tebal** -> *teal*
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text) ?? $text;
        // __miring__ -> _miring_
        $text = preg_replace('/__(.+?)__/s', '_$1_', $text) ?? $text;
        // `kode` -> ~kode~ (inline code WhatsApp pakai ~)
        $text = preg_replace('/`([^`\n]+)`/u', '~$1~', $text) ?? $text;
        // Sisa backtick liar dibuang
        $text = str_replace('`', '', $text);
        // Judul markdown # dibuang
        $text = preg_replace('/^\s*#{1,6}\s*/m', '', $text) ?? $text;

        // Rapikan spasi & baris kosong berlebih
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        // WhatsApp batasi 4096 karakter per pesan
        if (mb_strlen($text) > 4000) {
            $text = mb_substr($text, 0, 3900) . "\n\n_(Pesan dipotong karena terlalu panjang.)_";
        }

        return $text;
    }
}
