<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Saat customer minta dibantu admin (ketik kata "ADMIN"), bot Node.js mengirim
 * action forward_admin ke webhook. Notifikasi yang sampai ke admin WA harus
 * berisi pertanyaan asli customer dengan format simpel:
 *
 *     {pesan} ({nama} {4 digit terakhir no. WA})
 *
 * Bukan keyword literal "ADMIN" dan bukan banner panjang.
 */
class ForwardAdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'admin_wa_secondary'], ['value' => '08987654321']);
        Setting::updateOrCreate(['key' => 'admin_wa'], ['value' => '08123456789']);

        Http::fake([
            '*/send-message' => Http::response(['status' => true, 'message_id' => 'x'], 200),
        ]);
    }

    private function forwardAdmin(string $text, string $name = 'nwief', string $phone = '08987654321')
    {
        config(['services.whatsapp.api_key' => 'test-bot-key']);

        return $this->postJson('/api/v1/wa/webhook', [
            'sender_jid' => "{$phone}@s.whatsapp.net",
            'phone' => $phone,
            'name' => $name,
            'text' => $text,
            'action' => 'forward_admin',
        ], ['X-API-KEY' => 'test-bot-key']);
    }

    public function test_notifikasi_admin_berisi_pertanyaan_asli_customer(): void
    {
        $this->forwardAdmin('sewa mobil bisa? -admin');

        Http::assertSent(function ($request) {
            $sent = (string) $request['message'];

            return str_contains($sent, 'sewa mobil bisa? -admin')
                && str_contains($sent, 'nwief')
                && str_contains($sent, '4321');
        });
    }

    public function test_keluar_dua_kali_yaitu_grup_dan_wa_pribadi_admin(): void
    {
        Setting::updateOrCreate(['key' => 'admin_notify_group_id'], ['value' => '120363000000000000@g.us']);
        Setting::updateOrCreate(['key' => 'admin_wa_secondary'], ['value' => '08987654321']);

        $response = $this->forwardAdmin('kamera errornya gimana? -admin');

        $response->assertOk()
            ->assertJsonPath('status', true);

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            $sent = (string) $request['message'];
            return str_contains($sent, 'kamera errornya gimana? -admin (nwief 4321)');
        });
    }

    public function test_notifikasi_tidak_memakai_banner_panjang(): void
    {
        $this->forwardAdmin('bisa bantu dihitungin? -admin');

        Http::assertSent(function ($request) {
            $sent = (string) $request['message'];

            return !str_contains($sent, '🚨')
                && !str_contains($sent, 'CUSTOMER MINTA DIBANTU ADMIN')
                && !str_contains($sent, 'Link Chat')
                && !str_contains($sent, 'Nama');
        });
    }
}