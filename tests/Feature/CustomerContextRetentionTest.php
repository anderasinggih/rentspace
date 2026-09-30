<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Rental;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\GeminiAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bot sempat kehilangan konteks: tiap pertanyaan dijawab ulang dengan template
 * sapaan ("Selamat siang, ada yang bisa dibantu?") padahal customer jelas
 * sedang nanya unit yang ready.
 *
 * Penyebabnya dua, dan keduanya dikunci di sini:
 * 1. PITPM guard (batas token/menit) memangkas prompt dari awal sampai Rules
 *    terpotong, sehingga pertanyaan customer hilang dan model asal nulis
 *    template.
 * 2. Histori kosong membuat sapaan pembuka tidak pernah dibuang.
 */
class CustomerContextRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'test-key-context']);
    }

    private function fakeGemini(string $modelReply): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $modelReply]]]],
                ],
            ]),
        ]);
    }

    private function seedUnits(): void
    {
        foreach ([['iPhone 12', '128GB', 'Hitam'], ['iPhone 13', '256GB', 'Biru']] as [$seri, $memori, $warna]) {
            Unit::create([
                'seri' => $seri,
                'imei' => 'imei-' . str($seri)->slug(),
                'memori' => $memori,
                'warna' => $warna,
                'harga_per_jam' => 12500,
                'harga_per_hari' => 250000,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Paksa PITPM guard kepicu: batasnya 1000 token, jadi prompt katalog apa pun
     * langsung melewati ambang degrade.
     */
    private function tripTokenBudget(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_tpm_limit'], ['value' => '1000']);
    }

    private function sentPrompt(): string
    {
        $prompt = '';
        Http::assertSent(function ($request) use (&$prompt) {
            $prompt = (string) $request['contents'][0]['parts'][0]['text'];

            return true;
        });

        return $prompt;
    }

    public function test_prompt_pangkas_tetap_membawa_pertanyaan_dan_aturan(): void
    {
        $this->seedUnits();
        $this->tripTokenBudget();
        $this->fakeGemini('ip 12 sama ip 13 ready kak');

        GeminiAIService::customerReply('hari ini ready ip berapa?', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        $this->assertStringContainsString(
            'hari ini ready ip berapa?',
            $prompt,
            'pertanyaan customer wajib tetap ada walau prompt dipangkas'
        );
        $this->assertStringContainsString(
            'CARA JAWAB (WAJIB',
            $prompt,
            'blok aturan wajib ikut, kalau tidak model asal nulis template sapaan'
        );
        $this->assertStringContainsString(
            'ATURAN KHUSUS TOKO',
            $prompt,
            'aturan khusus toko berada di luar blok data, jadi tidak boleh ikut terpangkas'
        );
    }

    public function test_prompt_pangkas_tidak_membuang_isi_daftar_unit(): void
    {
        $this->seedUnits();
        $this->tripTokenBudget();
        $this->fakeGemini('ip 12 ready kak');

        GeminiAIService::customerReply('katalog iphone dong', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        // Yang dipangkas hanya daftar, bukan seluruh blok data. Harga ikut
        // hilang karena itu bagian dari daftar katalog.
        $this->assertStringContainsString('DAFTAR UNIT & HARGA', $prompt);
        $this->assertStringNotContainsString('Rp 250.000/24 jam', $prompt);
        $this->assertStringContainsString('dipangkas demi batas token/menit', $prompt);
    }

    public function test_daftar_ready_tetap_ada_walau_katalog_dipangkas(): void
    {
        $this->seedUnits();
        $this->tripTokenBudget();
        $this->fakeGemini('ip 12 ready kak');

        GeminiAIService::customerReply('daftar iphone yang ready', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        // Ketersediaan justru blok yang paling tidak boleh hilang: customer
        // beli unit, bukan daftar harga.
        $this->assertStringContainsString('KETERSEDIAAN SEKARANG', $prompt);
        $this->assertStringContainsString('2 dari 2 unit siap dipakai', $prompt);
    }

    public function test_daftar_unit_ready_sekarang_masuk_prompt(): void
    {
        $this->seedUnits();
        $this->fakeGemini('ip 12 sama ip 13 kosong kak');

        GeminiAIService::customerReply('hari ini ready ip berapa?', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        $this->assertStringContainsString('KETERSEDIAAN SEKARANG', $prompt);
        $this->assertStringContainsString('2 dari 2 unit siap dipakai', $prompt);
        $this->assertStringContainsString('iPhone 12', $prompt);
    }

    public function test_unit_yang_sedang_dipakai_tidak_dimasukkan_ke_daftar_ready(): void
    {
        $this->seedUnits();
        $busy = Unit::where('seri', 'iPhone 13')->first();
        Rental::create([
            'nama' => 'Rina',
            'alamat' => 'Purwokerto',
            'no_wa' => '628999',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(6),
            'subtotal_harga' => 250000,
            'grand_total' => 250000,
            'status' => 'renting',
        ])->units()->attach($busy->id, ['price_snapshot' => 250000]);

        $this->fakeGemini('ip 12 ready, ip 13 dipakai Rina');

        GeminiAIService::customerReply('hari ini ready ip berapa?', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        $this->assertStringContainsString('1 dari 2 unit siap dipakai', $prompt);
        $this->assertStringContainsString('SEDANG DIPAKAI: iPhone 13', $prompt);
    }

    public function test_pertanyaan_tanpa_kata_hari_tetap_dapat_ketersediaan(): void
    {
        $this->seedUnits();
        $busy = Unit::where('seri', 'iPhone 12')->first();
        Rental::create([
            'nama' => 'Rina',
            'alamat' => 'Purwokerto',
            'no_wa' => '628999',
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHours(6),
            'subtotal_harga' => 250000,
            'grand_total' => 250000,
            'status' => 'renting',
        ])->units()->attach($busy->id, ['price_snapshot' => 250000]);

        $this->fakeGemini('ip 13 ready kak, ip 12 lagi dipakai');

        // "ready" memicu katalog tapi tidak memicu jadwal -- ketersediaan
        // tetap harus ikut, kalau tidak model asal jawab "semua terpakai".
        GeminiAIService::customerReply('ip 12 ready?', 'lvny', '628123@s.whatsapp.net', '628123');

        $prompt = $this->sentPrompt();

        $this->assertStringContainsString('KETERSEDIAAN SEKARANG', $prompt);
        $this->assertStringContainsString('1 dari 2 unit siap dipakai', $prompt);
    }

    public function test_sapaan_pembuka_dibuang_walau_riwayat_kosong(): void
    {
        $this->seedUnits();
        $this->fakeGemini(
            "Selamat siang, selamat datang di layanan pelanggan Rent Space Purwokerto.\n\n"
            . "Ada yang bisa kami bantu terkait penyewaan unit iPhone hari ini?"
        );

        // Percakapan baru: belum ada satu pun turn tersimpan.
        $this->assertSame(0, AiConversation::where('channel', 'wa_customer')->count());

        $result = GeminiAIService::customerReply(
            'hari ini ready ip berapa?',
            'lvny',
            '628123@s.whatsapp.net',
            '628123'
        );

        $this->assertStringStartsNotWith(
            'Selamat siang',
            (string) $result['reply'],
            'sapaan harus dibuang walau histori kosong, asal customer-nya bukan cuma menyapa'
        );
    }

    public function test_sapaan_tetap_dipertahankan_kalau_customer_memang_menyapa(): void
    {
        $this->seedUnits();
        $this->fakeGemini("Halo Kak! Selamat datang di Rent Space Purwokerto, ada yang bisa dibantu?");

        $result = GeminiAIService::customerReply('halo kak', 'lvny', '628123@s.whatsapp.net', '628123');

        $this->assertStringContainsStringIgnoringCase('halo', (string) $result['reply']);
    }

    public function test_pertanyaan_yang_mengandung_sapaan_tetap_buang_sapaan(): void
    {
        $this->seedUnits();
        $this->fakeGemini("Halo kak anderasinggih! Selamat siang. iPhone 13 ready hari ini kak.");

        $result = GeminiAIService::customerReply(
            'hari ini ip 13 ready??',
            'lvny',
            '628123@s.whatsapp.net',
            '628123'
        );

        $this->assertStringStartsNotWith('Halo kak', (string) $result['reply']);
    }

    public function test_handoff_di_luar_topik_tetap_mencatat_giliran(): void
    {
        $this->seedUnits();
        $this->fakeGemini('[[DI LUAR TOPIK]]');

        GeminiAIService::customerReply('besok UAS dong', 'lvny', '628123@s.whatsapp.net', '628123');

        $conv = AiConversation::where('channel', 'wa_customer')->first();
        $this->assertNotNull($conv, 'sesi harus tetap dibuat');
        $this->assertSame(1, (int) $conv->turn_count, 'giliran handoff harus tercatat, kalau tidak histori terputus');
    }

    public function test_balapan_pertanyaan_tidak_kehapus_daftar_ready(): void
    {
        $this->seedUnits();
        $this->tripTokenBudget();
        $this->fakeGemini('ip 12 ready kak');

        // Katalog + jadwal + cara pesan sekaligus: semua blok data terpangkas.
        GeminiAIService::customerReply(
            'ready? harga berapa? cara pesan gimana?',
            'lvny',
            '628123@s.whatsapp.net',
            '628123'
        );

        $prompt = $this->sentPrompt();

        $this->assertStringContainsString('KETERSEDIAAN SEKARANG', $prompt);
        $this->assertStringContainsString('CARA JAWAB (WAJIB', $prompt);
    }
}
