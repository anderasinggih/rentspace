<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings as SettingsPanel;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiAIService;
use Database\Seeders\ReportTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Verifikasi jalur "tanpa batas" untuk asisten grup report.
 *
 * Aturan mainnya: di grup report semua data bisnis ikut dimuat, bukan cuma
 * bagian yang kata kuncinya kena. Batas karakter di Pengaturan bernilai 0 =
 * tanpa batas (default), dan angka lain hanya membatasi kalau memang diisi.
 *
 * Test ini memakai dataset uji (ReportTestDataSeeder) yang sengaja berisi
 * semua status transaksi, karena tanpa itu tiap blok data kosong dan tesnya
 * bisa lolos padahal tidak ada yang ikut dimuat.
 */
class InternalReportDataTest extends TestCase
{
    use RefreshDatabase;

    /** Bagian data yang harus selalu ikut, apa pun pertanyaan timnya. */
    private const ALWAYS_LOADED = [
        'RINGKASAN & OMSET',
        'KETERSEDIAAN UNIT TOKO (stok & status siap)',
        'JADWAL PENGAMBILAN HARI INI',
        'JADWAL PENGEMBALIAN HARI INI',
        'PENYEWA TERLAMBAT (sedang berjalan)',
        'RIWAYAT PERNAH KENA DENDA',
        'BOOKING MENUNGGU PENGAMBILAN (belum bayar)',
        'UNIT YANG SEDANG DISEWA',
        'RIWAYAT TRANSAKSI — 30 HARI TERAKHIR',
        'RIWAYAT PERNAH TERLAMBAT MENGEMBALIKAN (semua waktu)',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu dibekukan supaya "hari ini" tidak bergeser diuji dismissi.
        Carbon::setTestNow(Carbon::create(2026, 9, 28, 14, 0, 0));

        // Cache array hidup selama satu proses PHPUnit, jadi harus dikosongkan:
        // isi cache dari tes sebelumnya bukan isi database tes ini.
        Cache::flush();

        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'test-key-report']);
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '0']);

        $this->seed(ReportTestDataSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function callService(string $method, array $args = [])
    {
        $ref = new ReflectionMethod(GeminiAIService::class, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs(null, $args);
    }

    /** @return array{0:string,1:array<int,string>,2:array<int,string>} */
    private function buildData(string $question): array
    {
        $intents = $this->callService('detectInternalIntents', [$question]);

        return $this->callService('buildInternalData', [$question, $intents, Carbon::now()]);
    }

    /** Bentuk respons Gemini yang cuma berisi satu blok teks. */
    private function geminiSays(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    public function test_batas_karakter_nol_artinya_tanpa_batas(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '0']);
        $this->assertSame(200000, $this->callService('internalDataBudget'));

        // Angka di bawah 0 tidak boleh mengubah apa pun: tetapkan tanpa batas.
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '-1']);
        $this->assertSame(200000, $this->callService('internalDataBudget'));

        // Kalau memang dibatasi, angkanya dipatuhi.
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '30000']);
        $this->assertSame(30000, $this->callService('internalDataBudget'));

        // Tapi tidak boleh melebihi plafon aman (konteks model 1 juta token).
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '900000']);
        $this->assertSame(200000, $this->callService('internalDataBudget'));
    }

    public function test_pertanyaan_bebas_tanpa_kata_kunci_tetap_memuat_semua_bagian_data(): void
    {
        [$text, $loaded, $overflow] = $this->buildData('bro gimana nih?');

        foreach (self::ALWAYS_LOADED as $label) {
            $this->assertContains($label, $loaded, "Bagian data {$label} tidak ikut dimuat.");
        }

        $this->assertSame([], $overflow, 'Jalur tanpa batas tidak boleh menyisakan data di luar prompt.');
        $this->assertStringNotContainsString('data dipotong', $text);
        $this->assertStringNotContainsString('tidak ikut dimuat', $text);
    }

    public function test_kata_kunci_hanya_mengurutkan_bukan_menentukan_bagian_data(): void
    {
        [, $bebas] = $this->buildData('bro gimana nih?');
        [$text, $berkataKunci] = $this->buildData('ip 13 ready??');

        // Bagian yang ikut tidak boleh bergantung pada pertanyaan.
        $this->assertEqualsCanonicalizing($bebas, $berkataKunci);

        // Yang berubah cuma urutannya: bagian yang cocok ditulis paling dulu.
        $this->assertStringContainsString('KETERSEDIAAN UNIT TOKO', $text);
        $this->assertLessThan(
            mb_strpos($text, 'RIWAYAT PERNAH KENA DENDA'),
            mb_strpos($text, 'KETERSEDIAAN UNIT TOKO')
        );
    }

    public function test_data_yang_dimuat_berasal_dari_dataset_bukan_karangan(): void
    {
        [$text] = $this->buildData('ip 13 ready??');

        // Unit yang tidak disewa siapa pun harus ditandai siap pakai.
        $this->assertStringContainsString('iPhone 15', $text);
        $this->assertStringContainsString('SIAP DIPAKAI sekarang', $text);
        $this->assertStringContainsString('1 dari 3 unit siap dipakai sekarang', $text);

        // Unit yang disewa Rina lewat jadwal: harus kelihatan, bukan "ready".
        $this->assertMatchesRegularExpression('/iPhone 13 Pro.*SEDANG DIPAKAI.*MELEBIHI JADWAL 90 MENIT/s', $text);

        // Jadwal hari ini, keterlambatan, denda, dan riwayat dari dataset.
        $this->assertStringContainsString('Siti Aminah', $text);
        $this->assertStringContainsString('Bagus Prasetyo', $text);
        $this->assertStringContainsString('Denda telat: Rp 25.000', $text);
        $this->assertStringContainsString('Kaca kamera retak', $text);
        $this->assertStringContainsString('Telat: 45 menit', $text);
        $this->assertStringContainsString('Omset total: Rp', $text);

        // Unit non-aktif cuma berstatus catatan, tidak bikin blok gagal.
        $this->assertStringContainsString('1 unit lain berstatus non-aktif', $text);
    }

    public function test_batas_karakter_dari_pengaturan_memprioritaskan_bagian_yang_paling_relevan(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '1500']);
        Cache::flush();

        [$text, $loaded, $overflow] = $this->buildData('berapa yang telat?');

        $this->assertNotEmpty($overflow, 'Data yang tidak kebudget harus tersimpan untuk giliran retry.');
        $this->assertLessThanOrEqual(1500, mb_strlen($text));
        $this->assertStringNotContainsString('data dipotong', $text);

        // Bagian yang paling relevan (jadwal pengembalian hari ini) harus tetap
        // masuk walau bagian yang tidak relevan keburu memenuhi budget.
        $this->assertContains('JADWAL PENGEMBALIAN HARI INI', $loaded);
        $this->assertNotContains('KETERSEDIAAN UNIT TOKO (stok & status siap)', $loaded);

        // Bagian yang tidak kebudget tetap utuh, bukan dipotong jadi sia-sia.
        $this->assertStringContainsString('KETERSEDIAAN UNIT TOKO', implode("\n\n", $overflow));
    }

    public function test_prompt_yang_dikirim_ke_model_memuat_data_lengkap(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiSays('Rina telat 90 menit, iPhone 15 masih ready.')
            ),
        ]);

        $reply = GeminiAIService::replyInternal('ip 13 ready??', 'Rani');

        $this->assertSame('Rina telat 90 menit, iPhone 15 masih ready.', $reply);

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            foreach (self::ALWAYS_LOADED as $label) {
                if (! str_contains($prompt, $label)) {
                    $this->fail("Bagian data {$label} tidak terkirim ke model.");
                }
            }

            $this->assertStringContainsString('AKSES PENUH ke SELURUH data bisnis', $prompt);
            $this->assertStringContainsString('SIAP DIPAKAI sekarang', $prompt);
            $this->assertStringContainsString('Rina Wijaya', $prompt);
            $this->assertStringNotContainsStringIgnoringCase('sebut kata kunci', $prompt);
            $this->assertStringNotContainsString('data dipotong', $prompt);

            return true;
        });

        // Giliran ini harus tersimpan sebagai memori grup, supaya "yang tadi"
        // di pertanyaan berikutnya tetap nyambung.
        $this->assertDatabaseHas('ai_messages', ['role' => 'user', 'content' => 'ip 13 ready??']);
        $this->assertDatabaseHas('ai_messages', ['role' => 'model']);
    }

    public function test_jawaban_yang_menyuruh_pakai_kata_kunci_diteruskan_dengan_data_tambahan(): void
    {
        Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => '1200']);
        Cache::flush();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->geminiSays('Data tidak ikut dimuat, sebut kata kunci.'))
                ->push($this->geminiSays('Rina telat 90 menit ya bos.')),
        ]);

        $reply = GeminiAIService::replyInternal('siapa yang telat?', 'Rani');

        $this->assertSame('Rina telat 90 menit ya bos.', $reply);
        $this->assertCount(2, Http::recorded(), 'Giliran kedua harus benar-benar terjadi.');

        $retry = Http::recorded()[1][0]['contents'][0]['parts'][0]['text'];
        $this->assertStringContainsString('BAGIAN DATA TAMBAHAN', $retry);
        $this->assertStringContainsString('Jangan minta kata kunci', $retry);
    }

    public function test_kehabisan_token_per_menit_tidak_mengorbankan_blok_data(): void
    {
        $prompt = "aturan-aturan\n\nYANG SUDAH DIPBAHAS (MEMORI TIM):\n- obrolan lama sekali\n\nOBROLAN TERAKHIR:\n1. Tim: ip 13 ready\n   Kamu: ready\n\nDATA BISNIS LENGKAP (bagian yang tersedia: RINGKASAN & OMSET):\nRINGKASAN & OMSET:\n- omset: 1.000.000\n\nPertanyaan tim: \"gimana?\"";
        $rapat = $this->callService('shrinkInternalPrompt', [$prompt]);

        $this->assertStringContainsString('DATA BISNIS LENGKAP', $rapat);
        $this->assertStringContainsString('Omset', str_replace('- omset', 'Omset', $rapat));
        $this->assertStringNotContainsString('OBROLAN TERAKHIR', $rapat);
        $this->assertStringNotContainsString('obrolan lama sekali', $rapat);
    }

    public function test_omset_per_status_memuat_nominal_bukan_cuma_jumlah_transaksi(): void
    {
        [$text, $loaded] = $this->buildData('kalo bulan ini dihitung yang completed dan paid jadi berapa?');

        $this->assertContains('OMSET PER STATUS (nominal tiap status)', $loaded);

        // September 2026 dari dataset: selesai 120.000 + 150.000, disewa 150.000 + 120.000.
        $this->assertStringContainsString('SELESAI: 2 trs / Rp 270.000', $text);
        $this->assertStringContainsString('SEDANG DISEWA: 2 trs / Rp 270.000', $text);
        $this->assertStringContainsString('MENUNGGU (belum bayar): 1 trs / Rp 180.000', $text);

        // Versi dashboard web: transaksi Lestari dibayar 2 September walau sewa-nya
        // mulai 28 Agustus, jadi SELESAI bulan ini jadi 3 transaksi.
        $this->assertStringContainsString('versi dashboard web (dari tanggal pembayaran)', $text);
        $this->assertStringContainsString('SELESAI: 3 trs / Rp 370.000', $text);

        // Status yang memang tidak ada transaksinya boleh hilang, asal tidak dikarang jadi 0 palsu.
        $this->assertStringNotContainsString('DIBATALKAN', $text);
    }

    public function test_pertanyaan_status_tanpa_kata_omset_tetap_muat_rincian_omset(): void
    {
        // Pertanyaan aslinya dari tim tidak pernah menyebut "omset" sama sekali.
        foreach ([
            'kalo bulan ini dihitung yang completed dan paid jadi berapa',
            'skrng hitungin yg cuma statusnya paid coba',
            'nominalnya berapa',
        ] as $question) {
            [, $loaded] = $this->buildData($question);
            $this->assertContains(
                'OMSET PER STATUS (nominal tiap status)',
                $loaded,
                "Rincian omset per status tidak dimuat untuk: {$question}"
            );
        }
    }

    public function test_omset_dihitung_dari_dua_dasar_supaya_selisih_dengan_web_bisa_dijelaskan(): void
    {
        [$text] = $this->buildData('di web kok omsetnya beda?');

        // Dasar "tanggal mulai sewa": September = 150.000 + 120.000 (renting) + 120.000 + 150.000 (selesai).
        $this->assertStringContainsString('Omset bulan ini (September 2026): Rp 540.000', $text);
        $this->assertStringContainsString('Total bulan ini (September 2026): dari tanggal mulai sewa Rp 540.000', $text);

        // Dasar "tanggal pembayaran" (sama dengan dashboard web): transaksi Lestari
        // dibayar 2 September walau sewa-nya mulai 28 Agustus, jadi angkanya beda.
        $this->assertStringContainsString('versi dashboard web (dari tanggal pembayaran) Rp 640.000', $text);
    }

    public function test_prompt_memaksa_jawab_nominal_bukan_jumlah_transaksi(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiSays('Oke bos.')),
        ]);

        GeminiAIService::replyInternal('cuma status paid bulan ini berapa duit?', 'Rani');

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            $this->assertStringContainsString('NOMINAL, BUKAN JUMLAH TRANSAKSI', $prompt);
            $this->assertStringContainsString('JANGAN balas cuma jumlah transaksi', $prompt);
            $this->assertStringContainsString('tanggal pembayaran', $prompt);
            $this->assertStringContainsString('OMSET PER STATUS', $prompt);

            return true;
        });
    }

    public function test_pengaturan_admin_menampilkan_dan_menyimpan_batas_data_laporan(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/admin/settings.blade.php'));
        $this->assertStringContainsString('wire:model="chatbot_report_data_limit"', $blade);
        $this->assertStringContainsString('tanpa batas', $blade);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-report@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // saveGeneralSettings menulis GEMINI_API_KEY ke .env. Isi .env dikembalikan
        // apa adanya supaya tes tidak mengubah konfigurasi lokal.
        $envPath = base_path('.env');
        $envBackup = file_exists($envPath) ? file_get_contents($envPath) : null;

        try {
            $component = Livewire::test(SettingsPanel::class)
                ->set('home_title', 'Rent Space')
                ->set('home_description', 'Rental iPhone')
                ->set('admin_wa', '081200000000')
                ->set('admin_address', 'Purwokerto');

            $this->assertSame('0', (string) $component->get('chatbot_report_data_limit'));

            $component->set('chatbot_report_data_limit', '0')->call('saveGeneralSettings');
            $this->assertSame('0', (string) Setting::getVal('chatbot_report_data_limit'));

            // Nilai ngawur dijaga di panel: negatif tetap berarti tanpa batas.
            $component->set('chatbot_report_data_limit', '-50')->call('saveGeneralSettings');
            $this->assertSame('0', (string) Setting::getVal('chatbot_report_data_limit'));

            $component->set('chatbot_report_data_limit', '30000')->call('saveGeneralSettings');
            $this->assertSame('30000', (string) Setting::getVal('chatbot_report_data_limit'));
            $this->assertSame(30000, $this->callService('internalDataBudget'));
        } finally {
            if ($envBackup !== null) {
                file_put_contents($envPath, $envBackup);
            }
        }
    }
}
