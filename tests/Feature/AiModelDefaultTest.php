<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Model default AI harus punya satu sumber kebenaran.
 *
 * Dulu string model disalin di tujuh tempat dan sudah tidak sinkron: service
 * memakai gemini-3.6-flash, halaman Pengaturan memakai gemini-3.5-flash-lite.
 * Akibatnya admin yang mengosongkan field model tersimpan model lama, padahal
 * dropdown menandai yang lain sebagai recommended.
 */
class AiModelDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

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

    public function test_model_kosong_disimpan_sebagai_default_service(): void
    {
        Livewire::test(SettingsPage::class)
            ->set($this->fillRequiredGeneralFields())
            ->set('chatbot_model', '')
            ->set('report_model', '')
            ->set('broadcast_model', '')
            ->call('saveGeneralSettings')
            ->assertHasNoErrors();

        foreach (['chatbot_model', 'report_model', 'broadcast_model'] as $key) {
            $this->assertSame(
                GeminiAIService::DEFAULT_MODEL,
                Setting::getVal($key),
                "{$key} harus jatuh ke default yang sama dengan service"
            );
        }
    }

    public function test_model_yang_dipilih_admin_tidak_di_override(): void
    {
        Livewire::test(SettingsPage::class)
            ->set($this->fillRequiredGeneralFields())
            ->set('chatbot_model', 'gemini-3.5-flash-lite')
            ->set('report_model', 'gemini-2.5-flash')
            ->set('broadcast_model', '')
            ->call('saveGeneralSettings')
            ->assertHasNoErrors();

        $this->assertSame('gemini-3.5-flash-lite', Setting::getVal('chatbot_model'));
        $this->assertSame('gemini-2.5-flash', Setting::getVal('report_model'));
        $this->assertSame(GeminiAIService::DEFAULT_MODEL, Setting::getVal('broadcast_model'));
    }

    public function test_halaman_pengaturan_terbuka_pakai_default_service(): void
    {
        Livewire::test(SettingsPage::class)
            ->assertSet('chatbot_model', GeminiAIService::DEFAULT_MODEL)
            ->assertSet('report_model', GeminiAIService::DEFAULT_MODEL)
            ->assertSet('broadcast_model', GeminiAIService::DEFAULT_MODEL);
    }

    public function test_model_lama_yang_tidak_ada_di_katalog_kembali_ke_default(): void
    {
        // Instalasi lama masih menyimpan model yang sudah dihapus Google.
        Setting::updateOrCreate(['key' => 'chatbot_model'], ['value' => 'gemini-2.0-flash']);

        $this->assertSame(
            GeminiAIService::DEFAULT_MODEL,
            GeminiAIService::modelFor('customer'),
            'model yang hilang dari katalog harus dinormalkan, bukan dikirim ke Google'
        );
    }

    public function test_model_default_adalah_yang_ditandai_recommended_di_dropdown(): void
    {
        $view = file_get_contents(resource_path('views/livewire/admin/settings.blade.php'));

        $this->assertStringContainsString(
            'value="' . GeminiAIService::DEFAULT_MODEL . '"',
            $view,
            'opsi recommended di dropdown harus menunjuk model default yang sama'
        );
    }
}
