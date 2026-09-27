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

            $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
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

                    return $text;
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
            $total    = 'Rp ' . number_format($r->grand_total ?: $r->total_harga, 0, ',', '.');
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

        // --- PROFIT / PENDAPATAN ---
        // Hari ini
        $profitToday = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->whereBetween('waktu_mulai', [$todayStart, $todayEnd])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, total_harga)'));

        // Bulan ini
        $profitMonth = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->whereYear('waktu_mulai', $now->year)
            ->whereMonth('waktu_mulai', $now->month)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, total_harga)'));

        // Total semua waktu
        $profitAllTime = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, total_harga)'));

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

PANDUAN MENJAWAB:
- Jawab langsung to the point, seperti laporan internal. Jangan basa-basi berlebihan.
- Boleh tampilkan data detail (nama, nomor WA, alamat penyewa) karena ini percakapan internal tim.
- Gunakan format yang rapi dan mudah dibaca. Minimal emoji, maksimal informasi.
- Jawab singkat tapi lengkap.

DATA UNIT TOKO:
{$unitListText}

UNIT YANG SEDANG DISEWA / AKTIF SAAT INI:
{$rentingText}

JADWAL PENGAMBILAN HARI INI:
{$pickupTodayText}

JADWAL PENGEMBALIAN HARI INI:
{$returnTodayText}

PENYEWA TERLAMBAT MENGEMBALIKAN:
{$lateText}

DATA PENDAPATAN / PROFIT:
{$profitText}

Pertanyaan tim: \"{$userMessage}\"
Jawab sebagai asisten data internal:";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(12)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $systemPrompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 400,
                    ]
                ]
            );

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    $text = trim($candidates[0]['content']['parts'][0]['text']);
                    $text = preg_replace('/^#+\s*/m', '', $text);
                    return $text;
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('GeminiAIService::replyInternal Error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GeminiAIService::replyInternal Exception: ' . $e->getMessage());
        }

        return null;
    }
}
