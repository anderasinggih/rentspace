<?php

namespace Tests\Feature;

use App\Services\GeminiAIService;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifikasi gerbang "di luar topik sewa" untuk chat customer.
 *
 * Aturan mainnya: kalau chat customer tidak ada hubungannya dengan sewa unit,
 * AI tidak boleh menjawab isinya. Isi chat justru diteruskan ke admin, dan
 * penanda internal model tidak boleh bocor ke customer dalam bentuk apa pun.
 */
class CustomerOffTopicHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => 'test-key-off-topic']);
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

    public function test_chat_di_luar_topik_diarahkan_ke_admin_bukan_dijawab(): void
    {
        $this->fakeGemini('[[DI LUAR TOPIK]]');

        $result = GeminiAIService::customerReply(
            'tolong bantuin tugas matematika dong',
            'Budi',
            '628123@s.whatsapp.net',
            '628123'
        );

        $this->assertTrue($result['handoff'], 'chat di luar topik harus di-flag handoff');
        $this->assertNull($result['reply'], 'AI tidak boleh menjawab chat di luar topik');
    }

    public function test_penanda_yang_nempel_teks_lain_tetap_dianggap_handoff(): void
    {
        $this->fakeGemini('Ini penjelasan panjang soal tugas, [[DI LUAR TOPIK]]');

        $result = GeminiAIService::customerReply('besok UAS dong', 'Budi', '628123@s.whatsapp.net', '628123');

        $this->assertTrue($result['handoff']);
        $this->assertNull($result['reply'], 'teks yang sempat ditulis model tidak boleh ikut terkirim');
    }

    public function test_penanda_dalam_bentuk_apa_pun_tetap_terdeteksi(): void
    {
        $this->fakeGemini('**\[[DI LUAR TOPIK]\]**');

        $result = GeminiAIService::customerReply('cari lowongan kerja dong', 'Budi');

        $this->assertTrue($result['handoff'], 'penanda yang dibungkus markdown tetap harus kena');
    }

    public function test_pertanyaan_sewa_tetap_dijawab_normal(): void
    {
        $this->fakeGemini('ip 12 ada 2 unit kak, yang satu bebas besok jam 4 sore');

        $result = GeminiAIService::customerReply('ip 12 ready kapan?', 'Budi');

        $this->assertFalse($result['handoff'], 'pertanyaan sewa bukan handoff');
        $this->assertStringContainsStringIgnoringCase('ip 12', (string) $result['reply']);
    }

    public function test_penolakan_tanpa_penanda_ikut_dianggap_handoff(): void
    {
        $this->fakeGemini('Maaf kak, saya tidak bisa membantu soal itu.');

        $result = GeminiAIService::customerReply('tolong bantuin tugas dong', 'Budi');

        $this->assertTrue($result['handoff'], 'penolakan model tidak boleh sampai ke customer');
        $this->assertNull($result['reply']);
    }

    public function test_jawaban_maaf_yang_bukan_penolakan_tetap_dikirim(): void
    {
        $this->fakeGemini('Maaf kak, tank belum ada ya, yang ada iPhone sama PS3');

        $result = GeminiAIService::customerReply('sewa tank ada?', 'Budi');

        $this->assertFalse($result['handoff'], 'maaf karena unit tidak ada itu jawaban normal');
        $this->assertStringContainsStringIgnoringCase('tank', (string) $result['reply']);
    }

    public function test_prompt_meminta_model_menandai_chat_di_luar_topik(): void
    {
        $this->fakeGemini('halo kak, ada yang bisa dibantu?');

        GeminiAIService::customerReply('halo kak', 'Budi');

        Http::assertSent(function ($request) {
            $prompt = (string) $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, GeminiAIService::OFF_TOPIC_TOKEN)
                && str_contains($prompt, 'JANGAN menjawab isinya');
        });
    }

    public function test_webhook_kirim_flag_handoff_ke_bot(): void
    {
        config(['services.whatsapp.api_key' => 'test-bot-key']);
        $this->fakeGemini('[[DI LUAR TOPIK]]');

        $response = $this->postJson('/api/v1/wa/webhook', [
            'sender_jid' => '628123@s.whatsapp.net',
            'phone' => '628123',
            'name' => 'Budi',
            'text' => 'tolong bantuin tugas dong',
        ], ['X-API-KEY' => 'test-bot-key']);

        $response->assertOk()
            ->assertJsonPath('handoff', true)
            ->assertJsonPath('reply', null);
    }

    public function test_webhook_tetap_kirim_balasan_biasa_bila_tidak_handoff(): void
    {
        config(['services.whatsapp.api_key' => 'test-bot-key']);
        $this->fakeGemini('iphone 13 ready kak, mau hari ini atau besok?');

        $response = $this->postJson('/api/v1/wa/webhook', [
            'sender_jid' => '628123@s.whatsapp.net',
            'phone' => '628123',
            'name' => 'Budi',
            'text' => 'ip 13 ready kapan?',
        ], ['X-API-KEY' => 'test-bot-key']);

        $response->assertOk()
            ->assertJsonPath('handoff', null)
            ->assertJsonPath('reply', 'iphone 13 ready kak, mau hari ini atau besok?');
    }
}
