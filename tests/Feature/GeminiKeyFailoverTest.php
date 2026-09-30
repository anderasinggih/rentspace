<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiAIService;
use App\Livewire\Admin\Settings as SettingsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Failover pool API key Gemini.
 *
 * Premisanya: Google menghitung rate limit per PROJECT, bukan per API key. Jadi
 * pool kunci hanya berguna kalau kuncinya berasal dari project berbeda, dan begitu
 * satu kunci kena 429, permintaan yang sama harus langsung dicoba lagi dengan
 * kunci berikutnya — bukan menunggu giliran menit berikutnya.
 */
class GeminiKeyFailoverTest extends TestCase
{
    use RefreshDatabase;

    /** Kunci yang dipakai tiap panggilan, berurutan. */
    private array $seenKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seenKeys = [];
    }

    /** Isi pool kunci customer sebanyak $n slot. */
    private function fillKeys(int $n = 3): void
    {
        for ($slot = 1; $slot <= $n; $slot++) {
            Setting::updateOrCreate(
                ['key' => GeminiAIService::keySlotName('customer', $slot)],
                ['value' => "pool-key-{$slot}"]
            );
        }
    }

    /**
     * Palsukan Gemini. Closure deciding respons per kunci yang dipakai.
     * Semua kunci dicatat di $this->seenKeys supaya urutan failover bisa dicek.
     */
    private function fakeGemini(callable $decide): void
    {
        Http::fake(function ($request) use ($decide) {
            $key = '';
            if (str_contains($request->url(), 'key=')) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
                $key = $query['key'] ?? '';
            }
            $this->seenKeys[] = $key;

            $result = $decide($key) ?: $this->answerBody('Jawaban dari ' . $key);
            return Http::response($result['body'], $result['status']);
        });
    }

    /** Body error 429 yang bentuknya sama dengan error asli Google. */
    private function quotaError(): array
    {
        return [
            'status' => 429,
            'body' => ['error' => ['message' => 'Resource has been exhausted (e.g. check quota).']],
        ];
    }

    /**
     * Versi fakeGemini yang melempar exception, untuk menguji timeout/jaringan.
     *
     * $decide mengembalikan string pesan exception untuk kunci yang harus gagal
     * (null berarti kunci itu sehat dan dijawab normal).
     */
    private function fakeGeminiThrow(callable $decide): void
    {
        Http::fake(function ($request) use ($decide) {
            $key = '';
            if (str_contains($request->url(), 'key=')) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
                $key = $query['key'] ?? '';
            }
            $this->seenKeys[] = $key;

            $throw = $decide($key);
            if ($throw) {
                throw new \Illuminate\Http\Client\ConnectionException($throw);
            }

            $ok = $this->answerBody('Jawaban dari ' . $key);
            return Http::response($ok['body'], $ok['status']);
        });
    }

    private function answerBody(string $text): array
    {
        return [
            'status' => 200,
            'body' => ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]],
        ];
    }

    private function reply(string $message = 'iphone 13 ada?'): array
    {
        return GeminiAIService::customerReply($message, 'Budi', '6281@s.whatsapp.net', '6281');
    }

    // ---------------------------------------------------------------- pool

    public function test_semua_slot_kunci_terbaca_dan_slot_pertama_jadi_utama(): void
    {
        $this->fillKeys(4);

        $this->assertCount(4, GeminiAIService::apiKeysFor('customer'));
        $this->assertSame('pool-key-1', GeminiAIService::apiKeyFor('customer'));
    }

    public function test_slot_kosong_di_tengah_tidak_membuat_kunci_tetap_kosong(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'pool-key-1']);
        Setting::updateOrCreate(['key' => 'chatbot_api_key_3'], ['value' => 'pool-key-3']);

        $this->assertSame(['pool-key-1', 'pool-key-3'], GeminiAIService::apiKeysFor('customer'));
    }

    public function test_instalasi_lama_dengan_satu_kunci_tetap_jalan(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'solo-key']);

        $this->assertSame(['solo-key'], GeminiAIService::apiKeysFor('customer'));
        $this->assertSame('solo-key', GeminiAIService::apiKeyFor('customer'));
    }

    public function test_kunci_yang_sama_di_dua_slot_tidak_dihitung_ganda(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'dupe']);
        Setting::updateOrCreate(['key' => 'chatbot_api_key_2'], ['value' => 'dupe']);
        Setting::updateOrCreate(['key' => 'chatbot_api_key_3'], ['value' => 'lain']);

        $this->assertSame(['dupe', 'lain'], GeminiAIService::apiKeysFor('customer'));
    }

    public function test_laporan_tanpa_kunci_sendiri_tetap_pakai_kunci_customer(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'customer-key']);

        $this->assertSame(['customer-key'], GeminiAIService::apiKeysFor('report'));
    }

    // ------------------------------------------------------------ failover

    public function test_429_langsung_pindah_ke_kunci_berikutnya_dalam_permintaan_sama(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn (string $key) => $key === 'pool-key-1' ? $this->quotaError() : null);

        $result = $this->reply();

        $this->assertNotNull($result['reply'], 'kunci kedua harus rescuing jawaban');
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'harus mencoba slot 1 lalu slot 2');
    }

    public function test_semua_kunci_kena_429_tidak_menghasilkan_jawaban_palsu(): void
    {
        $this->fillKeys(2);
        $this->fakeGemini(fn () => $this->quotaError());

        $result = $this->reply();

        $this->assertNull($result['reply'], 'tidak boleh mengarang jawaban saat semua kunci kena 429');
        $this->assertStringContainsString('429', (string) GeminiAIService::lastError());
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'semua kunci di pool harus dicoba');
    }

    public function test_error_bukan_429_tidak_dicoba_ke_kunci_lain(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn () => [
            'status' => 400,
            'body' => ['error' => ['message' => 'API key not valid']],
        ]);

        $this->reply();

        $this->assertSame(
            ['pool-key-1'],
            $this->seenKeys,
            'kunci ditolak bukan karena kuota, jadi pindah kunci tidak menolong'
        );
    }

    // ---------------------------------------------------------------- timeout

    /**
     * Timeout adalah masalah jaringan, bukan salah konfigurasi kunci.
     *
     * Kasus nyata di production: satu kunci timeout cURL error 28, controller
     * langsung dapat null dan customer diberi "diteruskan ke admin" padahal
     * masih ada tiga kunci lain yang belum dicoba sama sekali.
     */
    public function test_timeout_pindah_ke_kunci_berikutnya(): void
    {
        $this->fillKeys(3);
        $this->fakeGeminiThrow(fn (string $key) => $key === 'pool-key-1'
            ? 'cURL error 28: Operation timed out after 25001 milliseconds with 0 bytes received'
            : null);

        $result = $this->reply();

        $this->assertNotNull($result['reply'], 'kunci kedua harus rescuing jawaban setelah timeout');
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'harus mencoba slot 1 lalu slot 2');
    }

    /** Semua kunci timeout: tidak boleh mengarang jawaban, dan dilaporkan apa adanya. */
    public function test_semua_kunci_timeout_tidak_menghasilkan_jawaban_palsu(): void
    {
        $this->fillKeys(2);
        $this->fakeGeminiThrow(fn () => 'cURL error 28: Operation timed out after 25001 milliseconds');

        $result = $this->reply();

        $this->assertNull($result['reply'], 'tidak boleh mengarang jawaban saat semua kunci timeout');
        $this->assertStringContainsString('timeout', (string) GeminiAIService::lastError());
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'semua kunci di pool harus dicoba');
    }

    /** Kunci ditolak (bukan transient) tetap berhenti di kunci pertama. */
    public function test_exception_konfigurasi_tidak_dicoba_ke_kunci_lain(): void
    {
        $this->fillKeys(3);
        $this->fakeGeminiThrow(fn () => 'API key not valid. Please pass a valid API key.');

        $this->reply();

        $this->assertSame(
            ['pool-key-1'],
            $this->seenKeys,
            'kunci ditolak akan gagal sama persis di semua kunci, jadi tidak perlu dicoba lagi'
        );
    }

    // ------------------------------------------------------- server busy (5xx)

    /** Body error 503 "high demand" yang bentuknya sama dengan error asli Google. */
    private function highDemandError(): array
    {
        return [
            'status' => 503,
            'body' => ['error' => ['message' => 'This model is currently experiencing high demand.']],
        ];
    }

    public function test_503_pindah_ke_kunci_berikutnya_dalam_permintaan_sama(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn (string $key) => $key === 'pool-key-1' ? $this->highDemandError() : null);

        $result = $this->reply();

        $this->assertNotNull($result['reply'], 'kunci kedua harus rescuing jawaban');
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'harus mencoba slot 1 lalu slot 2');
    }

    /**
     * 503 berasal dari kapasitas model, bukan dari kunci: kunci lain milik project
     * berbeda biasanya tetap bisa menjawab. Ini alasan 503 masuk daftar retryable.
     */
    public function test_503_di_semua_kunci_tidak_menghasilkan_jawaban_palsu(): void
    {
        $this->fillKeys(2);
        $this->fakeGemini(fn () => $this->highDemandError());

        $result = $this->reply();

        $this->assertNull($result['reply'], 'tidak boleh mengarang jawaban saat semua kunci kena 503');
        $this->assertStringContainsString('503', (string) GeminiAIService::lastError());
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, 'semua kunci di pool harus dicoba');
    }

    /**
     * 5xx lain punya alasan yang sama: server sibuk, bukan kunci rusak.
     *
     * Dipisah jadi tiga method, bukan satu loop: Http::fake() menumpuk stub
     * setiap kali dipanggil, jadi dalam satu test method closure iterasi
     * sebelumnya masih aktif dan yang diuji sebenarnya bukan status yang dimaksud.
     */
    public function test_500_dicoba_ke_kunci_berikutnya(): void
    {
        $this->assertFiveOhHundredFailsOver(500);
    }

    public function test_502_dicoba_ke_kunci_berikutnya(): void
    {
        $this->assertFiveOhHundredFailsOver(502);
    }

    public function test_504_dicoba_ke_kunci_berikutnya(): void
    {
        $this->assertFiveOhHundredFailsOver(504);
    }

    private function assertFiveOhHundredFailsOver(int $status): void
    {
        $this->fillKeys(2);
        $this->fakeGemini(fn (string $key) => $key === 'pool-key-1'
            ? ['status' => $status, 'body' => ['error' => ['message' => 'sibuk']]]
            : null);

        $result = $this->reply();

        $this->assertNotNull($result['reply'], "HTTP {$status} harus dipindah ke kunci berikutnya");
        $this->assertSame(['pool-key-1', 'pool-key-2'], $this->seenKeys, "HTTP {$status}: slot 1 lalu slot 2");
    }

    /** 404 = model tidak ada. Salah konfigurasi, gagal di semua kunci. */
    public function test_404_tidak_dicoba_ke_kunci_lain(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn () => [
            'status' => 404,
            'body' => ['error' => ['message' => 'models/gemini-x is not found']],
        ]);

        $this->reply();

        $this->assertSame(['pool-key-1'], $this->seenKeys, 'model tidak ada tidak akan hilang di kunci lain');
    }

    // ------------------------------------------- kunci cadangan lintas fitur

    /**
     * Laporan grup punya satu slot sendiri, tapi 503 itu sifatnya model-wide: kunci
     * customer yang sehat harus bisa dipakai, kalau tidak fitur ini mati tiap kali
     * Google sedang ramai. Ini bug yang bikin "Semua 1 kunci API gagal" padahal
     * ada 4 kunci yang menganggur.
     */
    public function test_laporan_dengan_satu_kunci_pakai_kunci_customer_sebagai_cadangan(): void
    {
        $this->fillKeys(3);
        Setting::updateOrCreate(['key' => 'report_api_key'], ['value' => 'report-key-1']);
        $this->fakeGemini(fn (string $key) => $key === 'report-key-1' ? $this->highDemandError() : null);

        $result = GeminiAIService::replyInternal('rekap omset hari ini', 'Tim');

        $this->assertNotNull($result, 'kunci customer harus rescuing laporan');
        $this->assertSame('report-key-1', $this->seenKeys[0], 'kunci laporan sendiri selalu dicoba lebih dulu');
        $this->assertContains('pool-key-1', $this->seenKeys, 'kunci customer harus dipakai setelahnya');
    }

    public function test_kunci_laporan_sendiri_selalu_dicoba_lebih_dulu(): void
    {
        $this->fillKeys(2);
        Setting::updateOrCreate(['key' => 'report_api_key'], ['value' => 'report-key-1']);
        $this->fakeGemini(fn () => null);

        GeminiAIService::replyInternal('rekap omset hari ini', 'Tim');

        $this->assertSame('report-key-1', $this->seenKeys[0], 'slot fitur didahulukan sebelum cadangan customer');
    }

    /** Cadangan tidak boleh diduplikasi kalau slot fitur dipakai kunci yang sama. */
    public function test_cadangan_tidak_menggandakan_kunci_yang_sama(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'satu-kunci']);
        Setting::updateOrCreate(['key' => 'report_api_key'], ['value' => 'satu-kunci']);
        $this->fakeGemini(fn () => null);

        $r = new \ReflectionMethod(GeminiAIService::class, 'apiKeysFor');
        $r->setAccessible(true);

        $this->assertSame(['satu-kunci'], $r->invoke(null, 'report'), 'kunci yang sama tidak boleh dihitung dua kali');
    }

    public function test_customer_tidak_mendapat_kunci_cadangan(): void
    {
        $this->fillKeys(2);
        $this->fakeGemini(fn () => null);

        $r = new \ReflectionMethod(GeminiAIService::class, 'reserveKeysFor');
        $r->setAccessible(true);

        $this->assertSame([], $r->invoke(null, 'customer'), 'customer adalah pool utama, tidak butuh cadangan dari diri sendiri');
    }

    /** Kalau kunci laporan kena cooldown, cadangan langsung dipakai tanpa menunggu. */
    public function test_kunci_laporan_yang_kena_cooldown_dilewati(): void
    {
        $this->fillKeys(2);
        Setting::updateOrCreate(['key' => 'report_api_key'], ['value' => 'report-key-1']);
        $this->fakeGemini(fn (string $key) => $key === 'report-key-1' ? $this->quotaError() : null);

        // Permintaan pertama: report key kena 429 lalu pindah ke cadangan.
        $first = GeminiAIService::replyInternal('rekap omset hari ini', 'Tim');
        $this->assertNotNull($first);

        $this->seenKeys = [];
        $this->fakeGemini(fn () => null);

        GeminiAIService::replyInternal('rekap omset kemarin', 'Tim');

        $this->assertNotContains('report-key-1', $this->seenKeys, 'kunci yang kena 429 tidak boleh dicoba lagi di permintaan berikutnya');
    }

    // --------------------------------------------------------- kebocoran kunci

    /**
     * Pesan error cURL memuat URL lengkap termasuk `?key=...`. Kalau apa adanya
     * masuk log, API key production tersimpan plaintext di storage/logs.
     */
    public function test_api_key_disembunyikan_dari_teks_error(): void
    {
        $method = new \ReflectionMethod(GeminiAIService::class, 'maskSecrets');
        $method->setAccessible(true);

        $withQuery = $method->invoke(null, 'cURL error 28 for https://generativelanguage.googleapis.com/v1beta/models/x:generateContent?key=AIzaSyRAHASILAPATDIAMBIL');
        $this->assertStringNotContainsString('AIzaSyRAHASILAPATDIAMBIL', $withQuery, 'kunci dari query string harus tersembunyi');
        $this->assertStringContainsString('key=***', $withQuery, 'parameternya tetap terbaca sebagai key, cuma nilainya disembunyikan');

        $dummyKey = 'AQ.' . 'Ab8RN6JAfajfUn5fhwKVy3oNEr0Jmho1rnDeE8l18_mWGjr6ZA';
        $raw = $method->invoke(null, 'token gagal: ' . $dummyKey);
        $this->assertStringNotContainsString($dummyKey, $raw, 'bentuk mentah kunci juga harus tersembunyi');
    }

    public function test_kunci_asli_tidak_pernah_muncul_di_pesan_error_panel(): void
    {
        $this->fillKeys(1);
        Setting::updateOrCreate(['key' => GeminiAIService::keySlotName('customer', 1)], ['value' => 'AIzaSyKUNCIASLIPANGSANGSANGATPANJANG']);
        $this->fakeGemini(fn () => $this->highDemandError());

        $this->reply();

        $this->assertStringNotContainsString(
            'AIzaSyKUNCIASLIPANGSANGSANGATPANJANG',
            (string) GeminiAIService::lastError(),
            'pesan yang tampil di Pengaturan tidak boleh membocorkan kunci'
        );
    }

    public function test_kunci_yang_kena_429_ditahan_lalu_dilewati_di_permintaan_berikutnya(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn (string $key) => $key === 'pool-key-1' ? $this->quotaError() : null);

        $this->reply();
        $this->reply('iphone 12 ada?');

        $this->assertSame(
            1,
            count(array_filter($this->seenKeys, fn ($k) => $k === 'pool-key-1')),
            'kunci yang kena 429 tidak boleh dipakai lagi di permintaan berikutnya'
        );
        $this->assertNotEmpty(array_filter($this->seenKeys, fn ($k) => $k === 'pool-key-2'));
    }

    public function test_status_pool_melaporkan_kunci_yang_ditahan(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn (string $key) => $key === 'pool-key-1' ? $this->quotaError() : null);

        $this->assertSame(
            ['total' => 3, 'usable' => 3, 'cooling' => []],
            GeminiAIService::keyPoolStatus('customer')
        );

        $this->reply();

        $this->assertSame(
            ['total' => 3, 'usable' => 2, 'cooling' => [1]],
            GeminiAIService::keyPoolStatus('customer')
        );
    }

    public function test_pool_satu_kunci_tidak_perlu_failover(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'solo-key']);
        $this->fakeGemini(fn () => null);

        $this->reply();

        $this->assertSame(['solo-key'], $this->seenKeys);
    }

    public function test_tanpa_api_key_tidak_panggil_google_sama_sekali(): void
    {
        // .env test masih menyimpan GEMINI_API_KEY; kosongkan supaya kasus ini
        // benar-benar menguji pool yang tidak punya satu pun kunci.
        config(['services.gemini.key' => null]);
        $this->assertSame([], GeminiAIService::apiKeysFor('customer'));

        $this->fakeGemini(fn () => null);

        $result = $this->reply();

        $this->assertNull($result['reply']);
        $this->assertSame([], $this->seenKeys);
    }

    // -------------------------------------------------------- tes per slot

    public function test_tes_kunci_per_slot_menguji_slot_yang_dipilih(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn () => $this->answerBody('SIAP'));

        $result = GeminiAIService::testKeySlot('customer', 3);

        $this->assertTrue($result['ok']);
        $this->assertSame(['pool-key-3'], $this->seenKeys, 'tes slot 3 tidak boleh diam-diam menguji slot lain');
    }

    public function test_tes_kunci_per_slot_tidak_pakai_failover(): void
    {
        $this->fillKeys(3);
        $this->fakeGemini(fn () => $this->quotaError());

        $result = GeminiAIService::testKeySlot('customer', 1);

        $this->assertFalse($result['ok'], 'slot 1 yang 429 tidak boleh dilaporkan valid karena slot 2 tersedia');
        $this->assertSame(['pool-key-1'], $this->seenKeys);
    }

    public function test_tes_slot_kosong_memberi_pesan_yang_jelas(): void
    {
        $this->fillKeys(1);

        $result = GeminiAIService::testKeySlot('customer', 2);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('slot 2', $result['message']);
    }

    // ------------------------------------------------------------- admin UI

    /** Isi field wajib saveGeneralSettings supaya validasi tidak menggagalkan tes. */
    private function fillRequiredGeneralFields(): array
    {
        return [
            'home_title' => 'Rent Space',
            'home_description' => 'Sewa HP',
            'late_tolerance_minutes' => 60,
            'admin_wa' => '08123',
            'admin_address' => 'Purwokerto',
        ];
    }

    public function test_pengaturan_admin_bisa_menyimpan_empat_slot_kunci(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(SettingsPage::class)
            ->set($this->fillRequiredGeneralFields())
            ->set('chatbot_api_key', 'k1')
            ->set('chatbot_api_key_2', 'k2')
            ->set('chatbot_api_key_3', 'k3')
            ->set('chatbot_api_key_4', 'k4')
            ->call('saveGeneralSettings')
            ->assertHasNoErrors();

        $this->assertSame('k1', Setting::getVal('chatbot_api_key'));
        $this->assertSame('k2', Setting::getVal('chatbot_api_key_2'));
        $this->assertSame('k3', Setting::getVal('chatbot_api_key_3'));
        $this->assertSame('k4', Setting::getVal('chatbot_api_key_4'));

        // Halaman harus memuat keempat slot lagi, bukan cuma slot 1.
        Livewire::test(SettingsPage::class)
            ->assertSet('chatbot_api_key_2', 'k2')
            ->assertSet('chatbot_api_key_4', 'k4');
    }

    public function test_slot_kunci_yang_kelewat_panjang_ditolak(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(SettingsPage::class)
            ->set($this->fillRequiredGeneralFields())
            ->set('chatbot_api_key', 'k1')
            ->set('chatbot_api_key_2', str_repeat('x', 201))
            ->call('saveGeneralSettings')
            ->assertHasErrors('chatbot_api_key_2');
    }

    public function test_slot_yang_dikosongkan_ikut_disimpan_kosong(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Setting::updateOrCreate(['key' => 'chatbot_api_key_2'], ['value' => 'kunci-lama']);

        Livewire::test(SettingsPage::class)
            ->set($this->fillRequiredGeneralFields())
            ->set('chatbot_api_key', 'k1')
            ->set('chatbot_api_key_2', '')
            ->call('saveGeneralSettings')
            ->assertHasNoErrors();

        $this->assertSame('', Setting::getVal('chatbot_api_key_2'));
    }
}
