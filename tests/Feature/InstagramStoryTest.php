<?php

namespace Tests\Feature;

use App\Exceptions\InstagramApiException;
use App\Livewire\Admin\InstagramStory;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\UnitManager;
use App\Models\InstagramStoryPost;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Services\InstagramService;
use App\Services\InstagramStoryComposer;
use App\Services\InstagramStoryPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Story Instagram tidak bisa diuji lewat UI aslinya, jadi tes ini memalsukan
 * Graph API dan memeriksa tiga hal yang paling sering jadi penyebab gagal di
 * lapangan:
 *
 *   1. Template & renderer — apakah placeholder terisi dan file 1080×1920
 *      benar-benar terbentuk.
 *   2. Pemanggilan API — apakah dua langkah `media` lalu `media_publish`
 *      berurutan, dan apakah token ikut dikirim.
 *   3. Ketahanan — kegagalan tetap dicatat, dan URL localhost ditolak dengan
 *      pesan yang bisa dibaca admin.
 */
class InstagramStoryTest extends TestCase
{
    use RefreshDatabase;

    private const IG_USER_ID = '17841400000000000';
    private const TOKEN = 'EAAGtesttoken1234567890abcdefghijklmnop';
    private const GRAPH = 'https://graph.facebook.com/v21.0/';

    private ?string $documentRoot = null;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'ig_user_id'], ['value' => self::IG_USER_ID]);
        Setting::updateOrCreate(['key' => 'ig_access_token'], ['value' => self::TOKEN]);
        Setting::updateOrCreate(['key' => 'ig_graph_version'], ['value' => 'v21.0']);
        Setting::updateOrCreate(['key' => 'admin_address'], ['value' => 'Jl. Jend. Sudirman, Purwokerto']);
        Setting::updateOrCreate(['key' => 'social_ig_name'], ['value' => 'rentspace.id']);

        // Composer menulis ke `$_SERVER['DOCUMENT_ROOT']/uploads/...`. Arahkan
        // ke folder sementara supaya tes tidak mengotori folder publik repo.
        $this->documentRoot = sys_get_temp_dir() . '/rentspace-ig-test-' . bin2hex(random_bytes(4));
        if (!is_dir($this->documentRoot . '/uploads')) {
            mkdir($this->documentRoot . '/uploads', 0755, true);
        }
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;

        config(['app.url' => 'https://rentspace.co.id']);
    }

    protected function tearDown(): void
    {
        if ($this->documentRoot && is_dir($this->documentRoot)) {
            File::deleteDirectory($this->documentRoot);
        }

        unset($_SERVER['DOCUMENT_ROOT']);

        parent::tearDown();
    }

    private function unit(array $attributes = []): Unit
    {
        static $seq = 0;
        $seq++;

        return Unit::create(array_merge([
            'seri' => 'iPhone 13',
            'imei' => 'imei-ig-' . $seq,
            'memori' => '256GB',
            'warna' => 'Malam',
            'kondisi' => 'Mulus, BH 98%',
            'harga_per_jam' => 15000,
            'harga_per_hari' => 90000,
            'is_active' => true,
        ], $attributes));
    }

    private function fakeGraphSuccess(): void
    {
        Http::fake([
            self::GRAPH . self::IG_USER_ID . '/media' => Http::response(['id' => 'container-1'], 200),
            self::GRAPH . self::IG_USER_ID . '/media_publish' => Http::response(['id' => 'media-1'], 200),
            '*' => Http::response(['id' => 'container-1'], 200),
        ]);
    }

    // ---------------------------------------------------------------- renderer

    public function test_renderer_produces_1080x1920_png(): void
    {
        $rendered = (new InstagramStoryComposer($this->unit()))->render();

        $this->assertFileExists($rendered['path']);

        [$width, $height] = getimagesize($rendered['path']);
        $this->assertSame(1080, $width);
        $this->assertSame(1920, $height);

        $this->assertStringStartsWith('https://rentspace.co.id/uploads/ig-story/', $rendered['url']);
        $this->assertLessThanOrEqual(InstagramStoryComposer::MAX_BYTES, $rendered['bytes']);
    }

    public function test_templates_are_filled_from_unit_data(): void
    {
        $composer = new InstagramStoryComposer($this->unit());
        $caption = $composer->caption();

        $this->assertStringContainsString('iPhone 13', $caption);
        $this->assertStringContainsString('Malam 256GB', $caption);
        $this->assertStringContainsString('Rp 90.000/hari', $caption);
        $this->assertStringContainsString('Rp 15.000/jam', $caption);
        $this->assertStringContainsString('Purwokerto', $caption);
        $this->assertStringContainsString('https://rentspace.co.id/', $caption);
        $this->assertStringNotContainsString('{', $caption, 'Semua placeholder bawaan harus terisi.');

        $overlay = $composer->overlay();
        $this->assertStringContainsString('iPhone 13', $overlay);
        $this->assertStringNotContainsString('{', $overlay);

        // Placeholder opsional yang tidak dipakai template bawaan tetap harus
        // tersedia untuk template buatan admin.
        $this->assertSame('@rentspace.id', $composer->tokens()['{ig}']);
    }

    public function test_caption_template_from_settings_is_used(): void
    {
        Setting::updateOrCreate(
            ['key' => 'ig_story_caption_template'],
            ['value' => 'Promo {nama} hanya {harga_hari}! 📍 {lokasi}']
        );

        $caption = (new InstagramStoryComposer($this->unit()))->caption();

        $this->assertStringContainsString('Promo iPhone 13 hanya Rp 90.000/hari!', $caption);
    }

    public function test_unknown_placeholder_is_left_visible_so_typos_show_up(): void
    {
        $composer = new InstagramStoryComposer($this->unit());

        $this->assertStringContainsString('{harga_bulanan}', $composer->applyTemplate('Harga {harga_bulanan}'));
    }

    public function test_caption_is_truncated_to_instagram_limit(): void
    {
        Setting::updateOrCreate(
            ['key' => 'ig_story_caption_template'],
            ['value' => str_repeat('a', 3000)]
        );

        $this->assertSame(2200, mb_strlen((new InstagramStoryComposer($this->unit()))->caption()));
    }

    public function test_photo_is_used_when_unit_has_one(): void
    {
        $dir = $this->documentRoot . '/uploads/unit';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $canvas = imagecreatetruecolor(600, 800);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 200, 40, 60));
        imagejpeg($canvas, $dir . '/ada.jpg', 90);
        imagedestroy($canvas);

        $unit = $this->unit(['foto' => 'ada.jpg']);

        $withPhoto = (new InstagramStoryComposer($unit))->render();
        $this->assertFileExists($withPhoto['path']);

        // Tanpa foto pun harus tetap bisa dirender (dipakai logo usaha).
        $withoutPhoto = (new InstagramStoryComposer($this->unit()))->render();
        $this->assertFileExists($withoutPhoto['path']);

        $this->assertNotSame(
            md5_file($withPhoto['path']),
            md5_file($withoutPhoto['path']),
            'Gambar story harus berbeda antara unit yang punya foto dan yang tidak.'
        );
    }

    public function test_photo_is_ignored_when_stored_as_broken_file(): void
    {
        $dir = $this->documentRoot . '/uploads/unit';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Ekstensi tidak cocok dengan isi file: loader harus gagal diam-diam dan
        // story tetap tayang dengan login saja, bukan fatal error.
        file_put_contents($dir . '/rusak.jpg', 'bukan gambar sama sekali');

        $unit = $this->unit(['foto' => 'rusak.jpg']);

        $this->assertFileExists((new InstagramStoryComposer($unit))->render()['path']);
    }

    public function test_missing_photo_file_does_not_break_render(): void
    {
        $unit = $this->unit(['foto' => 'sudah-dihapus.jpg']);

        $this->assertFileExists((new InstagramStoryComposer($unit))->render()['path']);
    }

    // ----------------------------------------------------------------- service

    public function test_test_connection_requires_both_token_and_user_id(): void
    {
        Setting::updateOrCreate(['key' => 'ig_access_token'], ['value' => '']);

        $result = InstagramService::testConnection();

        $this->assertFalse($result['ok']);
        $this->assertFalse(InstagramService::isConfigured());
    }

    public function test_test_connection_reports_username(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => self::IG_USER_ID,
                'username' => 'rentspace.id',
                'name' => 'Rent Space',
                'followers_count' => 1200,
            ], 200),
        ]);

        $result = InstagramService::testConnection();

        $this->assertTrue($result['ok']);
        $this->assertSame('rentspace.id', $result['username']);
    }

    public function test_test_connection_surfaces_api_error_instead_of_throwing(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $result = InstagramService::testConnection();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Invalid OAuth access token', $result['message']);
    }

    public function test_graph_version_is_configurable_without_code_change(): void
    {
        Setting::updateOrCreate(['key' => 'ig_graph_version'], ['value' => 'v23.0']);

        $this->assertSame('v23.0', InstagramService::version());
    }

    public function test_publish_creates_container_then_publishes_it(): void
    {
        $this->fakeGraphSuccess();

        $result = InstagramService::publishStory('https://rentspace.co.id/uploads/ig-story/a.png', 'Halo');

        $this->assertTrue($result['ok']);
        $this->assertSame('media-1', $result['media_id']);

        $calls = Http::recorded();
        $this->assertCount(2, $calls);

        $this->assertSame('POST', $calls[0][0]->method());
        $this->assertStringEndsWith('/' . self::IG_USER_ID . '/media', $calls[0][0]->url());
        $this->assertSame('STORIES', $calls[0][0]['media_type']);
        $this->assertSame('https://rentspace.co.id/uploads/ig-story/a.png', $calls[0][0]['image_url']);
        $this->assertSame('Halo', $calls[0][0]['caption']);

        $this->assertStringEndsWith('/' . self::IG_USER_ID . '/media_publish', $calls[1][0]->url());
        $this->assertSame('container-1', $calls[1][0]['creation_id']);

        // Token harus ikut di setiap panggilan, kalau tidak Graph API balas 401.
        foreach ($calls as $call) {
            $this->assertSame(self::TOKEN, $call[0]['access_token']);
        }
    }

    public function test_container_without_id_is_treated_as_error(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $this->expectException(InstagramApiException::class);

        InstagramService::publishStory('https://rentspace.co.id/a.png');
    }

    public function test_localhost_image_url_is_rejected_before_calling_api(): void
    {
        Http::fake(['*' => Http::response(['id' => 'x'], 200)]);

        $this->expectException(InstagramApiException::class);
        $this->expectExceptionMessageMatches('/localhost/');

        try {
            InstagramService::publishStory('http://localhost:8000/uploads/ig-story/a.png');
        } finally {
            $this->assertCount(
                0,
                Http::recorded(),
                'Panggilan API tidak boleh terjadi untuk URL yang pasti ditolak Meta.'
            );
        }
    }

    public function test_plain_http_image_url_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['id' => 'x'], 200)]);

        $this->expectException(InstagramApiException::class);
        $this->expectExceptionMessageMatches('/HTTPS/');

        try {
            InstagramService::publishStory('http://cdn.example.com/a.png');
        } finally {
            $this->assertCount(0, Http::recorded());
        }
    }

    public function test_localhost_url_from_app_url_is_reported_readably(): void
    {
        // Ini kondisi produksi yang paling sering terjadi: `.env` lokal masih
        // `http://localhost` lalu di-deploy apa adanya.
        Http::fake(['*' => Http::response(['id' => 'x'], 200)]);
        config(['app.url' => 'http://localhost']);

        $outcome = app(InstagramStoryPublisher::class)->publish($this->unit(), 'Uji');

        $this->assertFalse($outcome['ok']);
        $this->assertStringContainsString('APP_URL', $outcome['message']);
        $this->assertCount(0, Http::recorded());
        $this->assertDatabaseHas('instagram_story_posts', ['status' => 'failed']);
    }

    public function test_insights_are_read_from_metric_values(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['name' => 'impressions', 'values' => [['value' => 812]]],
                    ['name' => 'reach', 'values' => [['value' => 640]]],
                ],
            ], 200),
        ]);

        $this->assertSame(['impressions' => 812, 'reach' => 640], InstagramService::insights('media-1'));
    }

    public function test_insights_failure_returns_empty_instead_of_throwing(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Unsupported get request.']], 400)]);

        $this->assertSame([], InstagramService::insights('media-1'), 'Insights gagal harus mengembalikan kosong, bukan melempar error.');
    }

    public function test_insights_are_stored_on_the_post_after_refresh(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [['name' => 'impressions', 'values' => [['value' => 99]]]],
            ], 200),
        ]);

        $post = InstagramStoryPost::create([
            'unit_id' => $this->unit()->id,
            'status' => 'published',
            'caption' => 'uji',
            'media_id' => 'media-1',
            'published_at' => now(),
        ]);

        $this->assertSame(['impressions' => 99], app(InstagramStoryPublisher::class)->refreshInsights($post));
        $this->assertSame(['impressions' => 99], $post->fresh()->insights);
    }

    // --------------------------------------------------------------- publisher

    public function test_publisher_logs_successful_post(): void
    {
        $this->fakeGraphSuccess();

        $admin = User::factory()->create(['role' => 'admin']);
        $unit = $this->unit();
        $outcome = app(InstagramStoryPublisher::class)->publish($unit, 'Kotestory', $admin->id);

        $this->assertTrue($outcome['ok']);
        $this->assertNotNull($outcome['post']->id);

        $this->assertDatabaseHas('instagram_story_posts', [
            'unit_id' => $unit->id,
            'status' => 'published',
            'caption' => 'Kotestory',
            'media_id' => 'media-1',
            'created_by' => $admin->id,
            'error_message' => null,
        ]);

        $this->assertNotNull($outcome['post']->published_at);
        $this->assertTrue($outcome['post']->isPublished());
    }

    public function test_publisher_can_run_without_known_author(): void
    {
        $this->fakeGraphSuccess();

        // CLI atau cron tidak punya `auth()`, jadi `created_by` harus boleh null.
        $outcome = app(InstagramStoryPublisher::class)->publish($this->unit(), 'Tanpa penulis');

        $this->assertTrue($outcome['ok']);
        $this->assertDatabaseHas('instagram_story_posts', ['created_by' => null, 'status' => 'published']);
    }

    public function test_publisher_logs_failure_instead_of_throwing(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Container already expired.']], 400)]);

        $outcome = app(InstagramStoryPublisher::class)->publish($this->unit(), 'Caption uji');

        $this->assertFalse($outcome['ok']);
        $this->assertStringContainsString('Container already expired', $outcome['message']);

        $post = InstagramStoryPost::latest('id')->first();
        $this->assertSame('failed', $post->status);
        $this->assertFalse($post->isPublished());
        $this->assertNull($post->media_id);
        $this->assertNotNull($post->error_message);

        // Gambar tetap harus ada supaya admin bisa lihat apa yang sebenarnya gagal.
        $this->assertNotNull($post->image_path);
        $this->assertFileExists($post->image_path);
    }

    public function test_publisher_records_render_failure_without_api_call(): void
    {
        Http::fake(['*' => Http::response(['id' => 'x'], 200)]);

        // Folder output tidak bisa dibuat karena `uploads/ig-story` sudah
        // dipakai file biasa.
        file_put_contents($this->documentRoot . '/uploads/ig-story', 'bukan folder');

        $outcome = app(InstagramStoryPublisher::class)->publish($this->unit(), 'Uji');

        $this->assertFalse($outcome['ok']);
        $this->assertDatabaseHas('instagram_story_posts', ['status' => 'failed', 'media_id' => null]);
        $this->assertCount(0, Http::recorded());
    }

    // ----------------------------------------------------------------- Livewire

    public function test_publish_page_requires_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::updateOrCreate(['key' => 'ig_access_token'], ['value' => '']);

        Livewire::actingAs($admin)
            ->test(InstagramStory::class)
            ->assertSee('Access token')
            ->set('unit_id', $this->unit()->id)
            ->call('publish')
            ->assertSet('lastError', 'Access token / IG User ID belum diisi. Isi dulu di Pengaturan → Instagram.');
    }

    public function test_preview_renders_without_touching_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(InstagramStory::class)
            ->set('unit_id', $this->unit()->id)
            ->call('preview')
            ->assertSet('lastError', null)
            ->assertSet('previewUrl', fn (?string $url) => str_starts_with((string) $url, 'https://rentspace.co.id/uploads/ig-story/'));

        $this->assertCount(0, Http::recorded(), 'Preview hanya merender, tidak boleh memanggil Instagram.');
    }

    public function test_publish_page_sends_caption_from_form(): void
    {
        $this->fakeGraphSuccess();

        $admin = User::factory()->create(['role' => 'admin']);
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(InstagramStory::class)
            ->set('unit_id', $unit->id)
            ->set('caption', 'Caption dari form')
            ->call('publish')
            ->assertSet('result.ok', true);

        $this->assertDatabaseHas('instagram_story_posts', [
            'unit_id' => $unit->id,
            'caption' => 'Caption dari form',
            'status' => 'published',
        ]);
    }

    // ---------------------------------------------------------------- settings

    public function test_settings_tab_saves_credentials_and_templates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Settings::class, ['tab' => 'instagram'])
            ->set('ig_user_id', '9998887776665555')
            ->set('ig_access_token', 'EAAGtokenbaru1234567890abcdefghij')
            ->set('ig_graph_version', 'v22.0')
            ->set('ig_story_caption_template', 'Unit {nama} {harga_hari}')
            ->set('ig_story_overlay_template', '{nama}')
            ->set('ig_story_theme', '#123456')
            ->call('saveInstagramSettings')
            ->assertHasNoErrors()
            ->assertSet('igStoryMessage.ok', true)
            // Token dikosongkan di form supaya tidak tampil lagi di layar.
            ->assertSet('ig_access_token', '');

        $this->assertSame('9998887776665555', Setting::getVal('ig_user_id'));
        $this->assertSame('EAAGtokenbaru1234567890abcdefghij', Setting::getVal('ig_access_token'));
        $this->assertSame('v22.0', Setting::getVal('ig_graph_version'));
        $this->assertSame('Unit {nama} {harga_hari}', Setting::getVal('ig_story_caption_template'));
        $this->assertSame('#123456', Setting::getVal('ig_story_theme'));
    }

    public function test_empty_token_input_keeps_previous_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Settings::class, ['tab' => 'instagram'])
            ->set('ig_story_theme', '#0f172a')
            ->call('saveInstagramSettings');

        $this->assertSame(self::TOKEN, Setting::getVal('ig_access_token'));
    }

    public function test_settings_rejects_invalid_theme_and_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Settings::class, ['tab' => 'instagram'])
            ->set('ig_graph_version', 'terlalu-baru')
            ->set('ig_story_theme', 'biru')
            ->call('saveInstagramSettings')
            ->assertHasErrors(['ig_graph_version', 'ig_story_theme']);
    }

    public function test_settings_test_connection_button_uses_entered_token(): void
    {
        Http::fake(['*' => Http::response(['username' => 'rentspace.id'], 200)]);

        $newToken = 'EAAGtokenbaru0987654321abcdefghijkl';
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(Settings::class, ['tab' => 'instagram'])
            ->set('ig_user_id', self::IG_USER_ID)
            ->set('ig_access_token', $newToken)
            ->call('testInstagramConnection')
            ->assertSet('igStoryMessage.ok', true);

        $this->assertSame($newToken, Setting::getVal('ig_access_token'));

        $this->assertStringContainsString(
            'access_token=' . $newToken,
            urldecode(Http::recorded()[0][0]->url())
        );
    }

    public function test_non_admin_cannot_save_instagram_settings(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        Livewire::actingAs($viewer)
            ->test(Settings::class, ['tab' => 'instagram'])
            ->set('ig_story_theme', '#ff0000')
            ->call('saveInstagramSettings');

        $this->assertNotSame('#ff0000', Setting::getVal('ig_story_theme'));
    }

    // -------------------------------------------------------------- unit photo

    /**
     * `storeFoto()` lebih suka `$_SERVER['DOCUMENT_ROOT']` kalau bisa ditulis —
     * sama seperti folder story. Di server sungguhan keduanya menunjuk ke folder
     * `public/`, jadi di tes pun diarahkan ke sana supaya jalur kodenya sama.
     */
    private function usePublicDirAsDocumentRoot(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = public_path();
    }

    private function category(): \App\Models\Category
    {
        return \App\Models\Category::firstOrCreate(
            ['slug' => 'iphone'],
            ['name' => 'iPhone']
        );
    }

    public function test_unit_manager_stores_photo_in_public_uploads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->category();
        $this->usePublicDirAsDocumentRoot();

        $upload = UploadedFile::fake()->image('iphone.jpg', 900, 1600)->size(500);

        Livewire::actingAs($admin)
            ->test(UnitManager::class)
            ->set('category_id', $category->id)
            ->set('seri', 'iPhone 14 Pro')
            ->set('imei', 'imei-foto-1')
            ->set('memori', '256GB')
            ->set('warna', 'Biru')
            ->set('kondisi', 'Mulus')
            ->set('harga_per_jam', 20000)
            ->set('harga_per_hari', 120000)
            ->set('foto', $upload)
            ->call('save');

        $unit = Unit::where('seri', 'iPhone 14 Pro')->first();

        $this->assertNotNull($unit);
        $this->assertNotNull($unit->foto);
        $this->assertMatchesRegularExpression('/^imei-foto-1-[0-9]+-[0-9a-f]{6}\.jpg$/', $unit->foto);
        $this->assertFileExists(public_path('uploads/unit/' . $unit->foto));

        @unlink(public_path('uploads/unit/' . $unit->foto));
    }

    public function test_unit_manager_rejects_non_image_photo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->category();

        Livewire::actingAs($admin)
            ->test(UnitManager::class)
            ->set('category_id', $category->id)
            ->set('seri', 'iPhone 11')
            ->set('imei', 'imei-virus-1')
            ->set('memori', '128GB')
            ->set('warna', 'Hitam')
            ->set('kondisi', 'Mulus')
            ->set('harga_per_jam', 10000)
            ->set('harga_per_hari', 60000)
            ->set('foto', UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'))
            ->call('save')
            ->assertHasErrors('foto');

        $this->assertNull(Unit::where('seri', 'iPhone 11')->first());
    }

    public function test_editing_unit_without_new_photo_keeps_existing_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $unit = $this->unit();

        $dir = public_path('uploads/unit');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/lama.jpg', 'dummy');
        $unit->forceFill(['foto' => 'lama.jpg'])->save();

        Livewire::actingAs($admin)
            ->test(UnitManager::class)
            ->call('edit', $unit->id)
            ->assertSet('fotoPreview', '/uploads/unit/lama.jpg')
            ->set('kondisi', 'Baret, BH 90%')
            ->call('save');

        $this->assertSame('lama.jpg', $unit->fresh()->foto);
        $this->assertFileExists($dir . '/lama.jpg');

        @unlink($dir . '/lama.jpg');
    }

    public function test_remove_photo_deletes_file_and_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $unit = $this->unit();

        $dir = public_path('uploads/unit');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/hapus.jpg', 'dummy');
        $unit->forceFill(['foto' => 'hapus.jpg'])->save();

        Livewire::actingAs($admin)
            ->test(UnitManager::class)
            ->call('edit', $unit->id)
            ->call('removeFoto');

        $this->assertNull($unit->fresh()->foto);
        $this->assertFileDoesNotExist($dir . '/hapus.jpg');
    }
}
