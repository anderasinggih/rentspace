<?php

namespace App\Services;

use App\Exceptions\InstagramApiException;
use App\Models\InstagramStoryPost;
use App\Models\Unit;
use Throwable;

/**
 * Menggabungkan tiga hal yang selalu berjalan bareng: render gambar, kirim ke
 * Instagram, lalu catat hasilnya.
 *
 * Pencatatan sengaja dilakukan even kalau publish gagal. Kegagalan paling
 * sering terjadi di tahap "container jadi tapi tidak tayang" — kalau tidak
 * dicatat, admin akan bingung karena tidak ada salah-nya di Instagram tapi
 * apk-nya juga tidak bertambah.
 */
class InstagramStoryPublisher
{
    /**
     * @return array{ok: bool, message: string, post: InstagramStoryPost, image_url: ?string}
     */
    public function publish(Unit $unit, ?string $captionOverride = null, ?int $userId = null): array
    {
        $composer = new InstagramStoryComposer($unit);
        $caption = $captionOverride !== null && trim($captionOverride) !== ''
            ? mb_substr(trim($captionOverride), 0, 2200)
            : $composer->caption();

        $image = null;

        try {
            $image = $composer->render();
            $result = InstagramService::publishStory($image['url'], $caption);
        } catch (InstagramApiException $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'post' => $this->log($unit, 'failed', $caption, $image, null, $e->getMessage(), $userId),
                'image_url' => $image['url'] ?? null,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal menyiapkan story: ' . $e->getMessage(),
                'post' => $this->log($unit, 'failed', $caption, $image, null, $e->getMessage(), $userId),
                'image_url' => $image['url'] ?? null,
            ];
        }

        $post = $this->log($unit, 'published', $caption, $image, $result['media_id'], null, $userId);

        return [
            'ok' => true,
            'message' => $result['message'],
            'post' => $post,
            'image_url' => $image['url'],
        ];
    }

    /**
     * Ambil reach story. Dipisah dari `publish` karena insights baru tersedia
     * setelah story ditonton, jadi tidak bisa langsung diambil.
     *
     * @return array<string, int>
     */
    public function refreshInsights(InstagramStoryPost $post): array
    {
        if (!$post->isPublished()) {
            return [];
        }

        $insights = InstagramService::insights($post->media_id);
        if ($insights !== []) {
            $post->forceFill(['insights' => $insights])->save();
        }

        return $insights;
    }

    /**
     * @param  array{path: string, url: string, bytes: int}|null  $image
     */
    protected function log(
        Unit $unit,
        string $status,
        string $caption,
        ?array $image,
        ?string $mediaId,
        ?string $error,
        ?int $userId
    ): InstagramStoryPost {
        return InstagramStoryPost::create([
            'unit_id' => $unit->id,
            'status' => $status,
            'caption' => $caption,
            'image_path' => $image['path'] ?? null,
            'image_url' => $image['url'] ?? null,
            'media_id' => $mediaId,
            'error_message' => $error,
            'created_by' => $userId,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
