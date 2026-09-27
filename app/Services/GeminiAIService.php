<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    /** Kunci pemicu -> konteks tambahan untuk chat customer. */
    private const CUSTOMER_SECTION_RULES = [
        'katalog' => ['harga', 'katalog', 'daftar', 'list', 'ipho', 'iphone', 'hp', 'kamera', 'unit', 'stok', 'ready', 'tersedia', 'ada tidak', 'modal', 'series', 'tipe', 'promo', 'diskon', 'murah'],
        'jadwal' => ['kosong', 'tersedia', 'bisa', 'bisa ga', 'bisa tidak', 'kapan', 'jadwal', 'tanggal', 'hari', 'besok', 'lusa', 'nanti', 'minggu', 'bulan', 'jam', 'pagi', 'sore', 'malam', 'sampai', 'selama'],
        'cara_pesan' => ['cara', 'gimana', 'bagaimana', 'order', 'pesan', 'booking', 'daftar', 'alur', 'langkah', 'bookingnya', 'cara pesan'],
        'pengumuman' => ['kabar', 'info', 'pengumuman', 'promo terbaru', 'berita', 'announcement'],
    ];

    /**
     * Generate jawaban AI untuk customer chat WhatsApp.
     *
     * Hemat token: riwayat percakapan diambil dari tabel (bukan cache 2 jam),
     * hanya 3 turn terakhir yang dikirim, dan data katalog/jadwal/promo hanya
     * dimuat kalau kata kuncinya relevan.
     */
    public static function reply(string $userMessage, string $customerName = 'Kak', ?string $senderJid = null, ?string $customerPhone = null): ?string
    {
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key'));
        if (!$apiKey) {
            Log::info('GeminiAIService: API Key belum diisi di Pengaturan.');
            return null;
        }

        $model = self::activeModel();

        $peer = $senderJid ?: ($customerPhone ?: $customerName);
        $conv = AiMemoryService::session('wa_customer', (string) $peer, $customerName);

        $q = mb_strtolower($userMessage);
        $sections = [];
        foreach (self::CUSTOMER_SECTION_RULES as $section => $triggers) {
            foreach ($triggers as $trigger) {
                if (str_contains($q, $trigger)) {
                    $sections[$section] = true;
                    break;
                }
            }
        }
        $isFollowUp = mb_strlen($userMessage) <= 30 && !empty(AiMemoryService::recentTurns($conv, 1, 100));

        $now = now();
        $currentTimeStr = $now->translatedFormat('l, d F Y H:i') . ' WIB';
        $address = Setting::getVal('admin_address', 'Purwokerto');
        $adminWa = Setting::getVal('admin_wa', '0881082411878');

        $dataBlock = self::buildCustomerData($sections, $isFollowUp, $now);

        $memoryContext = AiMemoryService::contextFor($conv, $userMessage, 260);
        $recentTurns = AiMemoryService::recentTurns($conv, 3, 520);

        $systemPrompt = "Kamu adalah Customer Service WhatsApp di 'Rent Space Purwokerto' (rental iPhone, gadget, kamera di Purwokerto).
Waktu saat ini: {$currentTimeStr}.
Customer yang sedang chat bernama: {$customerName}.
Lokasi Toko: {$address} | WhatsApp Admin: {$adminWa} | Booking: https://rentspacepurwokerto.my.id/booking

CARA PESAN (ringkas): buka https://rentspacepurwokerto.my.id/booking → pilih tanggal → pilih unit → isi data (nama, NIK, No. WA, alamat) → pilih pembayaran (QRIS / Transfer / Cash) → bayar & unggah bukti bila perlu → admin konfirmasi → unit siap diambil di toko. Kode promo bisa diinput di halaman booking.
{$dataBlock}
" . ($memoryContext !== '' ? "\nCATATAN PERCAKAPAN SEBELUMNYA:\n{$memoryContext}\n" : '')
. "PANDUAN MENJAWAB (SANGAT PENTING):
1. GAYA BAHASA CS MANUSIA ASLI: santai, ramah, to the point. Panggil 'Kak {$customerName}'. Maksimal 1 emoji, jangan tabur emoticon.
2. JANGAN pernah menutup dengan template 'Jika ada yang lain hubungi admin...' atau 'Ada yang bisa dibantu lagi?'. Cukup jawab solusinya.
3. INGAT PERCAKAPAN SEBELUMNYA: pertanyaan lanjutan ('jam berapa?', 'yang 128 gb?', 'harga sewanya?') harus dipahami dari riwayat obrolan. Jangan minta mengulang pertanyaan yang sudah jelas.
4. JADWAL REAL-TIME: pakai bagian STATUS JADWAL. Kalau unit yang ditanyakan sedang dibooking, infokan jujur kapan baru bebas. Kalau tidak ada, katakan ready dan arahkan ke halaman booking.
5. PROMO: sebutkan promo yang tertulis di data, jangan karang promo baru.
6. FALLBACK: kalau tidak tahu atau tidak yakin (negosiasi harga, masalah teknis, di luar data), sarankan customer balas 'ADMIN'.
7. Jawaban singkat 2-4 kalimat, kecuali panduan cara pesan.";

        $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        $budget = AiMemoryService::consumeTokenBudget('wa_customer', $inputTokens);
        if (! $budget['ok']) {
            Log::warning('GeminiAIService::reply dipangkas demi batas token/menit.', $budget);
            $systemPrompt = self::stripCustomerData($systemPrompt);
            $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        }

        $text = self::askGemini($systemPrompt, $model, $apiKey, 0.6, 200, 30);

        if ($text !== null) {
            AiMemoryService::saveTurn($conv, $userMessage, $text, self::customerIntent($sections), $inputTokens, $customerName);
            // Cache lama tetap diisi agar kompatibel dengan alur lama (rapid reply).
            $cacheKey = 'wa_chat_history_' . md5($peer);
            $legacy = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
            $legacy[] = ['user' => $userMessage, 'model' => $text, 'time' => now()->toDateTimeString()];
            \Illuminate\Support\Facades\Cache::put($cacheKey, array_slice($legacy, -10), 7200);
        }

        return $text;
    }

    private static function customerIntent(array $sections): string
    {
        return match (true) {
            isset($sections['cara_pesan']) => 'cara_pesan',
            isset($sections['jadwal']) => 'jadwal',
            isset($sections['katalog']) => 'katalog',
            isset($sections['pengumuman']) => 'umum',
            default => 'umum',
        };
    }

    /**
     * Blok data untuk customer: hanya bagian yang relevan dengan pertanyaannya.
     * Pertanyaan pendek yang merupakan lanjutan (mis. "yang 128 gb?") tidak perlu
     * katalog penuh karena jawabannya sudah ada di riwayat obrolan.
     */
    private static function buildCustomerData(array $sections, bool $isFollowUp, $now): string
    {
        $needKatalog = ($sections['katalog'] ?? false) || ($sections['cara_pesan'] ?? false);
        $needJadwal = $sections['jadwal'] ?? false;
        $needPromo = $sections['katalog'] ?? false;
        $needPengumuman = $sections['pengumuman'] ?? false;

        // Tanpa pemicu jelas dan pesan pendek: andalkan riwayat + memori saja.
        if ($isFollowUp && ! $needKatalog && ! $needJadwal) {
            return "PANDUAN JAWABAN: ini pertanyaan lanjutan, pakai konteks obrolan sebelumnya. Jangan mengulang katalog; jawab langsung dengan menyinggung obrolan tadi.";
        }

        $block = '';

        if ($needKatalog) {
            $units = self::cached('cust_unit', 120, function () {
                $text = '';
                foreach (Unit::where('is_active', true)->with('category')->get() as $u) {
                    $cat = $u->category?->name ?? 'Unit';
                    $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24 jam' : '-';
                    $p12 = $u->harga_per_jam ? 'Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : '-';
                    $text .= "- [ID: {$u->id}] {$u->nama_lengkap} ({$cat}, {$p24} / {$p12})\n";
                }
                return $text;
            });
            if ($units !== '') {
                $block .= "DAFTAR UNIT & HARGA:\n{$units}";
            }
        }

        if ($needJadwal) {
            $schedule = self::cached('cust_jadwal', 60, function () use ($now) {
                $rows = \App\Models\Rental::with('units')
                    ->whereIn('status', ['paid', 'renting', 'pending_confirmation'])
                    ->where('waktu_selesai', '>=', $now)
                    ->where('waktu_mulai', '<=', $now->copy()->addDays(14))
                    ->orderBy('waktu_mulai')
                    ->limit(25)->get();
                if ($rows->isEmpty()) {
                    return "Semua unit saat ini belum ada booking terjadwal (bebas disewa).\n";
                }
                $text = '';
                foreach ($rows as $r) {
                    $names = $r->units->map(fn ($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
                    $start = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M H:i') : '-';
                    $end = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M H:i') : '-';
                    $label = $r->status === 'renting' ? 'Sedang Dipakai' : 'Sudah Dibooking';
                    $text .= "- {$names} ({$label} {$start} s/d {$end})\n";
                }
                return $text;
            });
            $block .= "STATUS JADWAL UNIT (14 hari ke depan):\n{$schedule}";
        }

        if ($needPromo) {
            $promo = self::promoText($now);
            if ($promo !== '') {
                $block .= $promo;
            }
        }

        if ($needPengumuman) {
            $block .= self::announcementText();
        }

        // Aturan & pengetahuan tambahan toko: kecil, tapi wajib selalu ada.
        $block .= self::customKnowledgeText();

        return $block === '' ? "PANDUAN JAWABAN: jawab singkat dengan gaya CS yang natural." : $block;
    }

    private static function promoText($now): string
    {
        return self::cached('cust_promo', 300, function () use ($now) {
            try {
                $promos = \App\Models\PricingRule::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $now->toDateString()))
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now->toDateString()))
                    ->whereNull('affiliate_code')
                    ->get();
                if ($promos->isEmpty()) {
                    return '';
                }
                $text = "PROMO AKTIF:\n";
                foreach ($promos as $p) {
                    $tipe = match ($p->tipe) {
                        'diskon_persen' => 'Diskon ' . (int) $p->value . '%',
                        'hari_gratis' => $p->value . ' Hari Gratis',
                        'jam_gratis' => $p->value . ' Jam Gratis',
                        'fix_price' => 'Harga Spesial Rp ' . number_format($p->value, 0, ',', '.'),
                        default => $p->tipe . ' (' . $p->value . ')',
                    };
                    $syarat = $p->syarat_minimal_durasi ? "minimal sewa {$p->syarat_minimal_durasi} {$p->syarat_tipe_durasi}" : 'tanpa syarat durasi';
                    $kode = $p->code ? " | kode: {$p->code}" : '';
                    $text .= "- {$p->nama_promo}: {$tipe}, {$syarat}{$kode}\n";
                }
                return $text;
            } catch (\Throwable $e) {
                return '';
            }
        });
    }

    private static function announcementText(): string
    {
        return self::cached('cust_promo_ann', 300, function () {
            try {
                $anns = \App\Models\Announcement::active()->get();
                if ($anns->isEmpty()) {
                    return '';
                }
                $text = "PENGUMUMAN TERKINI:\n";
                foreach ($anns as $ann) {
                    $text .= "- {$ann->title}: {$ann->message}\n";
                }
                return $text;
            } catch (\Throwable $e) {
                return '';
            }
        });
    }

    private static function customKnowledgeText(): string
    {
        $raw = Setting::getVal('chatbot_custom_knowledge', '[]');
        $memories = json_decode($raw, true) ?: [];
        if (empty($memories)) {
            return '';
        }
        $text = "ATURAN KHUSUS TOKO (WAJIB DIPAATUHI):\n";
        foreach ($memories as $mem) {
            $k = is_array($mem) ? ($mem['key'] ?? '') : '';
            $v = is_array($mem) ? ($mem['value'] ?? '') : (string) $mem;
            $text .= $k && $v ? "- {$k}: {$v}\n" : ($v ? "- {$v}\n" : '');
        }
        return $text;
    }

    /**
     * Buang blok data berat dari prompt customer saat token/menit mepet.
     */
    private static function stripCustomerData(string $prompt): string
    {
        $pos = strpos($prompt, 'DAFTAR UNIT & HARGA');
        if ($pos === false) {
            $pos = strpos($prompt, 'PANDUAN JAWABAN: ini pertanyaan lanjutan');
        }
        if ($pos === false) {
            return $prompt;
        }
        $guidePos = strpos($prompt, 'PANDUAN MENJAWAB');
        $guide = $guidePos !== false ? substr($prompt, $guidePos) : '';

        return substr($prompt, 0, $pos) . "KONTEKS KATALOG/JADWAL SEDANG DIKURANGI (pemakaian token tinggi). Kalau pertanyaan butuh data unit/jadwal, jawab sebatas yang kamu tahu lalu arahkan ke https://rentspacepurwokerto.my.id/booking atau balas ADMIN.\n" . $guide;
    }

    /**
     * Generate jawaban AI untuk grup internal tim (akses penuh database).
     *
     * Dipanggil HANYA ketika bot di-tag (@mention) di grup report terdaftar.
     *
     * Dua hal yang dijaga di sini:
     * 1. MEMORI: percakapan grup disimpan di tabel ai_messages, jadi pertanyaan
     *    lanjutan ("yang tadi itu gimana?", "trus si Rina telat berapa?") tetap nyambung
     *    walau sudah lewat jam atau bot restart.
     * 2. HEMAT TOKEN: data yang dikirim hanya bagian yang relevan dengan pertanyaan
     *    (tidak lagi semua tabel rental setiap kali ditanya), memakai ringkasan
     *    memori + maksimal 3 turn terakhir, dan dipotong sesuai batas karakter.
     */
    public static function replyInternal(string $userMessage, string $askerName = 'Tim'): ?string
    {
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key'));
        if (!$apiKey) {
            self::$lastError = 'API key kosong';
            Log::warning('GeminiAIService::replyInternal: API key AI kosong.');
            return self::fallbackMessage();
        }

        $model = self::activeModel();

        // Sesi bersama untuk grup report: semua anggota tim memakai satu memori.
        $groupId = Setting::sanitizeJid(Setting::getVal('admin_report_group_id', ''));
        $conv = AiMemoryService::session('wa_group_report', $groupId !== '' ? $groupId : 'grup-report', $askerName);

        $intents = self::detectInternalIntents($userMessage);
        $intent = $intents['primary'];

        $now = \Carbon\Carbon::now();
        $currentTimeStr = $now->translatedFormat('l, d F Y H:i') . ' WIB';
        $address = Setting::getVal('admin_address', 'Purwokerto');

        $memoryContext = AiMemoryService::contextFor($conv, $userMessage);
        $recentTurns = AiMemoryService::recentTurns($conv, 3, 600);
        $chatContext = self::formatRecentTurns($recentTurns);

        [$dataBlock, $loadedLabels] = self::buildInternalData($userMessage, $intents, $now);

        $systemPrompt = "Kamu adalah asisten internal tim *Rent Space Purwokerto* yang|super pintar dan punya AKSES PENUH ke data bisnis.
Waktu saat ini: {$currentTimeStr}.
Penanya dari dalam tim: {$askerName}.
Lokasi Toko: {$address}.

ATURAN PENTING (WAJIB DIPAATUHI):
1. Data di bawah ini adalah KEBENARAN. Jawab HANYA dari data tersebut, jangan mengarang nama, nomor, atau angka.
2. MEMORI: bagian \"YANG SUDAH DIPBAHAS\" adalah catatan percakapan sebelumnya dengan tim (kode booking, nama penyewa, topik). Pakai itu untuk menjawab pertanyaan lanjutan yang singkat, misalnya \"yang tadi\", \"trus dia\", \"yang nomor tadi\". Kalau pertanyaannya menyebut kode atau nama yang ada di memori, jawab langsung dari memori itu tanpa meminta penjelasan ulang.
3. Data di bawah sudah DISARING sesuai pertanyaan. Bagian yang tidak dimuat berarti tidak relevan dengan pertanyaan ini. Kalau kamu butuh bagian yang tidak dimuat, JANGAN mengarang: katakan data itu tidak ikut dimuat dan sebut kata kuncinya (mis. \"denda\", \"riwayat\", \"nama penyewa\", \"jadwal hari ini\") supaya tim bisa bertanya lagi.
4. Kalau ditanya \"hari ini\" / \"siapa yang mau ambil\" / \"siapa yang balikin\", pakai bagian JADWAL PENGAMBILAN HARI INI dan JADWAL PENGEMBALIAN HARI INI (sudah mencakup SEMUA status: pending, sudah bayar, sedang disewa). Jangan menebak.
5. Kalau pertanyaan menyiratkan laporan harian, jawaban WAJIB memuat: jumlah penyewa yang ambil, jumlah yang balikin, dan status keterlambatan — lengkap dengan nama & jamnya.
6. Bahasa gaul tim (cuk, yg, trs/trus, ngambil, balikin, telat, denda, omset, cod, msh, blm) adalah pertanyaan bisnis sungguhan, jawab dengan data.
7. Kalau tidak ada yang cocok, sebutkan apa yang ADA yang mendekati (\"yang paling mendekati: ...\"), jangan langsung menyerah.
8. Boleh tampilkan nama, nomor WA, alamat karena ini internal.
9. Format WA: pakai *tebal* (satu bintang) dan bullet -. Jangan pakai markdown lain.
10. Kata \"TERLAMBAT\" atau \"SUDAH MELEBIHI JADWAL\" berarti masalah nyata — wajib CHA-ATUR di jawaban.
11. Jawaban internal to the point, tidak perlu basa-basi sapaan.

YANG SUDAH DIPBAHAS (MEMORI TIM):
" . ($memoryContext !== '' ? $memoryContext . "\n" : "(belum ada obrolan sebelumnya di grup ini)") . "
" . ($chatContext !== '' ? "\nOBROLAN TERAKHIR:\n" . $chatContext . "\n" : '') . "
DATA BISNIS (bagian relevan untuk pertanyaan ini: " . implode(', ', $loadedLabels) . "):
{$dataBlock}

Pertanyaan tim: \"{$userMessage}\"
Jawab sebagai asisten data internal:";

        $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        $budget = AiMemoryService::consumeTokenBudget('wa_group_report', $inputTokens);
        if (! $budget['ok']) {
            Log::warning('GeminiAIService::replyInternal dipangkas demi batas token/menit.', $budget);
            $systemPrompt = self::shrinkInternalPrompt($systemPrompt);
            $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        }

        $text = self::askGemini($systemPrompt, $model, $apiKey, 0.2, 900, 45);

        if ($text !== null) {
            AiMemoryService::saveTurn($conv, $userMessage, $text, $intent, $inputTokens, $askerName);
        }

        return $text ?? self::fallbackMessage();
    }

    /**
     * Kunci pemicu -> bagian data yang dimuat.
     *
     * Tujuannya satu: jangan pernah mengirim seluruh database ke model kalau
     * tim hanya menanyakan satu hal.
     */
    private const INTERNAL_SECTION_RULES = [
        'kembali' => ['balikin', 'kembali', 'kembalikan', 'pengembalian', 'pulang', 'balik', 'ngembal'],
        'terlambat' => ['telat', 'terlambat', 'lewat jadwal', 'mewati', 'overtime', 'nyusul', 'keterlambatan'],
        'denda' => ['denda', 'rusak', 'kerusakan', 'damage', 'fine', 'bayar denda'],
        'riwayat' => ['riwayat', 'pernah', 'dulu', 'kemarin', 'bulan lalu', 'tahun lalu', 'semua transaksi', 'historis'],
        'pending' => ['pending', 'belum bayar', 'blm bayar', 'gak bayar', 'nggak bayar', 'menunggu', 'nunggu', 'konfirmasi'],
        'aktif' => ['sedang disewa', 'lagi dipakai', 'sedang dipakai', 'aktif', 'sibuk', 'yang jalan'],
        'jadwal' => ['hari ini', 'hr ini', 'ambil', 'ngambil', 'pengambilan', 'jadwal', 'datang', 'mampir', 'besok'],
        'cari' => ['transaksi', 'rental', 'booking', 'penyewa', 'cari', 'siapa'],
        'unit' => ['unit', 'iphone', 'hp', 'kamera', 'harga', 'katalog', 'daftar', 'stok', 'ada unit', 'series'],
        'pendapatan' => ['omset', 'profit', 'pendapatan', 'pemasukan', 'revenue', 'uang masuk', 'berapa total'],
    ];

    /**
     * Deteksi bagian data yang perlu dimuat untuk sebuah pertanyaan.
     *
     * @return array{primary:string,sections:array<string,bool>}
     */
    private static function detectInternalIntents(string $question): array
    {
        $q = mb_strtolower($question);
        $sections = [];

        // Kode booking yang disebut = sumber data paling presisi, langsung dicocokkan.
        $codes = self::extractCodeCandidates($question);
        if ($codes) {
            $sections['kode'] = true;
        }

        foreach (self::INTERNAL_SECTION_RULES as $section => $triggers) {
            foreach ($triggers as $trigger) {
                if (str_contains($q, $trigger)) {
                    $sections[$section] = true;
                    break;
                }
            }
        }

        // Pertanyaan soal orang: cari nama penyewa dari pertanyaan.
        $sections['cari'] = ($sections['cari'] ?? false) || self::looksLikePersonLookup($question);

        $primary = 'umum';
        if ($sections) {
            $priority = ['kode', 'terlambat', 'denda', 'kembali', 'jadwal', 'pending', 'cari', 'riwayat', 'aktif', 'unit', 'pendapatan'];
            foreach ($priority as $p) {
                if ($sections[$p] ?? false) {
                    $primary = $p;
                    break;
                }
            }
        }

        return ['primary' => $primary, 'sections' => $sections];
    }

    /**
     * Kandidat kode booking yang disebut tim (kode format 12 karakter acak).
     *
     * @return array<int,string>
     */
    private static function extractCodeCandidates(string $question): array
    {
        $upper = mb_strtoupper($question);
        preg_match_all('/\b[A-Z0-9]{8,20}\b/u', $upper, $m);

        $codes = [];
        foreach (array_unique($m[0] ?? []) as $token) {
            // Buang kata umum huruf besar (mis. "TERIMAKASIH")
            if (preg_match('/^[A-Z]+$/', $token) && self::looksLikeIndonesianWord(mb_strtolower($token))) {
                continue;
            }
            $codes[] = $token;
        }

        return array_slice($codes, 0, 3);
    }

    private static function looksLikeIndonesianWord(string $word): bool
    {
        return in_array($word, array_merge(self::NAME_STOPWORDS, [
            'makasih', 'terima', 'kasih', 'tolong', 'permisi', 'mohon', 'selamat', 'pagi', 'siang',
            'sore', 'malam', 'jumpa', 'sampai', 'nanti', 'besok', 'lusa', 'tadi', 'kemarin',
        ]), true);
    }

    /**
     * True kalau pertanyaan mengandung unsur nama orang (bukan kata umum).
     */
    private static function looksLikePersonLookup(string $question): bool
    {
        $tokens = preg_split('/[^a-z0-9]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) < 4) {
                continue;
            }
            if (in_array($t, self::NAME_STOPWORDS, true)) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Susun blok data yang dikirim ke model, hanya bagian relevan + batas karakter.
     *
     * @return array{0:string,1:array<int,string>} [teks data, label yang dimuat]
     */
    private static function buildInternalData(string $question, array $intents, \Carbon\Carbon $now, array $carryOver = []): array
    {
        $s = $intents['sections'];

        // Tanpa pemicu yang jelas dan pesannya pendek: ini pertanyaan lanjutan,
        // jadi pakai kembali bagian data dari giliran sebelumnya. Ini yang bikin
        // "trus yang tadi gimana?" tetap dijawab dari data, bukan dari ingatan free-form.
        $isFollowUp = $s === [] && count($carryOver) > 0 && mb_strlen($question) <= 40;
        if ($isFollowUp) {
            foreach ($carryOver as $section) {
                $s[$section] = true;
            }
        }

        // Tanpa pemicu yang jelas: pakai ringkasan default yang paling sering ditanya.
        $default = $s === [];
        $want = [
            'kode' => $s['kode'] ?? false,
            'jadwal' => $default || ($s['jadwal'] ?? false) || ($s['terlambat'] ?? false),
            'kembali' => $default || ($s['kembali'] ?? false) || ($s['terlambat'] ?? false),
            'terlambat' => $default || ($s['terlambat'] ?? false),
            'cari' => ! $default && (($s['cari'] ?? false) || ($s['kode'] ?? false)),
            'denda' => $s['denda'] ?? false,
            'pending' => ($s['pending'] ?? false) || ($s['jadwal'] ?? false),
            'riwayat' => $s['riwayat'] ?? false,
            'aktif' => $s['aktif'] ?? false,
            'unit' => $s['unit'] ?? false,
        ];

        $statusIndo = [
            'pending' => 'MENUNGGU (belum bayar)',
            'pending_confirmation' => 'MENUNGGU KONFIRMASI',
            'paid' => 'SUDAH BAYAR (belum ambil)',
            'renting' => 'SEDANG DISEWA',
            'completed' => 'SELESAI',
            'cancelled' => 'DIBATALKAN',
        ];

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $snapshot = self::cached('ringkasan', 60, function () use ($now) {
            $counts = \App\Models\Rental::selectRaw('status, COUNT(*) as jml')->groupBy('status')->pluck('jml', 'status');
            $lines = [];
            foreach (['pending', 'pending_confirmation', 'paid', 'renting', 'completed', 'cancelled'] as $st) {
                $lines[] = "- {$st}: " . (int) ($counts[$st] ?? 0);
            }
            $lines[] = '- Total penyewa dengan nama tersimpan: ' . (int) \App\Models\Rental::whereNotNull('nama')->where('nama', '!=', '')->distinct('nama')->count('nama');

            $profitToday = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
                ->whereBetween('waktu_mulai', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));
            $profitMonth = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
                ->whereYear('waktu_mulai', $now->year)->whereMonth('waktu_mulai', $now->month)
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));
            $profitAll = \App\Models\Rental::whereIn('status', ['renting', 'paid', 'completed'])
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));

            $lines[] = '- Omset hari ini: Rp ' . number_format($profitToday, 0, ',', '.');
            $lines[] = '- Omset bulan ini (' . $now->translatedFormat('F Y') . '): Rp ' . number_format($profitMonth, 0, ',', '.');
            $lines[] = '- Omset total: Rp ' . number_format($profitAll, 0, ',', '.');

            return implode("\n", $lines);
        });

        $blocks = [];
        $loaded = [];
        $budget = 6000; // ≈1.900 token untuk seluruh data

        $add = function (string $label, string $key, callable $builder, int $priority) use (&$blocks, &$loaded, &$budget) {
            $text = self::cached('sec_' . $key, 45, $builder);
            $cost = mb_strlen($text);
            if ($cost > $budget) {
                $text = mb_substr($text, 0, max(0, $budget - 60)) . "\n- (data dipotong, ada lagi di luar bagian ini)\n";
                $cost = mb_strlen($text);
            }
            if ($cost <= 0) {
                return;
            }
            $budget -= $cost;
            $blocks[$priority][] = "{$label}:\n{$text}";
            $loaded[] = $label;
        };

        $add('RINGKASAN & OMSET', 'ringkasan', fn () => $snapshot, 1);

        if ($want['jadwal']) {
            $add('JADWAL PENGAMBILAN HARI INI', 'jadwal', function () use ($todayStart, $todayEnd, $statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->whereIn('status', ['pending', 'pending_confirmation', 'paid', 'renting'])
                    ->whereBetween('waktu_mulai', [$todayStart, $todayEnd])
                    ->orderBy('waktu_mulai')
                    ->limit(20)->get();
                return $rows->isEmpty()
                    ? "Tidak ada pengambilan yang dijadwalkan hari ini.\n"
                    : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
            }, 2);
        }

        if ($want['kembali']) {
            $add('JADWAL PENGEMBALIAN HARI INI', 'kembali', function () use ($todayStart, $todayEnd, $now, $statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->whereIn('status', ['renting', 'paid', 'pending_confirmation', 'completed'])
                    ->whereBetween('waktu_selesai', [$todayStart, $todayEnd])
                    ->orderBy('waktu_selesai')
                    ->limit(20)->get();
                if ($rows->isEmpty()) {
                    return "Tidak ada pengembalian yang dijadwalkan hari ini.\n";
                }
                return $rows->map(function ($r) use ($now, $statusIndo) {
                    $suffix = '';
                    if ($r->waktu_selesai && \Carbon\Carbon::parse($r->waktu_selesai)->isPast()) {
                        $suffix = ' *** SUDAH MELEBIHI JADWAL ' . self::minutesLate($now, $r->waktu_selesai) . ' MENIT ***';
                    } elseif ($r->handed_over_at) {
                        $suffix = ' | Sudah dikembalikan: ' . \Carbon\Carbon::parse($r->handed_over_at)->translatedFormat('d M H:i');
                    }
                    return self::rentalLine($r, $statusIndo, $suffix);
                })->implode('');
            }, 3);
        }

        if ($want['terlambat']) {
            $add('PENYEWA TERLAMBAT (sedang berjalan)', 'telat', function () use ($now, $statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->whereIn('status', ['renting', 'paid', 'pending_confirmation'])
                    ->where('waktu_selesai', '<', $now)
                    ->orderBy('waktu_selesai')
                    ->limit(15)->get();
                return $rows->isEmpty()
                    ? "Tidak ada penyewa yang terlambat.\n"
                    : $rows->map(fn ($r) => self::rentalLine(
                        $r,
                        $statusIndo,
                        ' *** TERLAMBAT ' . self::minutesLate($now, $r->waktu_selesai) . ' MENIT ***'
                    ))->implode('');
            }, 2);
        }

        if ($want['cari']) {
            $add('PENCARIAN DATA PENYEWA (WAJIB DIBACA untuk soal orang tertentu)', 'cari', fn () => self::lookupRentalsByName($question), 2);
        }

        if ($want['denda']) {
            $add('RIWAYAT PERNAH KENA DENDA', 'denda', function () use ($statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->where(function ($q) {
                        $q->where('denda', '>', 0)->orWhere('denda_kerusakan', '>', 0);
                    })
                    ->orderByDesc('denda')->orderByDesc('denda_kerusakan')
                    ->limit(10)->get();
                if ($rows->isEmpty()) {
                    return "Belum ada penyewa yang pernah dikenakan denda.\n";
                }
                return $rows->map(function ($r) use ($statusIndo) {
                    $line = self::rentalLine($r, $statusIndo);
                    return $line . '  Denda telat: Rp ' . number_format($r->denda ?? 0, 0, ',', '.')
                        . ' | Denda kerusakan: Rp ' . number_format($r->denda_kerusakan ?? 0, 0, ',', '.')
                        . ' | Alasan: ' . ($r->catatan_kerusakan ?: '-') . "\n";
                })->implode('');
            }, 4);
        }

        if ($want['pending']) {
            $add('BOOKING MENUNGGU PENGAMBILAN (belum bayar)', 'pending', function () use ($statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->where('status', 'pending')
                    ->orderBy('waktu_mulai')
                    ->limit(12)->get();
                return $rows->isEmpty()
                    ? "Tidak ada booking yang menunggu pengambilan.\n"
                    : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
            }, 4);
        }

        if ($want['riwayat']) {
            $add('RIWAYAT PERNAH TERLAMBAT MENGEMBALIKAN', 'riwayat', function () use ($statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->whereNotNull('handed_over_at')->whereNotNull('waktu_selesai')
                    ->whereColumn('handed_over_at', '>', 'waktu_selesai')
                    ->orderByDesc('handed_over_at')
                    ->limit(10)->get();
                return $rows->isEmpty()
                    ? "Belum ada riwayat penyewa yang terlambat mengembalikan unit.\n"
                    : $rows->map(fn ($r) => self::rentalLine(
                        $r,
                        $statusIndo,
                        ' | Telat: ' . (int) round(\Carbon\Carbon::parse($r->waktu_selesai)->diffInMinutes(\Carbon\Carbon::parse($r->handed_over_at))) . ' menit'
                    ))->implode('');
            }, 5);
        }

        if ($want['aktif']) {
            $add('UNIT YANG SEDANG DISEWA', 'aktif', function () use ($statusIndo) {
                $rows = \App\Models\Rental::with(['units'])
                    ->where('status', 'renting')
                    ->where('waktu_selesai', '>=', now())
                    ->orderBy('waktu_selesai')
                    ->limit(20)->get();
                return $rows->isEmpty()
                    ? "Tidak ada unit yang sedang disewa saat ini.\n"
                    : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
            }, 4);
        }

        if ($want['unit']) {
            $add('DAFTAR UNIT TOKO', 'unit', function () {
                $units = \App\Models\Unit::where('is_active', true)->with('category')->get();
                $text = '';
                foreach ($units as $u) {
                    $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24jam' : '-';
                    $text .= "- [ID:{$u->id}] {$u->nama_lengkap} (" . ($u->category?->name ?? 'Unit') . ", {$p24})\n";
                }
                return $text !== '' ? $text : "Belum ada unit aktif.\n";
            }, 6);
        }

        ksort($blocks);

        $text = implode("\n\n", array_merge(...array_values($blocks) ?: [[]]));

        // Rapikan baris kosong berlebih pada blok data.
        $text = trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);

        return [$text, $loaded];
    }

    /**
     * Cache rendered section supaya query berat tidak diulang tiap pertanyaan
     * (bot dipakai banyak orang, tabel rental sizable).
     */
    private static function cached(string $key, int $seconds, callable $builder): string
    {
        $cacheKey = 'ai_ctx_' . $key . '_' . substr(md5(static::class . $key), 0, 8);

        try {
            return (string) \Illuminate\Support\Facades\Cache::remember($cacheKey, $seconds, $builder);
        } catch (\Throwable $e) {
            return (string) $builder();
        }
    }

    /**
     * Ringkas prompt saat pemakaian token menit ini sudah mendekati plafon.
     * Lebih baik konteksnya dikurangi daripada dapat Error 429.
     */
    private static function shrinkInternalPrompt(string $prompt): string
    {
        // Buang blok data, sisakan aturan + memori + pertanyaan.
        $pos = strpos($prompt, 'DATA BISNIS');
        if ($pos !== false) {
            $questionPos = strpos($prompt, 'Pertanyaan tim:');
            $question = $questionPos !== false ? substr($prompt, $questionPos) : '';
            $prompt = substr($prompt, 0, $pos) . "DATA BISNIS: (dikurangi sementara karena pemakaian token sedang tinggi; jawab sebatas yang sudah ada, jangan mengarang.)\n\n" . $question;
        }
        return $prompt;
    }

    /**
     * Ubah daftar turn terakhir menjadi blok teks ringkas untuk prompt.
     */
    private static function formatRecentTurns(array $turns): string
    {
        if (empty($turns)) {
            return '';
        }
        $text = '';
        foreach ($turns as $i => $turn) {
            $text .= ($i + 1) . '. Tim: ' . $turn['user'] . "\n   Kamu: " . $turn['model'] . "\n";
        }
        return rtrim($text);
    }

    /** Alasan panggilan Gemini terakhir yang gagal, untuk pesan fallback. */
    private static ?string $lastError = null;

    /**
     * Panggil Gemini sekali dan kembalikan teks bersih (null bila gagal).
     */
    private static function askGemini(string $prompt, string $model, string $apiKey, float $temperature, int $maxTokens, int $timeout): ?string
    {
        self::$lastError = null;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
                ]
            );

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (! empty($candidates[0]['content']['parts'][0]['text'])) {
                    return self::formatForWhatsApp(trim($candidates[0]['content']['parts'][0]['text']));
                }
                self::$lastError = 'jawaban kosong dari model ' . $model;
                Log::warning('GeminiAIService: jawaban kosong dari model ' . $model);
            } else {
                $status = $response?->status() ?? 0;
                $body = mb_substr((string) $response?->body(), 0, 400);
                self::$lastError = 'HTTP ' . $status . ' dari model ' . $model . ' — ' . self::readApiError($body);
                Log::warning('GeminiAIService gagal: HTTP ' . $status . ' | model ' . $model . ' | ' . $body);
            }
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            Log::error('GeminiAIService Exception: ' . $e->getMessage());
        }

        return null;
    }

    /** Ambil pesan error yang paling berguna dari body respons Google. */
    private static function readApiError(string $body): string
    {
        if ($body === '') {
            return '(respons kosong)';
        }
        $json = json_decode($body, true);
        $msg = $json['error']['message'] ?? null;
        return is_string($msg) && $msg !== '' ? mb_substr($msg, 0, 200) : mb_substr($body, 0, 200);
    }

    /**
     * Bot tidak boleh diam tanpa jejak.
     *
     * Dulu kegagalan AI hanya di-log lalu `reply` dikirim null, sehingga di grup
     * tidak ada yang terjadi dan tim mengira botnya "nyabodoh". Sekarang kegagalan
     * ini dikembalikan sebagai pesan yang isinya memberi tahu apa yang salah.
     */
    private static function fallbackMessage(): string
    {
        $reason = self::$lastError ?: 'sebab tidak diketahui';

        if (str_contains($reason, 'API_KEY_INVALID') || str_contains($reason, 'API key not valid')) {
            $hint = "Kunci API AI tidak valid. Buka *Web Admin -> Settings -> Tab Chatbot*, isi *Gemini API Key* yang benar lalu simpan.";
        } elseif (str_contains($reason, '429') || str_contains($reason, 'RESOURCE_EXHAUSTED')) {
            $hint = "Kuota API AI habis / kena rate limit. Tunggu sebentar atau ganti model ke yang lebih hemat di *Web Admin -> Settings -> Tab Chatbot*.";
        } elseif (str_contains($reason, '404') || str_contains($reason, 'NOT_FOUND')) {
            $hint = "Model AI yang dipilih tidak tersedia. Ganti model di *Web Admin -> Settings -> Tab Chatbot* (kosongkan dulu supaya kembali ke default).";
        } elseif (stripos($reason, 'timed out') !== false || stripos($reason, 'cURL error 28') !== false) {
            $hint = "Server terlalu lama menunggu jawaban AI. Coba ulangi pertanyaannya sebentar lagi.";
        } else {
            $hint = "Cek *Web Admin -> Settings -> Tab Chatbot* (kunci API & model), lalu coba ulangi.";
        }

        return self::formatForWhatsApp(
            "⚠️ *Data belum bisa dimuat — asisten AI gagal menjawab.*\n\n"
            . "Sebab: " . mb_substr($reason, 0, 220) . "\n\n"
            . $hint
        );
    }

    /** Model aktif, dinormalisasi bila versi lamanya masih tersimpan di Setting. */
    private static function activeModel(): string
    {
        $model = Setting::getVal('chatbot_model', 'gemini-3.5-flash-lite');
        if (in_array($model, ['gemini-2.0-flash-lite', 'gemini-1.5-flash-8b', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            return 'gemini-3.5-flash-lite';
        }
        return $model;
    }

    /**
     * Selisih menit yang sudah lewat, selalu bilangan bulat positif.
     *
     * Carbon 3 mengembalikan diffInMinutes() sebagai float, dan tandanya negatif
     * kalau argumennya ada di masa lalu. Dipakai langsung, laporan bisa tampil
     * "TERLAMBAT -826 menit". Hitung dari $since->diffInMinutes($now) supaya
     * arahnya tidak ambigu, lalu bulatkan dan jepit minimal 0.
     */
    private static function minutesLate(\Carbon\Carbon $now, $since): int
    {
        if (!$since) {
            return 0;
        }
        $since = \Carbon\Carbon::parse($since);
        if ($since->greaterThanOrEqualTo($now)) {
            return 0;
        }
        return (int) round($since->diffInMinutes($now));
    }

    /**
     * Satu baris ringkasan transaksi untuk ditampilkan ke AI.
     */
    private static function rentalLine($r, array $statusIndo = [], string $suffix = ''): string
    {
        $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ') ?: '-';
        $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M H:i') : '-';
        $endStr   = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M H:i') : '-';
        $total    = 'Rp ' . number_format($r->grand_total ?: $r->subtotal_harga, 0, ',', '.');
        $status   = $statusIndo[$r->status] ?? $r->status;

        return "• [{$status}] Unit: {$uNames} | Penyewa: " . ($r->nama ?: '-')
            . " | WA: " . ($r->no_wa ?: '-')
            . " | Alamat: " . ($r->alamat ?: '-')
            . " | Ambil: {$startStr} | Selesai: {$endStr}"
            . " | Total: {$total}"
            . " | Kode: " . ($r->booking_code ?: '-')
            . $suffix . "\n";
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
