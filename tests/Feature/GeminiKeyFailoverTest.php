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
