<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    /**
     * Token yang harus ditulis model kalau chat customer di luar topik sewa.
     *
     * Isi chat tetap dikembalikan ke customer sebagai arahkan ke admin (diatur
     * bot), bukan jawaban model, supaya model tidak mengarang jawaban untuk
     * topik yang memang bukan urusan Rent Space.
     */
    public const OFF_TOPIC_TOKEN = '[[DI LUAR TOPIK]]';

    /**
     * Penolakan yang dianggap handoff walau model tidak menulis penandanya.
     *
     * Sengaja sempit: harus diawali "maaf" dan menyebut tidak bisa. Kalimat
     * "maaf kak, unit itu lagi_full" tetap jawaban normal, bukan handoff.
     */
    private const REFUSAL_PATTERNS = [
        '/^maaf\b.{0,40}\btidak bisa (membantu|menjawab|menjawabnya)/iu',
        '/^maaf\b.{0,40}\bbukan (saya|wewenang|tugas saya)/iu',
        '/^maaf\b.{0,40}\bdi luar (kemampuan|batasan|topik)/iu',
    ];

    /** Setting pemanggil API key tiap fitur. */
    private const FEATURE_KEY_SETTING = [
        'customer' => 'chatbot_api_key',
        'report' => 'report_api_key',
        'broadcast' => 'broadcast_api_key',
    ];

    /** Padanan key fitur di config/services.php (jejak .env). */
    private const FEATURE_CONFIG_KEY = [
        'customer' => 'services.gemini.key',
        'report' => 'services.gemini.report_key',
        'broadcast' => 'services.gemini.broadcast_key',
    ];

    /** Setting pemanggil model AI tiap fitur. */
    private const FEATURE_MODEL_SETTING = [
        'customer' => 'chatbot_model',
        'report' => 'report_model',
        'broadcast' => 'broadcast_model',
    ];

    /**
     * Model bawaan kalau admin mengosongkan field model.
     *
     * Public supaya halaman Pengaturan memakai konstanta yang sama, bukan
     * menyalin stringnya. Saat string-nya dicopier, admin yang mengosongkan
     * model diam-diam tersimpan model lama yang tidak lagi jadi default.
     */
    public const DEFAULT_MODEL = 'gemini-3.6-flash';

    /** Model lama yang sudah tidak ada di katalog API Google. */
    private const LEGACY_MODELS = [
        'gemini-2.0-flash-lite',
        'gemini-1.5-flash-8b',
        'gemini-1.5-flash',
        'gemini-2.0-flash',
    ];

    /** Label fitur untuk pesan error ke admin. */
    private const FEATURE_LABELS = [
        'customer' => 'jawab customer',
        'report' => 'laporan grup tim',
        'broadcast' => 'broadcast',
    ];

    /**
     * Berapa slot kunci AI yang bisa diisi per fitur.
     *
     * Slot 1 memakai nama setting lama (`chatbot_api_key`), slot 2-4 memakai
     * sufiks `_2`, `_3`, `_4`. Karena itu instalasi lama yang cuma punya satu
     * kunci tetap jalan tanpa diisi ulang.
     */
    public const KEY_POOL_SIZE = 4;

    /**
     * Berapa lama kunci yang kena 429 disingkirkan sebelum dicoba lagi.
     *
     * Jendela rate limit Gemini（TPM) berputar 60 detik, jadi 90 detik cukup
     * untuk menyebutnya pulih. Kalau yang habis ternyata kuota harian (RPD),
     * cooldown ini hanya menahan sebentar lalu dicoba lagi — hasilnya tetap
     * pesan "kuota habis" ke admin, bukan jawaban basi ke customer.
     */
    private const KEY_COOLDOWN_SECONDS = 90;

    /**
     * Status HTTP yang layak dicoba lagi ke kunci berikutnya.
     *
     * 429 = kuota kunci itu sedang habis. 5xx = Google sedang sibuk, yang paling
     * sering datang sebagai 503 "This model is currently experiencing high
     * demand". Keduanya ditolak cepat (bukan timeout), jadi pindah ke kunci lain
     * hampir gratis — jauh lebih murah daripada membiarkan customer atau admin
     * dapat "asisten AI gagal menjawab".
     *
     * 400/401/403/404 sengaja TIDAK ada di sini: itu salah konfigurasi (key mati,
     * model tidak ada, payload ditolak) dan akan gagal sama persis di semua kunci,
     * jadi mencobanya ke kunci lain cuma menambah lambat tanpa mungkin berhasil.
     *
     * Timeout (cURL error 28) TIDAK masuk daftar ini karena itu bukan status
     * HTTP, tapi tetap ditangani sebagai kondisi transient di catch() bawah.
     * Timeout ke Google sering terjadi acak dan kunci berikutnya biasanya
     * sehat, jadi menyerah di kunci pertama membuat customer dapat
     * "diteruskan ke admin" padahal masih ada tiga kunci yang belum dicoba.
     */
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    /**
     * Pola pesan exception yang layak dicoba lagi ke kunci berikutnya.
     *
     * Semuanya masalah jaringan atau sisi server, bukan salah konfigurasi
     * kunci: timeout, koneksi terputus, dan host tidak bisa_contact.
     * Exception konfigurasi (key ditolak, model tidak ada) sengaja tidak
     * masuk supaya tidak menambah lambat tanpa mungkin berhasil.
     */
    private const TRANSIENT_EXCEPTION_PATTERNS = [
        'cURL error 28',
        'timed out',
        'Operation timed out',
        'Connection timed out',
        'Connection reset',
        'Connection refused',
        'Could not resolve host',
        'Failed to connect',
        'Empty reply from server',
        'Recv failure',
        'Send failure',
    ];

    /**
     * Jeda singkat sebelum pindah kunci saat kena 5xx.
     *
     * 429 tidak perlu jeda: kuota kunci itu sudah penuh, langsung pindah ke
     * kunci berikutnya. Untuk 5xx artinya server Google sedang sibuk, dan
     * menembak kunci-kunci lain dalam milidetik yang sama hanya menambah
     * antrean di puncak yang sama. 300ms cukup untuk mereda tanpa kelihatan.
     */
    private const SERVER_BUSY_BACKOFF_US = 300000;

    /**
     * Seluruh kunci milik sebuah fitur, urut dari slot 1.
     *
     * Google menghitung rate limit per PROJECT, bukan per API key: empat kunci
     * dari satu project berbagi kuota yang sama. Jadi pool ini baru menambah
     * kuota kalau admin mengisinya dari project berbeda, dan urutannya hanya
     * saja dipakai sebagai cadangan/failover.
     *
     * Urutan cadangan tetap sama seperti versi satu-kunci: slot fitur di DB ->
     * kunci fitur di .env -> kunci customer (DB lalu .env).
     *
     * @return string[]
     */
    public static function apiKeysFor(string $feature): array
    {
        $settingKey = self::FEATURE_KEY_SETTING[$feature] ?? 'chatbot_api_key';
        $configPath = self::FEATURE_CONFIG_KEY[$feature] ?? 'services.gemini.key';

        $keys = [];
        for ($slot = 1; $slot <= self::KEY_POOL_SIZE; $slot++) {
            $name = $slot === 1 ? $settingKey : "{$settingKey}_{$slot}";
            $key = trim((string) Setting::getVal($name, ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        if (! $keys) {
            $key = trim((string) (config($configPath) ?: ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        if (! $keys && $settingKey !== 'chatbot_api_key') {
            $key = trim((string) Setting::getVal('chatbot_api_key', ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        if (! $keys) {
            $key = trim((string) (config('services.gemini.key') ?: ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Kunci cadangan milik fitur customer untuk fitur lain yang pool-nya kurang.
     *
     * Dipisah dari apiKeysFor() dengan sengaja: apiKeysFor() mengembalikan daftar
     * akhir yang sudah diputar gilirannya per fitur, sedangkan ini daftar statis
     * yang ditambahkan belakangan sebagai cadangan.
     *
     * @return string[]
     */
    private static function reserveKeysFor(string $feature): array
    {
        if ($feature === 'customer') {
            return [];
        }

        $reserve = [];
        for ($slot = 1; $slot <= self::KEY_POOL_SIZE; $slot++) {
            $key = trim((string) Setting::getVal(self::keySlotName('customer', $slot), ''));
            if ($key !== '') {
                $reserve[] = $key;
            }
        }

        if ($reserve === []) {
            $key = trim((string) (config('services.gemini.key') ?: ''));
            if ($key !== '') {
                $reserve[] = $key;
            }
        }

        return array_values(array_unique($reserve));
    }

    /** Kunci utama sebuah fitur, yaitu slot 1. */
    public static function apiKeyFor(string $feature): ?string
    {
        return self::apiKeysFor($feature)[0] ?? null;
    }

    /**
     * Nama slot kunci sebuah fitur — sama untuk setting DB dan properti Livewire.
     *
     * Satu metode dipakai untuk keduanya supaya tidak pernah bisa melenceng:
     * kalau nama setting dan nama properti berbeda, slot yang disimpan tidak
     * sama dengan slot yang dibaca, dan gejalanya "kunci mysteriously kosong".
     */
    public static function keySlotName(string $feature, int $slot): string
    {
        $base = self::FEATURE_KEY_SETTING[$feature] ?? 'chatbot_api_key';

        return $slot <= 1 ? $base : "{$base}_{$slot}";
    }

    /**
     * Susun pool kunci dengan rotasi giliran, lalu sisihkan yang sedang cooldown.
     *
     * Rotasi disimpan di cache, bukan di memori statis, karena tiap request
     * web punya proses PHP sendiri. Kalau cache tidak bisa dipakai, urutan
     * dikembalikan apa adanya — bot tetap jalan, hanya gilirannya statis.
     *
     * @param  string[]  $keys
     * @return string[]
     */
    private static function usableKeysFor(string $feature, array $keys): array
    {
        [$usable, $cooling] = self::partitionKeysFor($feature, $keys);

        // Semua kunci sedang cooldown: jangan diam, pakai yang cooldown
        // paling lama. Mending 429 daripada customer dapat balasan kosong.
        if ($usable === [] && $cooling !== []) {
            return [$cooling[0]];
        }

        return $usable;
    }

    /**
     * Pisahkan kunci menjadi yang sehat dan yang sedang cooldown, urut giliran
     * rotasi sudah diterapkan ke keduanya.
     *
     * Dipisah dari usableKeysFor() karena pemanggil butuh tahu mana yang boleh
     * dipakai sekarang dan mana yang hanya boleh jadi upaya terakhir. Kalau
     * kunci yang sudah pasti kena 429 didahulukan, permintaan berikutnya
     * membuang satu giliran di kunci yang memang tidak akan berhasil — padahal
     * masih ada kunci cadangan yang sehat.
     *
     * @param  string[]  $keys
     * @return array{0: string[], 1: string[]}
     */
    private static function partitionKeysFor(string $feature, array $keys): array
    {
        if ($keys === []) {
            return [[], []];
        }

        $ordered = $keys;
        if (count($keys) > 1) {
            $cursorName = 'ai_key_cursor_' . $feature;
            $start = 0;
            try {
                $start = (int) (\Illuminate\Support\Facades\Cache::get($cursorName, 0));
                \Illuminate\Support\Facades\Cache::put($cursorName, $start + 1, \Illuminate\Support\Carbon::now()->addDays(2));
            } catch (\Throwable $e) {
                Log::warning('GeminiAIService: rotasi kunci tidak aktif (' . $e->getMessage() . ')');
                $start = 0;
            }

            $total = count($keys);
            $start = (($start % $total) + $total) % $total;
            $ordered = array_merge(array_slice($keys, $start), array_slice($keys, 0, $start));
        }

        $usable = [];
        $cooling = [];
        foreach ($ordered as $key) {
            if (self::isCoolingDown($feature, $key)) {
                $cooling[] = $key;
            } else {
                $usable[] = $key;
            }
        }

        return [$usable, $cooling];
    }

    /** Cache key penanda cooldown sebuah kunci. */
    private static function cooldownCacheKey(string $feature, string $apiKey): string
    {
        return 'ai_key_cool_' . $feature . '_' . substr(md5($apiKey), 0, 12);
    }

    private static function isCoolingDown(string $feature, string $apiKey): bool
    {
        try {
            return \Illuminate\Support\Facades\Cache::has(self::cooldownCacheKey($feature, $apiKey));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Buang API key dari teks apa pun yang mau masuk log atau ditampilkan ke admin.
     *
     * Pesan error cURL dari Laravel memuat URL lengkap, termasuk `?key=...`.
     * Tanpa penyaringan ini, storage/logs/laravel.log menyimpan API key dalam
     * bentuk plaintext selama berbulan-bulan. Dipanggil di dua tempat: sebelum
     * menulis ke log, dan sebelum teks masuk $lastError yang dirender di
     * halaman Pengaturan.
     */
    private static function maskSecrets(string $text): string
    {
        $masked = preg_replace('/([?&]key=)[A-Za-z0-9._\-]+/', '$1***', $text);

        // Kunci yang sudah terambil dari query string tetap dibersihkan kalau
        // muncul bentuk mentahnya (mis. tertanam di body error Google).
        $masked = preg_replace('/\b(?:AIza|AQ\.Ab)[A-Za-z0-9._\-]{20,}/', '***', (string) $masked);

        return $masked;
    }

    /**
     * Apakah exception ini Transient (jaringan/server) sehingga layak dicoba
     * lagi ke kunci berikutnya?
     *
     * Bedanya dengan status HTTP: di sini tidak ada kode status, hanya teks
     * pesan. Yang penting hanya membedakannya dari salah konfigurasi — timeout
     * atau koneksi putus akan mencoba kunci lain, sedangkan "API key not valid"
     * tidak akan pernah berhasil di kunci manapun juga.
     */
    private static function isTransientException(string $message): bool
    {
        foreach (self::TRANSIENT_EXCEPTION_PATTERNS as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function markCooldown(string $feature, string $apiKey): void
    {
        try {
            \Illuminate\Support\Facades\Cache::put(
                self::cooldownCacheKey($feature, $apiKey),
                1,
                \Illuminate\Support\Carbon::now()->addSeconds(self::KEY_COOLDOWN_SECONDS)
            );
        } catch (\Throwable $e) {
            Log::warning('GeminiAIService: gagal menandai cooldown kunci (' . $e->getMessage() . ')');
        }
    }

    /**
     * Ringkasan kesehatan pool kunci, untuk ditampilkan di Pengaturan.
     *
     * @return array{total:int, usable:int, cooling:array<int, int>}
     */
    public static function keyPoolStatus(string $feature): array
    {
        $keys = self::apiKeysFor($feature);
        $cooling = [];
        $usable = 0;

        foreach ($keys as $index => $key) {
            if (self::isCoolingDown($feature, $key)) {
                $cooling[] = $index + 1;
            } else {
                $usable++;
            }
        }

        return ['total' => count($keys), 'usable' => $usable, 'cooling' => $cooling];
    }

    /**
     * Model AI untuk sebuah fitur, dinormalisasi bila versi lamanya masih
     * tersimpan di Setting.
     */
    public static function modelFor(string $feature): string
    {
        $settingKey = self::FEATURE_MODEL_SETTING[$feature] ?? 'chatbot_model';

        $model = trim((string) Setting::getVal($settingKey, ''));
        if ($model === '' || in_array($model, self::LEGACY_MODELS, true)) {
            return self::DEFAULT_MODEL;
        }

        return $model;
    }

    /** Nama fitur yang bisa dibaca manusia, untuk pesan error. */
    public static function featureLabel(string $feature): string
    {
        return self::FEATURE_LABELS[$feature] ?? $feature;
    }

    /** Kata-kata sapaan; dipakai untuk memutuskan apakah sapaan dibuang. */
    private const GREETING_WORDS = [
        'halo', 'hai', 'hi', 'hello', 'hey', 'permisi', 'pagi', 'siang', 'sore',
        'malam', 'salam', 'assalamualaikum', 'waalaikumsalam', 'selamat', 'datang',
        'mohon', 'maaf', 'kak', 'kakak', 'bang', 'mas', 'bro',
    ];

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
        return self::customerReply($userMessage, $customerName, $senderJid, $customerPhone)['reply'];
    }

    /**
     * Sama seperti reply(), tapi ikut melaporkan kalau chat-nya di luar topik sewa.
     *
     * @return array{reply: ?string, handoff: bool, reason: ?string}
     */
    public static function customerReply(string $userMessage, string $customerName = 'Kak', ?string $senderJid = null, ?string $customerPhone = null): array
    {
        $apiKey = self::apiKeyFor('customer');
        if (!$apiKey) {
            Log::info('GeminiAIService: API Key Customer belum diisi di Pengaturan.');
            return ['reply' => null, 'handoff' => false, 'reason' => null];
        }

        $model = self::modelFor('customer');

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
        $recentTurns = AiMemoryService::recentTurns($conv, 3, 520);
        $isFollowUp = mb_strlen($userMessage) <= 30 && !empty($recentTurns);

        $now = now();
        $currentTimeStr = $now->translatedFormat('l, d F Y H:i') . ' WIB';
        $address = Setting::getVal('admin_address', 'Purwokerto');
        $adminWa = Setting::getVal('admin_wa', '0881082411878');

        $dataBlock = self::buildCustomerData($sections, $isFollowUp, $now);

        $memoryContext = AiMemoryService::contextFor($conv, $userMessage, 260);
        $chatContext = self::formatRecentTurns($recentTurns, 'Customer', 'CS');

        // Pertanyaan customer sebelumnya, supaya model tidak salah menjawab
        // pertanyaan yang sudah lewat (mis. dijawab iPhone 12 padahal yang
        // ditanya iPhone 13).
        $prevQuestion = '';
        if (!empty($recentTurns)) {
            $prevQuestion = trim((string) end($recentTurns)['user']);
        }
        $isFirstTurn = $prevQuestion === '';

        $customRules = self::customKnowledgeText();

        $systemPrompt = "Kamu CS WhatsApp asli dari 'Rent Space Purwokerto' (rental iPhone, gadget, kamera, PS3 di Purwokerto).
Waktu saat ini: {$currentTimeStr}.
Nama customer: {$customerName}.
Toko: {$address} | WA Admin: {$adminWa} | Booking: https://rentspacepurwokerto.my.id/booking

CARA PESAN: buka https://rentspacepurwokerto.my.id/booking → pilih tanggal → pilih unit → isi data → pilih pembayaran (QRIS / Transfer / Cash) → bayar & unggah bukti bila perlu → admin konfirmasi → unit siap diambil di toko. Kode promo bisa diinput di halaman booking.
" . ($customRules !== '' ? "\n{$customRules}\n" : '') . "
{$dataBlock}
" . ($memoryContext !== '' ? "\nCATATAN PERCAKAPAN SEBELUMNYA:\n{$memoryContext}\n" : '')
. ($chatContext !== '' ? "\nOBROLAN SEBELUMNYA:\n{$chatContext}\n" : '')
. ($prevQuestion !== '' ? "\nCustomer SEKARANG nanya: \"{$userMessage}\"\nCustomer SEBELUMNYA nanya: \"{$prevQuestion}\"\n" : "\nCustomer nanya: \"{$userMessage}\"\n")
. "\nCARA JAWAB (WAJIB, ini yang bikin kelihatan manusia):
1. Jawab HANYA pertanyaan terakhir di atas. Kalau customer ganti topik, jangan campur jawaban yang lama.
2. PANJANG: 1-2 kalimat pendek, maksimal sekitar 200 karakter. Bukan paragraf, bukan daftar panjang. Kalau mepet, tulis yang paling penting saja.
3. JANGAN buka dengan 'Halo Kak' / 'Hai Kak' lagi kalau obrolan sudah berjalan. Sapaan cuma di pesan pertama saja.
4. JANGAN sebut kode internal unit ('ID: 8'), kategori, atau istilah teknis. Customer cuma butuh: nama unit, harga, dan kapan bebas.
5. JANGAN mengulang-ulang pertanyaan customer dan JANGAN menutup dengan basa-basi ('Jika ada yang lain...', 'Ada yang bisa dibantu lagi?'). Beri jawabannya, lalu STOP.
6. Jangan tempel promo/kode diskon kalau customer tidak tanya promo atau harga.
7. Emoji paling banyak 1, dan jangan pakai 🙏/😊 di tiap balasan. Jangan pakai markdown *, [], atau bullet kecuali customer memang minta daftar.
8. Ikuti gaya customer: kalau dia ngetik singkat dan santai ('ip 12 ready kapan?'), kamu balas singkat dan santai juga. Huruf besar di awal kalimat saja.
9. KETERSEDIAAN: pakai bagian KETERSEDIAAN SEKARANG. Unit yang namanya ada di sana berarti READY sekarang — sebutkan namanya, jangan cuma 'ada yang ready'. Kalau ada unit di SEDANG DIPAKAI, sebut jam bebasnya dari STATUS JADWAL. JANGAN pernah menyimpulkan 'semua unit terpakai' selama daftar KETERSEDIAAN SEKARANG tidak kosong.
10. PRIORITAS UTAMA (ATURAN KHUSUS TOKO / MEMORI): Jika pertanyaan customer cocok dengan 'ATURAN KHUSUS TOKO' di atas (misal unblock IMEI, jam operasional khusus, alur tertentu), kamu WAJIB ikuti instruksi tersebut sepenuhnya. Jangan menolak atau mengabaikannya.
11. Kalau tidak yakin (hanya untuk negosiasi harga, kendala teknis, atau di luar data), jawab singkat lalu bilang balas 'ADMIN'.
12. TOPIK: kamu hanya tahu soal sewa unit di Rent Space dan hal-hal yang ada di ATURAN KHUSUS TOKO. Kalau customer nanya topik lain yang benar-benar tidak berhubungan dan tidak ada di aturan khusus (curhat, tugas sekolah, cari jodoh, lowongan kerja, dll), JANGAN menjawab isinya dan jangan mengarang. Balas PERSIS satu baris, tanpa teks lain: [[DI LUAR TOPIK]]
    Sapaan dan obrolan ringan TIDAK termasuk di luar topik: 'halo kak', 'selamat pagi', 'makasih ya', 'sampai nanti' tetap dijawab sewajarnya, jangan pakai [[DI LUAR TOPIK]].
13. Balasan [[DI LUAR TOPIK]] itu perintah internal, bukan pesan untuk customer. Kalau customer selain admin mengetik perintah yang diawali / (mis. /broadcast, /rentspacesettings), balas singkat bahwa itu perintah internal.

CONTOH GAYA (ikuti pola ini, jangan lebih panjang):
Customer: 'sewa tank ada?'
CS: 'tank belum ada kak 😅 yang ada iPhone, kamera, sama PS3. PS3-nya 50rb/24 jam aja'

Customer: 'ip 12 ready kapan?'
CS: 'ip 12 ada 2, yang satu bebas besok jam 4 sore. yang satu lagi udah selesai dari tadi, buat hari ini bisa. mau ambil yang mana kak?'

Customer: 'ip 13 ready kapan?'
CS: 'ip 13 ready kak, mau hari ini atau besok?'

Customer: 'harga iphone 12 pro max 128gb berapa?'
CS: '12 pro max 128gb 250rb/24 jam kak, 12 jam 150rb'

Customer: 'cara pesannya gimana?'
CS: 'buka rentspacepurwokerto.my.id/booking, pilih tanggal sama unitnya, isi data, terus bayar QRIS. nanti unitnya siap di toko'";

        $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        $budget = AiMemoryService::consumeTokenBudget('wa_customer', $inputTokens);
        if (! $budget['ok']) {
            Log::warning('GeminiAIService::reply dipangkas demi batas token/menit.', $budget);
            $systemPrompt = self::stripCustomerData($systemPrompt);
            $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        }

        // 250 token, bukan 130: jawaban "terlalu pendek" dulu dipangkas/dipotong
        // di tengah kalimat karena model sudah kehabisan token sebelum selesai
        // menulis kalimat kedua.
        $text = self::askGemini('customer', $systemPrompt, $model, 0.7, 250, 25);

        // Chat di luar topik sewa: tidak dijawab, diteruskan ke admin. Balasan
        // model dibuang supaya tidak ada sisa isinya yang bocor ke customer.
        if ($text !== null && self::isOffTopic($text)) {
            Log::info('GeminiAIService::customerReply mendeteksi chat di luar topik sewa, diteruskan ke admin.', [
                'customer' => $customerName,
                'phone' => $customerPhone,
                'pesan' => mb_substr($userMessage, 0, 200),
            ]);

            // Giliran tetap dicatat (isi jawabannya diganti penanda, bukan
            // teks aslinya) supaya obrolan tidak terputus. Kalau dilewatkan,
            // pesan berikutnya dianggap giliran pertama: histori kosong, sapaan
            // pembuka dibiarkan, dan model lupa apa yang tadi customer tanya.
            AiMemoryService::saveTurn($conv, $userMessage, '[diteruskan ke admin]', 'handoff', $inputTokens, $customerName);

            return ['reply' => null, 'handoff' => true, 'reason' => 'di luar topik sewa'];
        }

        if ($text !== null) {
            // Panduan cara pesan memang perlu ruang lebih; sisanya dibatasi ketat
            // supaya tetap resemble chat CS, bukan paragraf AI.
            $text = self::tidyCustomerReply(
                $text,
                $isFirstTurn,
                ($sections['cara_pesan'] ?? false) ? 520 : 300,
                $userMessage
            );
            AiMemoryService::saveTurn($conv, $userMessage, $text, self::customerIntent($sections), $inputTokens, $customerName);
            // Cache lama tetap diisi agar kompatibel dengan alur lama (rapid reply).
            $cacheKey = 'wa_chat_history_' . md5($peer);
            $legacy = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
            $legacy[] = ['user' => $userMessage, 'model' => $text, 'time' => now()->toDateTimeString()];
            \Illuminate\Support\Facades\Cache::put($cacheKey, array_slice($legacy, -10), 7200);
        }

        return ['reply' => $text, 'handoff' => false, 'reason' => null];
    }

    /**
     * Deteksi penanda "di luar topik" dari balasan model.
     *
     * Dicocokkan longgar (huruf besar, spasi, dan tanda markdown diabaikan)
     * karena beberapa versi model membungkus penandanya dengan `**` atau
     * `\[[ ... ]]`. Kalau terdeteksi, seluruh isi balasan dibuang: tidak ada
     * bagian yang ikut terkirim ke customer.
     *
     * Penolakan tanpa penanda (mis. "Maaf kak, saya tidak bisa membantu")
     * ikut dianggap handoff, karena kalau tidak customer tetap menerima
     * jawaban model untuk topik yang memang bukan urusan Rent Space.
     */
    private static function isOffTopic(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        $normalized = mb_strtolower(str_replace(['\\', ' ', '*', '`'], '', $text));
        if (str_contains($normalized, 'diluartopik')) {
            return true;
        }

        foreach (self::REFUSAL_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bersihkan jawaban AI biar tidak terlihat seperti bot.
     *
     * Model tetap suka membuka balasan dengan "Halo Kak {nama}, ..." di setiap
     * pesan dan kadang memuntahkan seluruh daftar unit. Dua hal itu yang bikin
     * customer komentar "kayak AI banget", jadi dibersihkan di sini (bukan
     * bergantung pada prompt saja).
     *
     * @param string|null $userMessage pesan customer yang memicu balasan ini.
     *        Dipakai sebagai cadangan saat riwayat obrolan kosong: sapaan tetap
     *        dibuang kecuali customer-nya memang cuma menyapa.
     */
    private static function tidyCustomerReply(string $text, bool $isFirstTurn, int $limit = 300, ?string $userMessage = null): string
    {
        $text = self::formatForWhatsApp($text);

        // 1. Buang sapaan pembuka kalau obrolan sudah berjalan, atau kalau
        //    customer-nya nanya sesuatu (bukan sekadar menyapa).
        //    Polanya sengaja ketat (sapaan + maksimal nama): kalau longgar,
        //    kalimat bermakna seperti "Maaf belum ada kak, PS3 saja..." ikut hilang.
        if (! $isFirstTurn || ! self::isGreetingOnlyMessage($userMessage)) {
            $stripped = preg_replace(
                '/^(?:halo|hai|hi|hei|permisi|assalamualaikum|salam|selamat\s+(?:pagi|siang|sore|malam))'
                . '(?:\s*(?:kak\w*|mas|mba|mba|bang|bro|om|bu|dadak|adyok|apak|teman|pak|dear)){0,2}'
                . '(?:\s+[A-Za-z][\w\'-]{1,20})?\s*[,!]\s*/iu',
                '',
                $text,
                1
            );
            if (is_string($stripped) && trim($stripped) !== '') {
                $text = ltrim($stripped);
            }
        }

        // 2. Buang ekor basa-basi (kalimat penutup template).
        $text = preg_replace(
            '/\s*(?:kalau|jika|bila)\b[^.!?\n]{0,80}(?:hubungi admin|terima kasih|🙏|😊)[^.!?\n]*[.!?]?\s*$/iu',
            '',
            $text
        ) ?? $text;
        $text = preg_replace(
            '/\s*(?:ada yang bisa dibantu (?:lagi|apapun)\??|ada lagi yang bisa dibantu\??|jika ada yang lain[^\n]*|butuh bantuan lagi\??|mau lihat unit yang tersedia\??|butuh info lainnya\??)\s*$/iu',
            '',
            $text
        ) ?? $text;
        $text = rtrim(trim($text), " \t\n,.;:-");

        // 3. Buang kebocoran data internal + rapikan artefak format.
        // "ada dua unit (ID: 8 dan ID: 17)" persis yang bikin customer bilang
        // "kok kayak AI banget", jadi ID internal tidak boleh lolos ke chat.
        $text = preg_replace(
            '/\s*\(?\bID:\s*\d+(?:\s*(?:dan|,&)\s*ID:\s*\d+)*\)?/iu',
            '',
            $text
        ) ?? $text;
        // "ya.PS3" -> "ya. PS3" (model kadang lupa spasi setelah titik).
        $text = preg_replace('/([.!?])([A-Z][a-z])/u', '$1 $2', $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([,.!?])/u', '$1', $text) ?? $text;
        $text = rtrim(trim($text), " \t\n,.;:-");

        // Huruf besar di awal kalimat, biar tidak terlihat seperti potongan-potongan.
        // Lewati nama brand (iPhone, PS3, QRIS, ...) supaya casing-nya tidak rusak.
        $firstWord = mb_strtolower(strtok($text, " \t\n,.:;!?") ?: '');
        $brands = ['iphone', 'ipad', 'imac', 'ps', 'ps3', 'ps4', 'qris', 'wa', 'ds', 'dll', 'usb', 'tv', 'kip', 'ovo', 'dana', 'gopay', 'link'];
        if (!in_array($firstWord, $brands, true) && preg_match('/^[a-z]/u', $text)) {
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        }

        // 4. Potong kalau masih kepanjangan, di batas kalimat terdekat.
        if (mb_strlen($text) > $limit) {
            $head = mb_substr($text, 0, $limit);
            $cut = max(
                (int) mb_strrpos($head, '. '),
                (int) mb_strrpos($head, '! '),
                (int) mb_strrpos($head, '? '),
                (int) mb_strrpos($head, "\n")
            );
            $text = $cut > 60
                ? rtrim(mb_substr($head, 0, $cut + 1))
                : rtrim(mb_substr($head, 0, $limit - 1)) . '…';
        }

        return trim($text) ?: $text;
    }

    /**
     * Pesan customer itu cuma sapaan, bukan pertanyaan.
     *
     * Cermin versi Node (`whatsapp-bot/lib/chat-humanizer.js`): pesan pendek,
     * tanpa angka, tanpa tanda tanya, dan semua kata-katanya cuma sapaan.
     * "halo kak ip 13 ready?" tetap dianggap pertanyaan, bukan sapaan.
     */
    private static function isGreetingOnlyMessage(?string $message): bool
    {
        $raw = mb_strtolower(trim((string) $message));
        if ($raw === '' || mb_strlen($raw) > 24) {
            return false;
        }
        if (preg_match('/\d/u', $raw) || preg_match('/[?？]/u', $raw)) {
            return false;
        }

        // Sama seperti versi Node: semua yang bukan huruf/angka diubah jadi
        // spasi dulu, baru dipecah per kata. Kalau langsung preg_split, spasi
        // ikut jadi pemisah dan "halo kak" terbaca sebagai satu kata.
        $words = preg_split(
            '/\s+/u',
            trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $raw) ?? ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];
        if (count($words) === 0 || count($words) > 3) {
            return false;
        }

        foreach ($words as $word) {
            if (! in_array($word, self::GREETING_WORDS, true)) {
                return false;
            }
        }

        return true;
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
        if ($isFollowUp && ! $needKatalog && ! $needJadwal && ! $needPengumuman) {
            return 'PANDUAN JAWABAN: pakai konteks obrolan sebelumnya, jangan mengulang daftar unit atau jadwal. Jawab langsung dan singkat.';
        }

        $block = '';

        if ($needKatalog) {
            $units = self::cached('cust_unit', 120, function () {
                $text = '';
                foreach (Unit::where('is_active', true)->with('category')->get() as $u) {
                    $cat = $u->category?->name ?? 'Unit';
                    $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24 jam' : '-';
                    $p12 = $u->harga_per_jam ? 'Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : '-';
                    // ID unit sengaja TIDAK ikut: itu kode internal, dan kalau
                    // muncul di jawaban customer jadi jelas "dijawab AI" sekaligus
                    // tidak ada artinya buat dia.
                    $text .= "- {$u->nama_lengkap} ({$cat}, {$p24} / {$p12})\n";
                }
                return $text;
            });
            if ($units !== '') {
                $block .= "DAFTAR UNIT & HARGA (pakai nama unit, jangan sebut ID internal):\n{$units}";
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

        // Ketersediaan ikut katalog juga: "ip 13 ready?" tidak mengandung kata
        // "hari", tapi tetap pertanyaan ketersediaan. Tanpa blok ini model
        // nebak dan pernah bilang "semua iPhone terpakai".
        if ($needKatalog || $needJadwal) {
            $block .= self::readyNowText($now);
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

    /**
     * Daftar unit yang benar-benar siap dipakai sekarang.
     *
     * Blok `STATUS JADWAL` di atas hanya memuat unit yang SEDANG dibooking, jadi
     * unit yang ready tidak pernah muncul di sana. Tanpa daftar ini model
     * menyimpulkan "semua iPhone terpakai" padahal ada yang kosong — persis
     * keluhan customer.
     *
     * Query-nya irit: satu kali ambil unit aktif + satu kali ambil booking yang
     * sedang menutup waktu sekarang, lalu dicocokkan di memori.
     */
    private static function readyNowText(\Carbon\Carbon $now): string
    {
        return self::cached('cust_ready', 60, function () use ($now) {
            try {
                $units = Unit::where('is_active', true)->orderBy('seri')->get();
                if ($units->isEmpty()) {
                    return "KETERSEDIAAN SEKARANG: belum ada unit aktif di toko.\n";
                }

                // Booking yang sedang berlaku. Toleransi 2 jam supaya unit yang
                // masih dipegang penyewa telat tidak ikut dihidupkan sebagai
                // "ready" (unit yang belum kembali bukan stok yang bisa dijual).
                $rentals = \App\Models\Rental::with('units')
                    ->whereIn('status', ['paid', 'renting', 'pending_confirmation'])
                    ->where('waktu_selesai', '>=', $now->copy()->subHours(2))
                    ->where('waktu_mulai', '<=', $now)
                    ->get();

                $occupied = [];
                foreach ($rentals as $r) {
                    foreach ($r->units as $u) {
                        $occupied[$u->id] = $r;
                    }
                }

                $siap = [];
                $dipakai = [];
                foreach ($units as $u) {
                    $nama = $u->nama_lengkap ?: $u->seri;
                    $r = $occupied[$u->id] ?? null;
                    if (!$r) {
                        $siap[] = $nama;
                        continue;
                    }
                    $sampai = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai) : null;
                    $label = $sampai ? 's/d ' . $sampai->translatedFormat('d M H:i') : '-';
                    if ($sampai && $sampai->lessThan($now)) {
                        $label .= ' (lewat jadwal, cek admin)';
                    }
                    $dipakai[] = "{$nama} ({$label})";
                }

                $text = 'KETERSEDIAAN SEKARANG (' . $now->translatedFormat('d M H:i') . ') — '
                    . count($siap) . ' dari ' . $units->count() . " unit siap dipakai:\n";
                $text .= $siap
                    ? '- ' . implode("\n- ", $siap) . "\n"
                    : "- (tidak ada unit yang kosong saat ini)\n";
                $text .= 'SEDANG DIPAKAI: ' . ($dipakai ? implode(', ', $dipakai) : 'tidak ada') . "\n";

                return $text;
            } catch (\Throwable $e) {
                Log::warning('GeminiAIService::readyNowText gagal: ' . $e->getMessage());
                return '';
            }
        });
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
        $text = "ATURAN KHUSUS TOKO (MEMORI RESMI - PRIORITAS PALING TINGGI):\n";
        foreach ($memories as $mem) {
            $k = is_array($mem) ? ($mem['key'] ?? '') : '';
            $v = is_array($mem) ? ($mem['value'] ?? '') : (string) $mem;
            $text .= $k && $v ? "- {$k}: {$v}\n" : ($v ? "- {$v}\n" : '');
        }
        $text .= "CATATAN: Jika customer menanyakan hal yang cocok dengan aturan khusus di atas, jawab sesuai instruksi tersebut (jangan tolak atau anggap di luar topik)!\n";
        return $text;
    }

    /**
     * Pangkas blok data berat dari prompt customer saat token/menit mepet.
     *
     * Yang dipangkas HANYA isi daftar unit dan status jadwal. Pertanyaan
     * customer, riwayat obrolan, dan seluruh aturan `CARA JAWAB` wajib tetap
     * ada: tanpa mereka model tidak tahu apa yang ditanya dan balasannya jadi
     * template ("Selamat siang, ada yang bisa dibantu?") yang justru bikin
     * kelihatan bot.
     *
     * Blok yang dipangkas diganti satu kalimat penanda supaya model tahu
     * datanya tidak lengkap — dan tidak boleh menebak-nebak status unit.
     */
    private static function stripCustomerData(string $prompt): string
    {
        // Aturan selalu utuh di bagian akhir prompt, jadi posisinya dipakai
        // sebagai batas: bagian depan dipangkas, bagian belakang disalin apa
        // adanya.
        $rulesPos = strpos($prompt, 'CARA JAWAB (WAJIB');
        $head = $rulesPos !== false ? substr($prompt, 0, $rulesPos) : $prompt;
        $rules = $rulesPos !== false ? substr($prompt, $rulesPos) : '';

        // Isi tiap blok data = deretan baris "- ...". Modifier `m` supaya `^`
        // nempel di awal tiap baris, bukan cuma di awal string. Header dan
        // baris lain (aturan toko, pengumuman, riwayat) tidak ikut tersentuh.
        foreach (['DAFTAR UNIT & HARGA', 'STATUS JADWAL UNIT'] as $header) {
            $head = preg_replace(
                '/(' . preg_quote($header, '/') . '[^\n]*\n)(?:^- [^\n]*\n?)+/m',
                '$1(daftar ini dipangkas demi batas token/menit)\n',
                $head
            ) ?? $head;
        }

        $note = "CATATAN PANGKASAN: daftar unit & status jadwal di atas dipangkas demi batas token/menit, jadi isinya TIDAK lengkap. Jangan menyimpulkan semua unit terpakai. Kalau customer menanyakan unit/jadwal yang tidak kamu lihat di sini, jawab sebatas yang kamu tahu lalu arahkan ke https://rentspacepurwokerto.my.id/booking atau balas ADMIN.\n";

        return $head . $note . ($rules !== '' ? "\n" . $rules : '');
    }

    /**
     * Generate jawaban AI untuk grup internal tim (akses penuh database).
     *
     * Dipanggil HANYA ketika bot di-tag (@mention) di grup report terdaftar.
     *
     * Tiga hal yang dijaga di sini:
     * 1. AKSES PENUH: SELURUH data bisnis ikut dikirim (jadwal, keterlambatan,
     *    inventaris + status tiap unit, transaksi, riwayat, denda). Tidak ada
     *    syarat kata kunci — tim bertanya dengan bahasa sehari-hari, dan jawaban
     *    "data tidak ikut dimuat, sebut kata kunci" dianggap bot tidak berguna.
     * 2. MEMORI: percakapan grup disimpan di tabel ai_messages, jadi pertanyaan
     *    lanjutan ("yang tadi itu gimana?", "trus si Rina telat berapa?") tetap nyambung
     *    walau sudah lewat jam atau bot restart.
     * 3. TOKEN: data tetap dibatasi plafond karakter dan tidak pernah boncos —
     *    kalau memang tidak kebudget, bagian yang paling relevan yang ditulis duluan.
     */
    public static function replyInternal(string $userMessage, string $askerName = 'Tim'): ?string
    {
        $apiKey = self::apiKeyFor('report');
        if (!$apiKey) {
            self::$lastError = 'API key laporan kosong';
            Log::warning('GeminiAIService::replyInternal: API key AI laporan kosong.');
            return self::fallbackMessage('report');
        }

        $model = self::modelFor('report');

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

        [$dataBlock, $loadedLabels, $overflowBlocks] = self::buildInternalData($userMessage, $intents, $now);

        $systemPrompt = "Kamu adalah asisten internal tim *Rent Space Purwokerto* yang|super pintar dan punya AKSES PENUH ke SELURUH data bisnis.
Waktu saat ini: {$currentTimeStr}.
Penanya dari dalam tim: {$askerName}.
Lokasi Toko: {$address}.

ATURAN PENTING (WAJIB DIPAATUHI):
1. Data di bawah ini adalah KEBENARAN. Jawab HANYA dari data tersebut, jangan mengarang nama, nomor, atau angka.
2. MEMORI: bagian \"YANG SUDAH DIPBAHAS\" adalah catatan percakapan sebelumnya dengan tim (kode booking, nama penyewa, topik). Pakai itu untuk menjawab pertanyaan lanjutan yang singkat, misalnya \"yang tadi\", \"trus dia\", \"yang nomor tadi\". Kalau pertanyaannya menyebut kode atau nama yang ada di memori, jawab langsung dari memori itu tanpa meminta penjelasan ulang.
3. DATA LENGKAP: semua bagian data bisnis sudah dimuat di bawah (jadwal hari ini, pengembalian, keterlambatan, inventaris + status siap tiap unit, transaksi, riwayat, denda, omset). Tidak ada kata kunci yang harus dipatuhi dan tidak ada bagian yang \"tidak ikut dimuat\" — JANGAN pernah meminta tim menyebutkan kata kunci atau menyebut kata kunci di jawaban. Kalau sebuah data memang tidak ada (mis. tidak ada penyewa yang terlambat), katakan memang tidak ada, itu jawaban yang benar.
4. STOK UNIT: untuk \"ip 13 ready?\", \"ada unit apa aja?\", \"unitnya lagi dipakai siapa?\" pakai bagian KETERSEDIAAN UNIT TOKO. \"SIAP DIPAKAI sekarang\" = ready, \"SEDANG DIPAKAI s/d ...\" = sedang disewa sampai jam itu, \"Berikutnya: ...\" = jadwal sewa berikutnya. Sebutkan nama unit persis seperti tertulis di data.
5. Kalau ditanya \"hari ini\" / \"siapa yang mau ambil\" / \"siapa yang balikin\", pakai bagian JADWAL PENGAMBILAN HARI INI dan JADWAL PENGEMBALIAN HARI INI (sudah mencakup SEMUA status: pending, sudah bayar, sedang disewa). Jangan menebak.
6. Kalau ditanya \"riwayat\", \"historical\", atau rentang tanggal (\"dari tgl 1 September sampai sekarang\", \"bulan lalu\"), pakai bagian RIWAYAT TRANSAKSI. bagian itu sudah memuat periode, total transaksi, jumlah penyewa berbeda, omset, daftar nama penyewa, dan detail transaksinya — jawab langsung dari sana, jangan bilang datanya tidak ada.
7. Kalau pertanyaan menyiratkan laporan harian, jawaban WAJIB memuat: jumlah penyewa yang ambil, jumlah yang balikin, dan status keterlambatan — lengkap dengan nama & jamnya.
8. Bahasa gaul tim (cuk, yg, trs/trus, ngambil, balikin, telat, denda, omset, cod, msh, blm, ready) adalah pertanyaan bisnis sungguhan, jawab dengan data.
9. Kalau tidak ada yang cocok, sebutkan apa yang ADA yang mendekati (\"yang paling mendekati: ...\"), jangan langsung menyerah.
10. Boleh tampilkan nama, nomor WA, alamat karena ini internal.
11. Format WA: pakai *tebal* (satu bintang) dan bullet -. Jangan pakai markdown lain.
12. Kata \"TERLAMBAT\" atau \"SUDAH MELEBIHI JADWAL\" berarti masalah nyata — wajib disebut di jawaban.
13. Jawaban internal to the point, tidak perlu basa-basi sapaan.
14. NOMINAL, BUKAN JUMLAH TRANSAKSI: kalau tim tanya \"berapa\", \"berapa duit\", \"nominalnya berapa\", atau menyebut status+periode (\"yg statusnya completed + paid bulan ini\"), jawaban WAJIB berisi ANGKA RUPIAH untuk status dan periode itu — boleh dijumlahkan sendiri dari rincian per status. JANGAN balas cuma jumlah transaksi (\"176 transaksi\") dan JANGAN menggantinya dengan ringkasan omset yang lain. Kalau status itu memang tidak ada transaksinya, sebutkan angkanya Rp 0.
15. KALAU ANGKA BEDA DENGAN DASHBOARD WEB: omset punya dua dasar, \"tanggal mulai sewa\" (waktu_mulai, angka bawaan) dan \"tanggal pembayaran\" (paid_at, angka yang sama dengan dashboard web). Jelaskan selisihnya sebagai perbedaan dasar itu. JANGAN mengarang alasan lain seperti \"total 30 hari terakhir\" atau \"statusnya belum completed\".

YANG SUDAH DIPBAHAS (MEMORI TIM):
" . ($memoryContext !== '' ? $memoryContext . "\n" : "(belum ada obrolan sebelumnya di grup ini)") . "
" . ($chatContext !== '' ? "\nOBROLAN TERAKHIR:\n" . $chatContext . "\n" : '') . "
DATA BISNIS LENGKAP (bagian yang tersedia: " . implode(', ', $loadedLabels) . "):
{$dataBlock}

Pertanyaan tim: \"{$userMessage}\"
Jawab sebagai asisten data internal:";

        $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        $budget = AiMemoryService::consumeTokenBudget('wa_group_report', $inputTokens);
        if (! $budget['ok']) {
            // Di grup report data TIDAK boleh dikorbankan: yang dipangkas hanya
            // catatan obrolan sebelumnya. Dikasih tahu lewat log, bukan lewat
            // jawaban ke WhatsApp, karena timnya memang minta akses penuh.
            Log::warning('GeminiAIService::replyInternal: obrolan sebelumnya dipangkas demi batas token/menit (data tetap lengkap).', $budget);
            $systemPrompt = self::shrinkInternalPrompt($systemPrompt);
            $inputTokens = AiMemoryService::estimateTokens($systemPrompt);
        }

        $text = self::askGemini('report', $systemPrompt, $model, 0.2, 900, 45);

        // Jaring pengaman: kalau jawaban pertama malah menyuruh tim memakai kata
        // kunci, kirim giliran kedua dengan bagian data yang tadi tidak kebudget.
        if ($text !== null && $overflowBlocks !== [] && self::isHedgingAnswer($text)) {
            $extra = mb_substr(implode("\n\n", $overflowBlocks), 0, 8000);
            $retryPrompt = $systemPrompt
                . "\n\nBAGIAN DATA TAMBAHAN (baru ditambahkan di giliran ini):\n{$extra}\n"
                . "JAWAB ULANG pertanyaan tim di atas memakai data ini juga. Jangan minta kata kunci, jangan bilang ada data yang tidak dimuat.";

            Log::info('GeminiAIService::replyInternal: jawaban pertama menyuruh pakai kata kunci, mencoba lagi dengan data tambahan.');
            $retry = self::askGemini('report', $retryPrompt, $model, 0.2, 900, 30);
            if ($retry !== null) {
                $text = $retry;
            }
        }

        if ($text !== null) {
            // 'cari' sering muncul karena heuristik kata, jadi tidak disimpan sebagai
            // konteks lanjutan; kalau memang ini pertanyaan soal orang, tetap disimpan.
            $carrySections = in_array($intent, ['cari', 'kode'], true)
                ? $intents['sections']
                : array_diff_key($intents['sections'], ['cari' => true]);

            AiMemoryService::saveTurn($conv, $userMessage, $text, $intent, $inputTokens, $askerName, $carrySections);
        }

        return $text ?? self::fallbackMessage('report');
    }

    /**
     * Susun draf pesan broadcast dari brief singkat milik admin.
     *
     * Dulu admin harus mengetik sendiri tiap kalimat promosi dari nol, itu
     * lambat dan gampang kelewatan detail. Sekarang cukup satu kalimat
     * ("promo gawaiipi 13, diskon 20% weekend ini") dan AI merangkai drafnya.
     *
     * Draf ini TIDAK langsung dikirim: hasilnya hanya diisi ke kolom pesan biar
     * admin tetap bisa baca dan ubah sebelum menekan tombol kirim. Katalog unit
     * ikut dikirim supaya harga yang muncul di copy bukan karangan.
     */
    public static function draftBroadcast(string $brief, string $tone = 'promosi'): ?string
    {
        $apiKey = self::apiKeyFor('broadcast');
        if (!$apiKey) {
            self::$lastError = 'API key broadcast kosong';
            Log::warning('GeminiAIService::draftBroadcast: API key AI broadcast kosong.');
            return null;
        }

        $model = self::modelFor('broadcast');
        $now = now();
        $address = Setting::getVal('admin_address', 'Purwokerto');
        $tones = [
            'promosi' => 'promosi yang menggoda dan singkat, gaya chat punches promotions',
            'info' => 'pengumuman resmi yang lugas dan singkat',
            'santai' => 'ngobrol santai, hangat, kayak CS balas pelanggan',
        ];
        $toneKey = array_key_exists($tone, $tones) ? $tone : 'promosi';

        $catalog = self::broadcastCatalogText();
        $knowledge = self::customKnowledgeText();

        $header = "Kamu copywriter WhatsApp untuk 'Rent Space Purwokerto' (rental iPhone, gadget, kamera, PS3).\n"
            . "Toko: {$address} | Booking: https://rentspacepurwokerto.my.id/booking\n"
            . "Waktu saat ini: " . $now->translatedFormat('l, d F Y H:i') . " WIB\n";

        $prompt = $header
            . ($knowledge !== '' ? "\nCATATAN TOKEN / PROMO BERLAKU:\n{$knowledge}\n" : '')
            . "\nKATALOG UNIT YANG BENERAN ADA (hanya boleh menyebut unit & harga dari daftar ini):\n{$catalog}\n"
            . "\nATURAN MENULIS:\n"
            . "1. Panjang 3-6 baris, maksimal sekitar 500 karakter. Ini dikirim massal, jadi jangan panjang.\n"
            . "2. Boleh dipakai markdown WhatsApp: *tebal* (satu bintang) dan - untuk bullet. Jangan pakai heading atau **.\n"
            . "3. DILARANG mengarang harga, promo, kode diskon, atau nama unit yang tidak ada di katalog. Kalau brief-nya minta info yang tidak ada di katalog, tulis tanpa angka itu dan sisipkan kalimat \"info lengkapnya chat admin\".\n"
            . "4. Gaya: {$tones[$toneKey]}. Bahasa Indonesia santai, sapaan 'Kak' boleh maksimal sekali di awal.\n"
            . "5. Emoji paling banyak 2 dan hanya di awal atau akhir. Jangan pakai emoji di tiap baris.\n"
            . "6. Akhiri dengan ajakan bertindak yang jelas (booking sekarang / chat admin), tanpa basa-basi.\n"
            . "7. Kembalikan HANYA teks pesan akhirnya. Tanpa pengantar, tanpa tanda kutip, tanpa catatan apa pun.\n"
            . "8. Kalau brief-nya terlalu singkat (mis. 'buatkan promo'), buat versi umum yang aman tanpa angka yang tidak ada di katalog.\n"
            . "\nBRIEF DARI ADMIN:\n" . mb_substr(trim($brief), 0, 1000)
            . "\n\nTulis pesan broadcast-nya sekarang:";


        $inputTokens = AiMemoryService::estimateTokens($prompt);
        AiMemoryService::consumeTokenBudget('wa_broadcast', $inputTokens);

        $text = self::askGemini('broadcast', $prompt, $model, 0.8, 400, 30);
        if ($text === null) {
            return null;
        }

        // Buang pengantar ala "Berikut draf pesan:" kalau model ternyata tetap
        // mengembalikannya meski sudah dilarang di prompt.
        $text = self::stripDraftPreamble($text);

        return $text;
    }

    /**
     * Buang baris pertama yang isinya cuma basa-basi pengantar.
     *
     * Baris pertama dibuang kalau setelah semua kata basa-basi dan tandanya
     * dihapus tidak tersisa satu huruf pun — jadi kalimat sungguhan yang kebetulan
     * diawali kata serupa ("Ini promo akhir bulan!") tetap utuh. Panjang baris
     * ikut dijaga supaya baris isi yang kata kuncinya kebetulan habis tidak
     * ikut terpotong.
     */
    private static function stripDraftPreamble(string $text): string
    {
        $filler = '/\b(berikut|inilah|ini|itu|oke|ok|siap|hai|halo|kak|dong|ya|draf|isi|naskah|hasil|contoh|versi|pesan|teks|content|broadcast|untuk|promosi|wa|adalah|yang|kopikan|seperti)\b|[^a-z0-9\n]/iu';

        $parts = preg_split('/\R/u', trim($text), 2);
        if (! is_array($parts) || count($parts) < 2) {
            return trim($text, " \t\n\r\"'`");
        }

        $first = trim($parts[0]);
        $residue = preg_replace($filler, '', $first) ?? '';

        if ($residue === '' && mb_strlen($first) <= 60) {
            $text = $parts[1];
        }

        return trim($text, " \t\n\r\"'`");
    }

    /**
     * Daftar unit + harga untuk merangkai draf broadcast.
     *
     * Sengaja jauh lebih ramping daripada konteks internal: broadcast cuma perlu
     * nama unit dan harga supaya copy-nya tidak mengarang, bukan status
     * ketersediaan per jam.
     */
    private static function broadcastCatalogText(): string
    {
        $text = self::cached('broadcast_catalog', 300, function () {
            $units = Unit::where('is_active', true)->with('category')->orderBy('nama_lengkap')->limit(60)->get();
            if ($units->isEmpty()) {
                return "(belum ada unit aktif terdaftar)\n";
            }

            $lines = '';
            foreach ($units as $u) {
                $harga = [];
                if ($u->harga_per_hari) {
                    $harga[] = '24 jam Rp ' . number_format($u->harga_per_hari, 0, ',', '.');
                }
                if ($u->harga_per_jam) {
                    $harga[] = '12 jam Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.');
                }
                $lines .= '- ' . ($u->nama_lengkap ?: $u->seri)
                    . ' (' . ($u->category?->name ?? 'Unit') . ')'
                    . ($harga ? ': ' . implode(', ', $harga) : '')
                    . "\n";
            }

            return $lines . "\n";
        });

        return $text !== '' ? $text : "(belum ada unit aktif terdaftar)\n";
    }

    /**
     * Panggil API dengan prompt sependek mungkin untuk memeriksa kunci & model.
     *
     * Dipakai tombol "Tes" di Pengaturan: tanpa ini, kunci yang salah ketik baru
     * ketahuan setelah bot gagal menjawab di depan customer.
     */
    public static function testKey(string $feature): array
    {
        return self::testKeySlot($feature, 1);
    }

    /**
     * Tes satu slot kunci tertentu, bukan sekadar slot 1.
     *
     * Penting untuk pool: kalau hanya slot 1 yang dites, admin tidak pernah tahu
     * bahwa slot 3 berisi kunci project yang sudah mati. Slot yang dites boleh
     * yang sedang cooldown — justru itu yang ingin dicek.
     */
    public static function testKeySlot(string $feature, int $slot = 1): array
    {
        $slot = max(1, min(self::KEY_POOL_SIZE, $slot));
        $apiKey = trim((string) Setting::getVal(self::keySlotName($feature, $slot), ''));
        if ($apiKey === '' && $slot === 1) {
            $apiKey = (string) (self::apiKeyFor($feature) ?: '');
        }

        if ($apiKey === '') {
            return ['ok' => false, 'message' => "Kunci slot {$slot} masih kosong."];
        }

        $model = self::modelFor($feature);
        $text = self::probeKey($apiKey, $model);

        if ($text === null) {
            return ['ok' => false, 'message' => 'Gagal: ' . (self::$lastError ?: 'tidak diketahui')];
        }

        return ['ok' => true, 'message' => "Kunci slot {$slot} valid, model {$model} merespons (HTTP OK)."];
    }

    /**
     * Satu panggilan kecil ke satu kunci tertentu, tanpa failover.
     *
     * Sengaja tidak memakai askGemini(): tes harus isolating kunci yang diklik,
     * bukan diam-diam pindah ke kunci lain lalu dilaporkan "valid".
     */
    private static function probeKey(string $apiKey, string $model): ?string
    {
        self::$lastError = null;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => 'Balas dengan satu kata: SIAP']]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 16,
                        'thinkingConfig' => [
                            'thinkingBudget' => 0,
                        ],
                    ],
                ]
            );

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (! empty($candidates[0]['content']['parts'][0]['text'])) {
                    return trim($candidates[0]['content']['parts'][0]['text']);
                }

                self::$lastError = 'jawaban kosong dari model ' . $model;

                return null;
            }

            $status = $response?->status() ?? 0;
            self::$lastError = 'HTTP ' . $status . ' — ' . self::readApiError((string) $response?->body());
        } catch (\Throwable $e) {
            self::$lastError = self::maskSecrets($e->getMessage());
        }

        return null;
    }

    /**
     * Pengaman terakhir kalau datanya fantastis (mis. 100 ribu transaksi). Konteks
     * model yang dipakai menerima 1 juta token, jadi angka ini masih jauh di bawahnya.
     */
    private const INTERNAL_DATA_HARD_CAP = 200000;

    /**
     * Plafon karakter data grup report, dibaca dari Pengaturan.
     *
     * 0 = TANPA BATAS (default). Di grup report tim ingin akses penuh: semua
     * transaksi, semua unit, semua riwayat ikut terpakai, bukan cuma bagian yang
     * kata kuncinya kena. Jadi angka default-nya 0, bukan pluckingan karakter.
     */
    private static function internalDataBudget(): int
    {
        $limit = (int) Setting::getVal('chatbot_report_data_limit', '0');

        if ($limit <= 0) {
            return self::INTERNAL_DATA_HARD_CAP;
        }

        return min($limit, self::INTERNAL_DATA_HARD_CAP);
    }

    /**
     * Pemicu kata -> bagian data yang DIPRIORITASKAN (urutan tulis), bukan syarat dimuat.
     *
     * Tim ngomong bebas ("ip 13 ready??", "hari ini yg ambil siapa aja"), jadi
     * kata kunci tidak boleh menentukan apakah data ikut atau tidak. Yang dia
     * lakukan cuma mengurutkan: kalau data harus dipangkas, bagian yang paling
     * relevan tetap ditulis paling dulu.
     */
    private const INTERNAL_SECTION_RULES = [
        'kembali' => ['balikin', 'kembali', 'kembalikan', 'pengembalian', 'pulang', 'balik', 'ngembal'],
        'terlambat' => ['telat', 'terlambat', 'lewat jadwal', 'mewati', 'overtime', 'nyusul', 'keterlambatan'],
        'denda' => ['denda', 'rusak', 'kerusakan', 'damage', 'fine', 'bayar denda'],
        'riwayat' => ['riwayat', 'pernah', 'dulu', 'histor', 'historis', 'sejak', 'dari tgl', 'dari tanggal', 's/d', 's.d.', 'bulan lalu', 'tahun lalu', 'semua transaksi'],
        'pending' => ['pending', 'belum bayar', 'blm bayar', 'gak bayar', 'nggak bayar', 'menunggu', 'nunggu', 'konfirmasi'],
        'aktif' => ['sedang disewa', 'lagi dipakai', 'sedang dipakai', 'aktif', 'sibuk', 'yang jalan'],
        'jadwal' => ['hari ini', 'hr ini', 'ambil', 'ngambil', 'pengambilan', 'jadwal', 'datang', 'mampir', 'besok'],
        'cari' => ['transaksi', 'rental', 'booking', 'penyewa', 'cari', 'siapa'],
        'unit' => ['unit', 'iphone', 'ipho', 'ip ', 'hp', 'kamera', 'harga', 'katalog', 'daftar', 'stok', 'ada unit', 'series', 'ready', 'siap', 'inventaris', 'tersedia'],
        'pendapatan' => ['omset', 'profit', 'pendapatan', 'pemasukan', 'revenue', 'uang masuk', 'berapa total', 'duit', 'nominal', 'uangnya', 'berapa rp', 'rp berapa'],
    ];

    /**
     * Kata status transaksi — dipakai untuk deteksi "duit per status".
     */
    private const STATUS_WORDS = [
        'completed', 'selesai', 'paid', 'bayar', 'renting', 'disewa', 'dibatalkan',
        'cancelled', 'pending', 'menunggu', 'status',
    ];

    /**
     * Kata "minta angka" — disandingkan dengan STATUS_WORDS.
     */
    private const ASK_MONEY_WORDS = ['berapa', 'nominal', 'duit', 'jumlah', 'total', 'rp'];

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
        $sections['cari'] = ($sections['cari'] ?? false) || self::detectNameToken($question) !== null;

        // "yg statusnya completed + paid jadi berapa" = pertanyaan uang per status,
        // walau tidak ada kata "omset". Kalau tidak dikenali di sini, rincian per
        // status tenggelam di urutan prioritas dan bot hanya bisa balas jumlah transaksi.
        if (! ($sections['pendapatan'] ?? false) && self::looksLikeStatusMoneyQuestion($q)) {
            $sections['pendapatan'] = true;
        }

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
     * True kalau pertanyaannya menyebut status transaksi DAN minta angka.
     *
     * Dipakai hanya untuk mengurutkan blok data, jadi cukup konservatif: cukup
     * satu kata status dan satu kata tanya angka.
     */
    private static function looksLikeStatusMoneyQuestion(string $q): bool
    {
        $hasStatus = false;
        foreach (self::STATUS_WORDS as $word) {
            if (str_contains($q, $word)) {
                $hasStatus = true;
                break;
            }
        }

        if (! $hasStatus) {
            return false;
        }

        foreach (self::ASK_MONEY_WORDS as $word) {
            if (str_contains($q, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kandidat kode booking yang disebut tim.
     *
     * Kode booking di sistem ini = 12 karakter acak (huruf + angka), jadi filternya
     * ketat. Kalau longgar, kata biasa seperti "storefront" ikut terambil dan kita
     * membuang token untuk mencari data yang jelas tidak ada.
     *
     * @return array<int,string>
     */
    private static function extractCodeCandidates(string $question): array
    {
        $upper = mb_strtoupper($question);
        preg_match_all('/\b[A-Z0-9-]{8,20}\b/u', $upper, $m);

        $codes = [];
        foreach (array_unique($m[0] ?? []) as $token) {
            $plain = str_replace('-', '', $token);
            $len = strlen($plain);

            $hasDigit = (bool) preg_match('/\d/', $plain);
            $hasLetter = (bool) preg_match('/[A-Z]/', $plain);

            // Format 12 karakter huruf+angka, atau format berawalan "RS-".
            $isCode = ($len >= 10 && $len <= 14 && $hasDigit && $hasLetter)
                || (str_starts_with($token, 'RS-') && $len >= 5);

            if ($isCode) {
                $codes[] = $token;
            }
        }

        return array_slice($codes, 0, 3);
    }

    /**
     * Kata tanya/biasa yang sering muncul tapi BUKAN nama penyewa.
     * Tanpa daftar ini, kata seperti "gimana" atau "storefront" dianggap nama
     * dan AI sia-sia mencari transaksi yang jelas tidak ada.
     */
    private const NON_NAME_WORDS = [
        'gimana', 'kenapa', 'begitu', 'begini', 'seharusnya', 'mungkin', 'kadang',
        'banyak', 'semua', 'tersedia', 'ready', 'statusnya', 'laporannya', 'laporan',
        'rekap', 'rekapnya', 'summary', 'ringkasan', 'translate', 'fix', 'bener',
        // Kata yang muncul di pertanyaan periode: jangan dianggap nama penyewa.
        'september', 'agustus', 'oktober', 'november', 'desember', 'januari',
        'februari', 'maret,', 'april', 'agustus,', 'tanggal', 'histor', 'historis',
        'historical', 'sejak',         'sampe', 'sampai', 'skrng', 'sekarang,', 'daftar', 'list', 'lists',
        'namanya', 'mulyadi', 'bula', 'tgl', 'bln', 'thn',
        'riwayat', 'pernah', 'dulu', 'datang', 'lompat', 'gas', 'poll',
        'lalu', 'terakhir', 'sejak', 'transaksi', 'sewa', 'unit',
        // Akibat dari keywords/tanya yang sering nempel: "sekarang" (bukan "sekarang,"),
        // "omsetnya", dan nama-nama barang. Kalau tidak, pencarian penyewa meleset ke
        // kata benda dan jawabannya Berubah jadi "tidak ada transaksi yang cocok".
        'sekarang', 'skrg', 'omsetnya', 'pendapatan', 'pemasukan', 'profit', 'revenue',
        'harga', 'harganya', 'stok', 'inventaris', 'katalog', 'unitnya', 'apanya', 'bisa', 'boleh',
        'iphone', 'ipho', 'ipad', 'imac', 'android', 'samsung', 'canon', 'nikon', 'sony',
        'playstation', 'ps3', 'ps4', 'ps5', 'kamera', 'lensa', 'drone', 'gopro', 'macbook',
        'hari', 'kemarin', 'pagi', 'siang', 'sore', 'malam', 'minggu', 'pekan', 'seminggu',
    ];

    /**
     * Nama bulan (Indonesia + Inggris) -> nomor bulan, buat deteksi rentang tanggal.
     */
    private const MONTH_WORDS = [
        'januari' => 1, 'jan' => 1, 'january' => 1,
        'februari' => 2, 'feb' => 2, 'february' => 2,
        'maret' => 3, 'mar' => 3, 'march' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'jun' => 6, 'june' => 6,
        'juli' => 7, 'jul' => 7, 'july' => 7,
        'agustus' => 8, 'agu' => 8, 'august' => 8, 'ags' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11, 'nopember' => 11,
        'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
    ];

    /**
     * Tebak rentang tanggal dari kalimat tim, lalu kembalikan [start, end, label].
     *
     * Dipakai untuk pertanyaan "dari tgl 1 September sampai sekarang" / "bulan lalu" /
     * "tahun 2025". Tanpa ini, AI cuma bisa bilang "tidak ikut dimuat" padahal
     * datanya ada.
     *
     * @return array{0:\Carbon\Carbon,1:\Carbon\Carbon,2:string}|null
     */
    private static function parseDateRange(string $question, \Carbon\Carbon $now): ?array
    {
        $q = mb_strtolower(self::stripInvisible($question));

        // "hari ini" / "kemarin" / "n hari terakhir"
        if (preg_match('/(\d{1,3})\s*hari\s+(terakhir|keduaan)/u', $q, $m)) {
            $n = max(1, (int) $m[1]);
            $start = $now->copy()->subDays($n - 1)->startOfDay();
            return [$start, $now->copy()->endOfDay(), $n . ' hari terakhir (' . $start->translatedFormat('d M') . ' - ' . $now->translatedFormat('d M Y') . ')'];
        }
        if (str_contains($q, 'hari ini')) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hari ini (' . $now->translatedFormat('d M Y') . ')'];
        }
        if (str_contains($q, 'kemarin')) {
            $y = $now->copy()->subDay();
            return [$y->copy()->startOfDay(), $y->copy()->endOfDay(), 'kemarin (' . $y->translatedFormat('d M Y') . ')'];
        }

        // "tahun ini" / "tahun lalu" / "tahun 2025"
        $year = null;
        if (preg_match('/tahun\s*(\d{4})/u', $q, $m)) {
            $year = (int) $m[1];
        } elseif (str_contains($q, 'tahun lalu')) {
            $year = $now->year - 1;
        } elseif (str_contains($q, 'tahun ini')) {
            $year = $now->year;
        }
        if ($year !== null) {
            $start = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
            $end = \Carbon\Carbon::create($year, 12, 31)->endOfDay();
            return [$start, $end, 'tahun ' . $year];
        }

        // "bulan lalu" / "bulan ini" / "bulan September"
        // Tim sering ketik "bula" (tanpa n), jadi dua-duanya diterima.
        // Tim juga sering menulis "dari tgl 1 September" tanpa kata "bulan",
        // jadi nama bulan dicari di seluruh kalimat kalau pola "bulan X" tidak kena.
        $month = null;
        $named = null;
        if (preg_match('/bulan?\s+([a-z]{3,9})/u', $q, $m)) {
            $word = mb_substr($m[1], 0, 9);
            $named = self::MONTH_WORDS[$word] ?? self::MONTH_WORDS[mb_substr($word, 0, 3)] ?? null;
        } elseif (preg_match('/\b(januari|jan|februari|feb|maret|mar|april|apr|mei|may|juni|jun|juli|jul|agustus|agu|august|ags|september|sept|sep|oktober|okt|oct|november|nov|desember|dec|december)\b/u', $q, $m)) {
            $word = $m[1];
            $named = self::MONTH_WORDS[$word] ?? null;
        }
        // $relatif = bulan dihitung dari posisi sekarang ("bulan lalu"), jadi
        //	tahunnya sudah pasti dan tidak boleh digeser lagi di bawah.
        $relatif = false;
        $tahunRelatif = null;
        if ($named !== null) {
            $month = $named;
        } elseif (str_contains($q, 'bulan lalu')) {
            $month = $now->month === 1 ? 12 : $now->month - 1;
            $relatif = true;
            if ($now->month === 1) {
                $tahunRelatif = $now->year - 1;
            }
        } elseif (str_contains($q, 'bulan ini') || str_contains($q, 'bulan sekarang')) {
            $month = $now->month;
            $relatif = true;
        }

        if ($month === null) {
            return null;
        }

        // Tahun: default tahun berjalan, tapi bulan yang sudah lewat pakai tahun lalu.
        $tahun = $now->year;
        if (preg_match('/\b(20\d{2})\b/u', $q, $m)) {
            $tahun = (int) $m[1];
        } elseif ($relatif) {
            $tahun = $tahunRelatif ?? $now->year;
        } elseif ($month < $now->month) {
            $tahun = $now->year - 1;
        }

        $start = \Carbon\Carbon::create($tahun, $month, 1)->startOfDay();
        $end = \Carbon\Carbon::create($tahun, $month, 1)->endOfMonth()->endOfDay();

        // "dari tgl 1 September" -> mulai tanggal tersebut, "sampai tgl 15" -> dipotong.
        if (preg_match('/(?:tgl|tanggal|dr)\s*(\d{1,2})\b/u', $q, $m) && $month === $now->month) {
            $start = \Carbon\Carbon::create($tahun, $month, max(1, (int) $m[1]))->startOfDay();
        }
        if (preg_match('/(?:sampai|smpe|sm|s\/d|s\.d\.)\s*(?:tgl\s*)?(\d{1,2})\b/u', $q, $m)) {
            $d = (int) $m[1];
            if ($d >= 1 && $d <= 31) {
                $end = \Carbon\Carbon::create($tahun, $month, $d)->endOfDay();
            }
        }

        $bulanIndo = $start->translatedFormat('F Y');

        // "sampai sekarang" = jangan lewat hari ini.
        if (preg_match('/sampai|smpe|skrng|s\.d\.|sekarang|now/u', $q)) {
            $end = min($end, $now->copy()->endOfDay());
        }

        return [$start, $end, '1 ' . $bulanIndo . ($end->lt($start->copy()->endOfMonth()) ? ' - ' . $end->translatedFormat('d M Y') : '')];
    }

    /**
     * Kata pertama di pertanyaan yang panjang dan layak dianggap nama penyewa.
     */
    private static function detectNameToken(string $question): ?string
    {
        $tokens = preg_split('/[^a-z]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) < 4) {
                continue;
            }
            if (in_array($t, self::NAME_STOPWORDS, true) || in_array($t, self::NON_NAME_WORDS, true)) {
                continue;
            }
            return $t;
        }
        return null;
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
     * Pecah omset per status untuk satu cakupan query: jumlah transaksi + nominal.
     *
     * Tanpa angka per status, pertanyaan "yg statusnya completed + paid berapa?"
     * tidak punya apa-apa untuk dijawab, jadi AI balas jumlah transaksi lalu
     * mengulang ringkasan omset. Satu query sudah cukup untuk dua-duanya.
     *
     * @param  callable():\Illuminate\Database\Eloquent\Builder  $scope
     * @return array<string,array{trx:int,rp:int}>  status => ['trx' => n, 'rp' => nominal]
     */
    private static function revenueByStatus(callable $scope, array $skip = []): array
    {
        $rows = $scope()
            ->selectRaw('status, COUNT(*) as trx, SUM(COALESCE(grand_total, subtotal_harga, 0)) as rp')
            ->groupBy('status')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (in_array($row->status, $skip, true)) {
                continue;
            }
            $out[$row->status] = ['trx' => (int) $row->trx, 'rp' => (int) $row->rp];
        }

        return $out;
    }

    /**
     * Rincian omset per status: bulan ini, bulan ini versi dashboard web, dan
     * seumur hidup.
     *
     * Ini yang menjawab "yg statusnya completed + paid bulan ini berapa?".
     * Sebelumnya data ini tidak ada sama sekali, jadi bot tidak punya angka
     * untuk dijawab — dia balas jumlah transaksi ("176 transaksi") lalu
     * mengulang ringkasan omset, padahal yang ditanya nominalnya.
     *
     * @param  array<string,string>  $statusIndo
     */
    private static function statusRevenueText(\Carbon\Carbon $now, array $statusIndo): string
    {
        $bulan = $now->translatedFormat('F Y');

        // Status yang dihitung sebagai omset. Status "menunggu" sengaja TIDAK
        // masuk supaya totalnya sama persis dengan "Omset bulan ini" di ringkasan;
        // kalau ikut, dua blok jadi beda dan bot melaporkan angka yang salah.
        $statusOmset = ['paid', 'renting', 'completed'];
        $statusBelumBayar = ['pending', 'pending_confirmation'];

        // Satu query per cakupan, lalu dipakai untuk total maupun rinciannya.
        $rekap = function (callable $scope) use ($statusIndo, $statusOmset, $statusBelumBayar) {
            $rows = self::revenueByStatus($scope, ['cancelled']);

            $total = 0;
            foreach ($rows as $status => $row) {
                if (in_array($status, $statusOmset, true)) {
                    $total += $row['rp'];
                }
            }

            return [
                'total' => 'Rp ' . number_format($total, 0, ',', '.'),
                'omset' => self::revenueByStatusLine($rows, $statusIndo, $statusOmset),
                'belumBayar' => self::revenueByStatusLine($rows, $statusIndo, $statusBelumBayar),
            ];
        };

        $mulai = $rekap(fn () => \App\Models\Rental::whereYear('waktu_mulai', $now->year)
            ->whereMonth('waktu_mulai', $now->month));

        $bayar = $rekap(fn () => \App\Models\Rental::whereBetween('paid_at', [
            $now->copy()->startOfMonth(), $now->copy()->endOfMonth(),
        ]));

        $semua = $rekap(fn () => \App\Models\Rental::query());

        $text = "Total bulan ini ({$bulan}): dari tanggal mulai sewa {$mulai['total']}"
            . " | versi dashboard web (dari tanggal pembayaran) {$bayar['total']}\n";
        $text .= "Bulan ini ({$bulan}) per status, dari tanggal mulai sewa: {$mulai['omset']}\n";
        $text .= "Bulan ini ({$bulan}) per status, versi dashboard web (dari tanggal pembayaran): {$bayar['omset']}\n";
        $text .= "Belum dibayar bulan ini ({$bulan}, bukan omset): {$mulai['belumBayar']}\n";
        $text .= "Semua waktu per status, dari tanggal mulai sewa: {$semua['omset']}\n";

        return $text;
    }

    /**
     * Rincian omset per status dalam satu baris: "SELESAI: 12 trs / Rp 5.000.000".
     *
     * Status dengan nominal 0 dilewati supaya barisnya tidak rame di prompt.
     * Urutan status mengikuti $statusIndo supaya konsisten dengan blok lain.
     * $only membatasi status yang ditampilkan (kosong = semua).
     *
     * @param  array<string,array{trx:int,rp:int}>  $byStatus
     * @param  array<int,string>  $only
     */
    private static function revenueByStatusLine(array $byStatus, array $statusIndo, array $only = []): string
    {
        $parts = [];
        foreach ($statusIndo as $status => $label) {
            if ($only !== [] && ! in_array($status, $only, true)) {
                continue;
            }

            $row = $byStatus[$status] ?? null;
            if ($row === null || $row['rp'] <= 0) {
                continue;
            }
            $parts[] = "{$label}: {$row['trx']} trs / Rp " . number_format($row['rp'], 0, ',', '.');
        }

        return $parts === [] ? 'belum ada' : implode(' | ', $parts);
    }

    /**
     * Susun blok data untuk tim: SEMUA bagian data bisnis, tanpa syarat kata kunci.
     *
     * Dulu bagian data hanya dimuat kalau pertanyaan memuat kata kuncinya, jadi
     * "ip 13 ready??" dijawab "inventaris tidak ikut dimuat, sebut kata kunci".
     * Sekarang semua bagian selalu ikut; kata kuncinya cuma dipakai untuk MENGURUTKAN
     * mana yang ditulis duluan, sehingga kalau data harus dipangkas, bagian yang paling
     * relevan tetap ada. Bagian yang tidak kebudget dikembalikan terpisah supaya
     * bisa dicoba lagi (lihat retry di replyInternal).
     *
     * @return array{0:string,1:array<int,string>,2:array<int,string>} [teks data, label yang dimuat, teks bagian yang tidak kebudget]
     */
    private static function buildInternalData(string $question, array $intents, \Carbon\Carbon $now): array
    {
        $s = $intents['sections'];

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

            // Catatan: angka-angka di sini dihitung dari TANGGAL MULI SEWA.
            // Dashboard web menghitung dari TANGGAL PEMBAYARAN (paid_at), jadi
            // angkanya selalu beda sedikit. Kedua angka dimuat di blok OMSET PER
            // STATUS — kalau tidak, tim membandingkan angka bot dengan angka web
            // lalu AI mengarang alasan yang ngawur ("total 30 hari terakhir").
            $lines[] = '- Omset hari ini: Rp ' . number_format($profitToday, 0, ',', '.');
            $lines[] = '- Omset bulan ini (' . $now->translatedFormat('F Y') . '): Rp ' . number_format($profitMonth, 0, ',', '.');
            $lines[] = '- Omset total: Rp ' . number_format($profitAll, 0, ',', '.');

            return implode("\n", $lines);
        });

        $collected = [];   // [prioritas, label, isi] — dikumpulkan dulu

        $add = function (string $label, string $key, callable $builder, int $priority) use (&$collected) {
            $text = trim(self::cached('sec_' . $key, 45, $builder));
            if ($text === '') {
                return;
            }

            $collected[] = [$priority, $label, $text];
        };

        $add('RINGKASAN & OMSET', 'ringkasan', fn () => $snapshot, 1);

        // Nominal per status. Berdiri sendiri (bukan di dalam ringkasan) supaya
        // ringkasan tetap ramping, tapi diprioritaskan kalau pertanyaannya
        // memang soal uang per status.
        $add('OMSET PER STATUS (nominal tiap status)', 'omset_per_status', fn () => self::statusRevenueText($now, $statusIndo),
            ($s['pendapatan'] ?? false) ? 1 : 3);

        // Kode booking yang disebut = sumber data paling presisi, selalu paling depan.
        $add('DETAIL KODE BOOKING YANG DITANYAKAN', 'kode_' . md5($question), function () use ($question, $statusIndo) {
            $codes = self::extractCodeCandidates($question);
            if (empty($codes)) {
                return '';
            }
            $rows = \App\Models\Rental::with('units')
                ->whereIn('booking_code', $codes)
                ->orderByDesc('waktu_mulai')
                ->limit(5)->get();
            if ($rows->isEmpty()) {
                return "Kode yang disebut tidak ada di database.\n";
            }
            return $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
        }, ($s['kode'] ?? false) ? 1 : 4);

        // Pencarian nama penyewa: hanya ada isinya kalau pertanyaan memang menyebut nama.
        // Tanpa cek ini, kata umum seperti "gimana" ikut di-LIKE ke tabel rental dan
        // muncul sebagai "tidak ada transaksi yang cocok" yang hanya membingungkan.
        $nameToken = self::detectNameToken($question);
        $add('PENCARIAN DATA PENYEWA (untuk soal orang tertentu)', 'cari_' . md5($question), function () use ($question, $nameToken) {
            if ($nameToken === null) {
                return '';
            }
            $text = self::lookupRentalsByName($question);
            return str_contains($text, 'Tidak ada kata kunci nama') ? '' : $text;
        }, $nameToken !== null ? 1 : 9);

        // Inventaris + status siap per unit. Inilah yang jawab "ip 13 ready??" tanpa
        // perlu kata kunci, karena daftar unit TIDAK lagi terpisah dari status pakainya.
        $add('KETERSEDIAAN UNIT TOKO (stok & status siap)', 'ketersediaan', function () use ($now) {
            return self::unitAvailabilityText($now);
        }, ($s['unit'] ?? false) ? 1 : 2);

        $add('JADWAL PENGAMBILAN HARI INI', 'jadwal', function () use ($todayStart, $todayEnd, $statusIndo) {
            $rows = \App\Models\Rental::with(['units'])
                ->whereIn('status', ['pending', 'pending_confirmation', 'paid', 'renting'])
                ->whereBetween('waktu_mulai', [$todayStart, $todayEnd])
                ->orderBy('waktu_mulai')
                ->limit(20)->get();
            return $rows->isEmpty()
                ? "Tidak ada pengambilan yang dijadwalkan hari ini.\n"
                : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
        }, ($s['jadwal'] ?? false) || ($s['terlambat'] ?? false) ? 1 : 2);

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
                if ($r->status === 'completed' && $r->completed_at) {
                    $suffix = ' | Sudah dikembalikan pada: ' . \Carbon\Carbon::parse($r->completed_at)->translatedFormat('d M H:i');
                } elseif ($r->waktu_selesai && \Carbon\Carbon::parse($r->waktu_selesai)->isPast() && $r->status === 'renting') {
                    $suffix = ' *** SUDAH MELEBIHI JADWAL ' . self::minutesLate($now, $r->waktu_selesai) . ' MENIT ***';
                } elseif ($r->handed_over_at) {
                    $suffix = ' | Unit sudah diambil customer sejak: ' . \Carbon\Carbon::parse($r->handed_over_at)->translatedFormat('d M H:i');
                }
                return self::rentalLine($r, $statusIndo, $suffix);
            })->implode('');
        }, ($s['kembali'] ?? false) || ($s['terlambat'] ?? false) ? 1 : 2);

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
        }, ($s['terlambat'] ?? false) ? 1 : 3);

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
        }, ($s['denda'] ?? false) ? 3 : 7);

        $add('BOOKING MENUNGGU PENGAMBILAN (belum bayar)', 'pending', function () use ($statusIndo) {
            $rows = \App\Models\Rental::with(['units'])
                ->where('status', 'pending')
                ->orderBy('waktu_mulai')
                ->limit(12)->get();
            return $rows->isEmpty()
                ? "Tidak ada booking yang menunggu pengambilan.\n"
                : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
        }, ($s['pending'] ?? false) || ($s['jadwal'] ?? false) ? 3 : 6);

        $add('UNIT YANG SEDANG DISEWA', 'aktif', function () use ($statusIndo) {
            $rows = \App\Models\Rental::with(['units'])
                ->where('status', 'renting')
                ->where('waktu_selesai', '>=', now())
                ->orderBy('waktu_selesai')
                ->limit(20)->get();
            return $rows->isEmpty()
                ? "Tidak ada unit yang sedang disewa saat ini.\n"
                : $rows->map(fn ($r) => self::rentalLine($r, $statusIndo))->implode('');
        }, ($s['aktif'] ?? false) ? 3 : 5);

        // Riwayat: kalau tim menyebut periode, periodenya dipakai; kalau tidak,
        // tetap 30 hari terakhir supaya "berapa omset bulan ini" ada jawabannya.
        $range = self::parseDateRange($question, $now);
        [$rStart, $rEnd, $rLabel] = $range ?? [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay(), '30 hari terakhir'];

        $add('RIWAYAT TRANSAKSI — ' . mb_strtoupper($rLabel), 'riwayat_' . $rStart->format('Ymd') . '_' . $rEnd->format('Ymd'), function () use ($rStart, $rEnd, $rLabel, $statusIndo) {
            $base = fn () => \App\Models\Rental::whereBetween('waktu_mulai', [$rStart, $rEnd])
                ->whereNotIn('status', ['cancelled']);

            // Hitungan & omset dihitung dari seluruh periode, bukan dari 30 baris
            // yang ditampilkan — kalau tidak, totalnya jadi tidak akurat.
            $totalTransaksi = (int) $base()->count();
            $jumlahNama = (int) $base()->whereNotNull('nama')->where('nama', '!=', '')->distinct()->count('nama');
            $omset = (int) $base()->whereIn('status', ['renting', 'paid', 'completed'])
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(grand_total, subtotal_harga)'));

            $rows = $base()->with('units')->orderByDesc('waktu_mulai')->limit(30)->get();

            if ($rows->isEmpty()) {
                return "Tidak ada transaksi pada rentang {$rLabel}.\n";
            }

            // Komposisi status dihitung dari seluruh periode, bukan 30 baris tampil.
            // Nominal per status ikut dalam satu query yang sama: tanpa itu,
            // "yang statusnya completed + paid periode ini berapa?" cuma bisa
            // dijawab pakai jumlah transaksi.
            $byStatus = self::revenueByStatus($base);
            $summary = [];
            foreach ($statusIndo as $st => $label) {
                if (! isset($byStatus[$st])) {
                    continue;
                }
                $row = $byStatus[$st];
                $summary[] = "{$label}: {$row['trx']} transaksi / Rp " . number_format($row['rp'], 0, ',', '.');
            }

            // Daftar nama unik + berapa kali, itu yang paling sering ditanyakan.
            $names = $rows->whereNotNull('nama')->where('nama', '!=', '')
                ->groupBy('nama')
                ->map(fn ($grp, $nama) => ['nama' => $nama, 'jumlah' => $grp->count(), 'terakhir' => $grp->max('waktu_mulai')])
                ->sortByDesc('terakhir')
                ->values();

            $out = "Periode: {$rLabel}.\n";
            $out .= 'Total: ' . $totalTransaksi . ' transaksi | ' . $jumlahNama . ' penyewa berbeda | Omset: Rp ' . number_format($omset, 0, ',', '.') . "\n";
            $out .= 'Status: ' . implode(', ', $summary) . "\n";
            $out .= "Daftar nama penyewa (urut terbaru, dari 30 transaksi terbaru):\n";
            $i = 0;
            foreach ($names as $n) {
                if ($i++ >= 40) {
                    $out .= '- ...dan ' . ($names->count() - 40) . ' nama lain.\n';
                    break;
                }
                $tgl = \Carbon\Carbon::parse($n['terakhir'])->translatedFormat('d M');
                $out .= '- ' . $n['nama'] . ' (' . $n['jumlah'] . 'x, terakhir ' . $tgl . ")\n";
            }
            // Transaksi dibuat ringkas: yang ditanyakan biasanya "siapa", bukan detail unit.
            $out .= "Transaksi:\n" . $rows->map(function ($r) use ($statusIndo) {
                $u = $r->units->map(fn ($x) => $x->nama_lengkap ?: $x->seri)->implode(', ') ?: '-';
                $tgl = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M') : '-';
                return '- ' . $tgl . ' | ' . ($r->nama ?: '-') . ' | ' . $u
                    . ' | ' . ($statusIndo[$r->status] ?? $r->status)
                    . ' | Rp ' . number_format($r->grand_total ?: $r->subtotal_harga, 0, ',', '.')
                    . ' | ' . ($r->no_wa ?: '-') . "\n";
            })->implode('');
            return $out;
        }, ($s['riwayat'] ?? false) ? 1 : 8);

        $add('RIWAYAT PERNAH TERLAMBAT MENGEMBALIKAN (semua waktu)', 'riwayat_telat', function () use ($statusIndo) {
            $rows = \App\Models\Rental::with(['units'])
                ->whereNotNull('completed_at')->whereNotNull('waktu_selesai')
                ->whereColumn('completed_at', '>', 'waktu_selesai')
                ->orderByDesc('completed_at')
                ->limit(10)->get();
            return $rows->isEmpty()
                ? "Belum ada riwayat penyewa yang terlambat mengembalikan unit.\n"
                : $rows->map(fn ($r) => self::rentalLine(
                    $r,
                    $statusIndo,
                    ' | Telat: ' . self::minutesLate(\Carbon\Carbon::parse($r->completed_at), $r->waktu_selesai) . ' menit'
                ))->implode('');
        }, ($s['riwayat'] ?? false) || ($s['terlambat'] ?? false) ? 5 : 9);

        // Budget dibagi sesuai PRIORITAS, bukan sesuai urutan pemanggilan.
        // Dulu budget habis sesuai urutan $add(), jadi bagian yang paling relevan
        // dengan pertanyaan (mis. daftar penyewa terlambat) justru sering yang
        // dibuang padahal masih muat. usort() stabil, jadi urutan aslinya tetap
        // terjaga di dalam prioritas yang sama.
        usort($collected, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $blocks = [];
        $loaded = [];
        $overflow = [];   // bagian yang tidak kebudget -> dicoba lagi kalau jawaban pertama masih menyuruh pakai kata kunci
        $budget = self::internalDataBudget();

        foreach ($collected as [$priority, $label, $body]) {
            // Label + pemisah ikut dihitung, supaya total data yang benar-benar
            // terkirim tidak melewati plafon yang sudah diatur di Pengaturan.
            $cost = mb_strlen($label) + 1 + mb_strlen($body) + ($blocks === [] ? 0 : 2);

            // Tidak dipotong/dibuang, tapi disimpan utuh di $overflow supaya masih
            // bisa dikirim di giliran kedua kalau ternyata jawabannya belum cukup.
            if ($cost > $budget) {
                $overflow[] = "{$label}:\n{$body}";
                continue;
            }

            $budget -= $cost;
            $blocks[$priority][] = "{$label}:\n{$body}";
            $loaded[] = $label;
        }

        ksort($blocks);

        $text = implode("\n\n", array_merge(...array_values($blocks) ?: [[]]));

        // Rapikan baris kosong berlebih pada blok data.
        $text = trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);

        return [$text, $loaded, $overflow];
    }

    /**
     * Inventaris unit + status siapnya per unit.
     *
     * Inilah yang menjawab "ip 13 ready??" dari data nyata. Sebelumnya bagian ini
     * hanya memuat daftar unit + harga (tanpa status) dan hanya ikut kalau
     * pertanyaannya memuat kata kunci seperti "stok"/"unit", jadi team sering
     * diberi jawaban "inventaris tidak ikut dimuat" padahal unitnya ada di toko.
     *
     * Kuerinya tetap irit: satu query booking untuk semua unit, lalu dikelompokkan
     * per unit di memori — bukan satu query per unit.
     */
    private static function unitAvailabilityText(\Carbon\Carbon $now): string
    {
        $units = Unit::where('is_active', true)->with('category')->get();
        if ($units->isEmpty()) {
            return "Belum ada unit aktif di toko.\n";
        }

        // Booking yang relevan: yang masih berjalan (termasuk yang sudah lewat
        // jadwal, itu justru yang paling harus kelihatan) dan yang akan datang.
        //
        // Dulu difilter "waktu_selesai >= sekarang - 1 jam", sehingga unit yang
        // masih dipegang penyewa yang telat ikut hilang dari daftar dan dilaporkan
        // "SIAP DIPAKAI" padahal barangnya belum kembali. Dipakai yang 300
        // booking terbaru (bukan terlama), lalu dibalik lagi ke urut kronologis
        // supaya indeks terakhir = booking yang sedang berjalan.
        $rentals = \App\Models\Rental::with('units')
            ->whereIn('status', ['pending', 'pending_confirmation', 'paid', 'renting'])
            ->orderByDesc('waktu_mulai')
            ->limit(300)->get()
            ->reverse()->values();

        $byUnit = [];
        foreach ($rentals as $r) {
            foreach ($r->units as $u) {
                $byUnit[$u->id][] = $r;
            }
        }

        $text = '';
        $siap = 0;

        foreach ($units as $u) {
            $harga = [];
            if ($u->harga_per_hari) {
                $harga[] = '24 jam: Rp ' . number_format($u->harga_per_hari, 0, ',', '.');
            }
            if ($u->harga_per_jam) {
                $harga[] = '12 jam: Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.');
            }

            $lines = $byUnit[$u->id] ?? [];
            // Daftar urut mulai dari paling awal, jadi indeks terakhir yang sudah
            // mulai = booking yang sedang berjalan.
            $jalanIdx = null;
            foreach ($lines as $i => $r) {
                if (! $r->waktu_mulai) {
                    continue;
                }
                if (\Carbon\Carbon::parse($r->waktu_mulai)->lessThanOrEqualTo($now)) {
                    $jalanIdx = $i;
                }
            }

            if ($jalanIdx !== null) {
                $r = $lines[$jalanIdx];
                $selesai = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai) : null;
                $status = 'SEDANG DIPAKAI s/d ' . ($selesai ? $selesai->translatedFormat('d M H:i') : '-');
                if ($selesai && $selesai->lessThan($now)) {
                    $status .= ' *** MELEBIHI JADWAL ' . self::minutesLate($now, $selesai) . ' MENIT ***';
                }
                $status .= ' | Penyewa: ' . ($r->nama ?: '-') . ' (' . ($r->no_wa ?: '-') . ') | Kode: ' . ($r->booking_code ?: '-');
            } else {
                $siap++;
                $status = 'SIAP DIPAKAI sekarang';
            }

            $next = $lines[$jalanIdx === null ? 0 : $jalanIdx + 1] ?? null;
            if ($next && $next->waktu_mulai) {
                $mulai = \Carbon\Carbon::parse($next->waktu_mulai)->translatedFormat('d M H:i');
                $selesai = $next->waktu_selesai ? \Carbon\Carbon::parse($next->waktu_selesai)->translatedFormat('d M H:i') : '-';
                $status .= ' | Berikutnya: ' . $mulai . ' - ' . $selesai
                    . ' (' . ($next->nama ?: '-') . ', ' . ($next->status) . ')';
            }

            $text .= "- [ID:{$u->id}] {$u->nama_lengkap} | Kategori: " . ($u->category?->name ?? 'Unit')
                . ($harga ? ' | ' . implode(' | ', $harga) : '') . ' | ' . $status . "\n";
        }

        $text .= "Total: {$siap} dari " . $units->count() . " unit siap dipakai sekarang.\n";

        try {
            $nonAktif = (int) Unit::where('is_active', false)->count();
            if ($nonAktif > 0) {
                $text .= "Catatan: {$nonAktif} unit lain berstatus non-aktif (rusak/perbaikan), tidak disewakan.\n";
            }
        } catch (\Throwable $e) {
            // Jumlah unit non-aktif hanya pelengkap; kalau gagal query tidak boleh menggagalkan seluruh bagian.
        }

        return $text;
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
     *
     * Yang dipangkas HANYA catatan obrolan ("YANG SUDAH DIPBAHAS" dan "OBROLAN
     * TERAKHIR"). Blok DATA BISNIS sengaja tidak pernah disentuh: di grup report
     * data yang hilang berarti bot menjawab "data tidak dimuat", dan itu justru
     * keluhan yang paling ingin dihilangkan.
     */
    private static function shrinkInternalPrompt(string $prompt): string
    {
        $dataPos = strpos($prompt, 'DATA BISNIS');
        if ($dataPos === false) {
            return $prompt;
        }

        $head = substr($prompt, 0, $dataPos);
        $dataPos = strpos($prompt, 'DATA BISNIS');
        $tail = substr($prompt, $dataPos);

        $head = preg_replace('/YANG SUDAH DIPBAHAS \(MEMORI TIM\):.*?(?=DATA BISNIS|\z)/s', "YANG SUDAH DIPBAHAS (MEMORI TIM):\n(dikurangi sementara karena pemakaian token sedang tinggi)\n", $head) ?? $head;
        $head = preg_replace('/OBROLAN TERAKHIR:.*?(?=DATA BISNIS|\z)/s', '', $head) ?? $head;

        return $head . $tail;
    }

    /**
     * Deteksi jawaban ala "data tidak ikut dimuat, coba pake kata kunci ...".
     *
     * Kalimat itu yang paling dikeluhkan tim di grup report: mereka cuma ingin
     * bertanya dengan bahasa sehari-hari, bukan menghafal daftar kata kunci.
     * Kalau model tetap mengulanginya, berarti datanya memang kurang — jadi
     * lebih baik giliran kedua dengan data tambahan daripada mengirim unload
     * "sebut kata kunci" ke WhatsApp.
     */
    private static function isHedgingAnswer(string $text): bool
    {
        return (bool) preg_match(
            '/tidak ikut dimuat|belum dimuat|tidak dimuat|data tidak tersedia|tidak tersedia di sini|kata kunci|silakan (tanyakan|coba)|coba tanyakan dengan/i',
            $text
        );
    }

    /**
     * Ubah daftar turn terakhir menjadi blok teks ringkas untuk prompt.
     */
    private static function formatRecentTurns(array $turns, string $userLabel = 'Tim', string $botLabel = 'Kamu'): string
    {
        if (empty($turns)) {
            return '';
        }
        $text = '';
        foreach ($turns as $i => $turn) {
            $text .= ($i + 1) . ". {$userLabel}: " . $turn['user'] . "\n   {$botLabel}: " . $turn['model'] . "\n";
        }
        return rtrim($text);
    }

    /** Alasan panggilan Gemini terakhir yang gagal, untuk pesan fallback. */
    private static ?string $lastError = null;

    /** Alasan kegagalan panggilan terakhir, untuk service lain & pesan fallback. */
    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    /**
     * Kirim payload ke Gemini dengan failover antar-kunci, kembalikan respons sukses.
     *
     * Ini primitif bersama: dipakai bot WhatsApp (askGemini) dan juga chat web
     * (AiService) karena keduanya memakai pool kunci "customer" yang sama. Kalau
     * hanya satu yang punya failover, kuota habis di jalur pertama tetap membuat
     * jalur kedua gagal even though masih ada kunci yang sehat.
     *
     * Kalau satu kunci kena 429, kunci itu langsung disingkirkan sebentar lalu
     * dicoba lagi dengan kunci berikutnya di pool yang sama — dalam permintaan
     * yang sama. Menunggu pergantian menit seperti rotasi jam dinding justru
     * bikin customer kehilangan jawaban: selama kunci yang salah masih jadi
     * giliran, semua chat di menit itu tetap gagal.
     *
     * Status di RETRYABLE_STATUSES dipindah ke kunci berikutnya: 429 karena kuota
     * kunci itu habis, 5xx karena Google sedang sibuk. Keduanya ditolak cepat,
     * jadi pindah key hampir gratis. Status lain (400/401/403/404) langsung
     * berhenti: penyebabnya salah konfigurasi, bukan soal kuota, dan akan gagal
     * sama di project lain.
     */
    public static function callWithFailover(string $feature, string $model, array $payload, int $timeout): ?\Illuminate\Http\Client\Response
    {
        self::$lastError = null;

        $ownKeys = self::apiKeysFor($feature);
        if ($ownKeys === []) {
            self::$lastError = 'API key ' . self::featureLabel($feature) . ' belum diisi';
            return null;
        }

        // Urutan percobaan:
        //   1. kunci fitur sendiri yang sehat
        //   2. kunci customer sebagai cadangan, selama fitur ini bukan customer
        //   3. kunci fitur sendiri yang sedang cooldown (upaya terakhir)
        //
        // Kunci cadangan didahulukan atas kunci yang sedang cooldown karena kita
        // sudah tahu kunci itu akan kena 429 lagi, sedangkan cadangan belum pernah
        // dicoba untuk fitur ini. 503 dari Google bersifat sementara dan model-wide,
        // jadi satu slot fitur yang kebetulan kena tidak boleh membuat seluruh
        // fitur mati padahal ada kunci sehat yang menganggur.
        [$ownHealthy, $ownCooling] = self::partitionKeysFor($feature, $ownKeys);

        $keys = $ownHealthy;
        $ownCount = count($ownHealthy);

        foreach (self::reserveKeysFor($feature) as $reserve) {
            if (! in_array($reserve, $keys, true) && ! self::isCoolingDown($feature, $reserve)) {
                $keys[] = $reserve;
            }
        }

        $reserveCount = count($keys) - $ownCount;

        if ($keys === []) {
            $keys = $ownCooling;
            // Semua kunci fitur cooldown dan tidak ada cadangan sehat: pakai yang
            // paling lama cooldown. Mending 429 daripada dapat balasan kosong.
            $keys = $keys === [] ? [] : [$keys[0]];
        }

        $exhausted = [];
        $seenStatuses = [];

        foreach ($keys as $apiKey) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout($timeout)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    $payload
                );

                if ($response && $response->successful()) {
                    return $response;
                }

                $status = $response?->status() ?? 0;
                $body = (string) $response?->body();
                self::$lastError = 'HTTP ' . $status . ' dari model ' . $model . ' — ' . self::readApiError($body);
                Log::warning('GeminiAIService gagal: HTTP ' . $status . ' | model ' . $model . ' | ' . mb_substr(self::maskSecrets($body), 0, 400));

                if (in_array($status, self::RETRYABLE_STATUSES, true)) {
                    self::markCooldown($feature, $apiKey);
                    $exhausted[] = $apiKey;
                    $seenStatuses[$status] = ($seenStatuses[$status] ?? 0) + 1;

                    if ($status !== 429) {
                        usleep(self::SERVER_BUSY_BACKOFF_US);
                    }

                    continue;
                }

                return null;
            } catch (\Throwable $e) {
                $message = self::maskSecrets($e->getMessage());
                self::$lastError = $message;

                if (self::isTransientException($message)) {
                    // Timeout atau koneksi putus: kunci ini tidak salah, hanya
                    // jaringan yang lagi tidak sehat. Pindah ke kunci berikutnya
                    // supaya satu timeout acak tidak menggagalkan percakapan.
                    //
                    // Yang penting: whatever pun yang gagal saat_switch ke kunci
                    // berikutnya (mis. cache cooldown) tidak boleh membatalkan
                    // failover, jadi dibungkus try terpisah dari catch di atas.
                    try {
                        self::markCooldown($feature, $apiKey);
                    } catch (\Throwable $ignored) {
                        // Tanpa cache, cooldown tidak bisa disimpan. Tetap coba
                        // kunci berikutnya — lebih baik daripada langsung gagal.
                    }

                    $exhausted[] = $apiKey;
                    $seenStatuses['timeout'] = ($seenStatuses['timeout'] ?? 0) + 1;
                    Log::warning('GeminiAIService timeout/jaringan, pindah kunci: ' . $message);

                    usleep(self::SERVER_BUSY_BACKOFF_US);
                    continue;
                }

                Log::error('GeminiAIService Exception: ' . $message);
                return null;
            }
        }

        if ($exhausted !== []) {
            $codes = [];
            foreach ($seenStatuses as $code => $count) {
                $codes[] = ($code === 'timeout' ? 'timeout' : 'HTTP ' . $code) . '×' . $count;
            }

            $summary = implode(', ', $codes);
            $isRateLimit = count($seenStatuses) === 1 && isset($seenStatuses[429]);
            $isTimeoutOnly = count($seenStatuses) === 1 && isset($seenStatuses['timeout']);
            $label = $isRateLimit
                ? 'kena rate limit'
                : ($isTimeoutOnly ? 'timeout' : 'gagal (server sibuk / tidak stabil)');

            self::$lastError = 'Semua ' . count($exhausted) . ' kunci API ' . $label . ' (' . $summary . ').';
            Log::error('GeminiAIService: pool kunci ' . $feature . ' habis, ' . count($exhausted) . ' kunci ' . $label . ' — ' . $summary . '.');

            if ($reserveCount > 0) {
                Log::warning(
                    'GeminiAIService: fitur ' . $feature . ' memakai ' . count($exhausted)
                    . ' kunci sendiri lalu ' . $reserveCount
                    . ' kunci cadangan customer, semuanya tetap gagal.'
                );
            }
        }

        return null;
    }

    /** Panggil Gemini dan kembalikan teks bersih (null bila gagal). */
    private static function askGemini(string $feature, string $prompt, string $model, float $temperature, int $maxTokens, int $timeout): ?string
    {
        $response = self::callWithFailover($feature, $model, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ], $timeout);

        if ($response === null) {
            return null;
        }

        $candidates = $response->json('candidates');
        if (empty($candidates[0]['content']['parts'][0]['text'])) {
            self::$lastError = 'jawaban kosong dari model ' . $model;
            Log::warning('GeminiAIService: jawaban kosong dari model ' . $model);
            return null;
        }

        return self::formatForWhatsApp(trim($candidates[0]['content']['parts'][0]['text']));
    }

    /**
     * Ambil pesan error yang paling berguna dari body respons Google.
     *
     * Body harus di-decode utuh; kalau dipotong lebih dulu, JSON-nya jadi tidak
     * lengkap dan pesan aslinya justru bocor ke WhatsApp.
     */
    private static function readApiError(string $body): string
    {
        if (trim($body) === '') {
            return '(respons kosong)';
        }
        $json = json_decode($body, true);
        $msg = is_array($json) ? ($json['error']['message'] ?? null) : null;
        if (is_string($msg) && $msg !== '') {
            return mb_substr($msg, 0, 200);
        }
        return mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 200);
    }

    /**
     * Bot tidak boleh diam tanpa jejak.
     *
     * Dulu kegagalan AI hanya di-log lalu `reply` dikirim null, sehingga di grup
     * tidak ada yang terjadi dan tim mengira botnya "nyabodoh". Sekarang kegagalan
     * ini dikembalikan sebagai pesan yang isinya memberi tahu apa yang salah.
     */
    private static function fallbackMessage(string $feature = 'report'): string
    {
        $reason = self::$lastError ?: 'sebab tidak diketahui';
        $label = self::featureLabel($feature);

        if (str_contains($reason, 'API_KEY_INVALID') || str_contains($reason, 'API key not valid')) {
            $hint = "Kunci API AI ({$label}) tidak valid. Buka *Web Admin -> Pengaturan -> Tab WhatsApp*, isi *API Key {$label}* yang benar lalu simpan.";
        } elseif (str_contains($reason, '429') || str_contains($reason, 'RESOURCE_EXHAUSTED')) {
            $hint = "Semua kunci API AI ({$label}) kena rate limit. Tunggu sebentar, atau isi slot kunci 2-4 di *Web Admin -> Pengaturan -> Tab WhatsApp* (wajib dari project Google yang berbeda, karena kuota dihitung per project, bukan per kunci).";
        } elseif (str_contains($reason, '404') || str_contains($reason, 'NOT_FOUND')) {
            $hint = "Model AI ({$label}) yang dipilih tidak tersedia. Ganti modelnya di *Web Admin -> Pengaturan -> Tab WhatsApp* (kosongkan dulu supaya kembali ke default).";
        } elseif (stripos($reason, 'timed out') !== false || stripos($reason, 'cURL error 28') !== false) {
            $hint = "Server terlalu lama menunggu jawaban AI. Coba ulangi pertanyaannya sebentar lagi.";
        } else {
            $hint = "Cek *Web Admin -> Pengaturan -> Tab WhatsApp* (API Key & Model {$label}), lalu coba ulangi.";
        }

        return self::formatForWhatsApp(
            "⚠️ *Data belum bisa dimuat — asisten AI gagal menjawab.*\n\n"
            . "Sebab: " . mb_substr($reason, 0, 220) . "\n\n"
            . $hint
        );
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
