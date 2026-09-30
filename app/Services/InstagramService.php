<?php

namespace App\Services;

use App\Exceptions\InstagramApiException;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client tipis untuk Instagram Graph API (Content Publishing).
 *
 * Hanya akun Business/Creator yang bisa publish lewat API — akun personal
 * tidak punya endpoint ini sama sekali.
 *
 * Alur publish story selalu dua langkah dan tidak boleh dilewati:
 *   1. POST /{ig-user-id}/media          -> membuat "container", statusnya masih DRAFT
 *   2. POST /{ig-user-id}/media_publish  -> container dipromosikan jadi story tayang
 *
 * Kalau langkah 1 sukses tapi langkah 2 gagal, container itu akan kedaluwarsa
 * sendiri setelah 24 jam. Itu alasan kenapa kegagalan dicatat di tabel
 * `instagram_story_posts` — admin perlu tahu ada yang menggantung.
 *
 * Catatan penting: `image_url` harus bisa dijangkau Instagram dari internet
 * (HTTPS publik). Kalau file-nya masih di localhost, publish akan gagal dengan
 * "media fetch error" — bukan karena token-nya salah.
 */
class InstagramService
{
    public const GRAPH_HOST = 'https://graph.facebook.com';

    /**
     * Settings di tabel `settings` adalah sumber utama; env hanya fallback
     * supaya bisa dipakai di server yang tidak punya akses ke UI admin.
     */
    public static function accessToken(): string
    {
        $token = (string) (Setting::getVal('ig_access_token') ?: config('services.instagram.access_token', ''));

        return trim($token);
    }

    public static function igUserId(): string
    {
        return trim((string) (Setting::getVal('ig_user_id') ?: config('services.instagram.user_id', '')));
    }

    public static function isConfigured(): bool
    {
        return static::accessToken() !== '' && static::igUserId() !== '';
    }

    /**
     * Cek apakah token + IG User ID benar-benar bisa dipakai.
     *
     * @return array{ok: bool, message: string, username: ?string}
     */
    public static function testConnection(): array
    {
        if (!static::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Access token atau IG User ID masih kosong. Isi keduanya dulu di tab Instagram.',
                'username' => null,
            ];
        }

        try {
            $fields = 'id,username,name,followers_count';
            $response = static::request('get', static::igUserId(), array_filter([
                'fields' => $fields,
            ]));

            $username = (string) ($response['username'] ?? '');

            return [
                'ok' => true,
                'message' => 'Tersambung ke @' . $username . '.',
                'username' => $username,
            ];
        } catch (InstagramApiException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'username' => null];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'Tidak bisa menghubungi Instagram: ' . $e->getMessage(), 'username' => null];
        }
    }

    /**
     * Publish satu story dari URL gambar publik.
     *
     * @return array{ok: bool, message: string, media_id: ?string}
     *
     * @throws InstagramApiException
     */
    public static function publishStory(string $imageUrl, string $caption = ''): array
    {
        if (!static::isConfigured()) {
            throw new InstagramApiException('Access token atau IG User ID belum diisi.');
        }

        static::assertReachableImageUrl($imageUrl);

        $creationId = static::createStoryContainer($imageUrl, $caption);
        $mediaId = static::publishContainer($creationId);

        return [
            'ok' => true,
            'message' => 'Story berhasil ditayangkan.',
            'media_id' => $mediaId,
        ];
    }

    /**
     * Instagram menarik `image_url` dari server Meta, bukan dari browser kita.
     * Kalau URL-nya localhost, HTTP tanpa TLS, atau host internal, permintaannya
     * tidak akan pernah sampai ke file kita dan Meta hanya membalas
     * "media fetch error" — yang berguna untuk siapa pun.
     *
     * Dicek sebelum container dibuat supaya kegagalan ini tidak menghabiskan
     * satu slot publish dan tidak menyisakan container menggantung.
     *
     * @throws InstagramApiException
     */
    public static function assertReachableImageUrl(string $imageUrl): void
    {
        if ($imageUrl === '' || !preg_match('#^(https?)://#i', $imageUrl, $m)) {
            throw new InstagramApiException('URL gambar tidak valid. Instagram hanya bisa menarik gambar dari URL publik.');
        }

        $host = strtolower((string) (parse_url($imageUrl, PHP_URL_HOST) ?: ''));

        if ($host === '') {
            throw new InstagramApiException('URL gambar tidak valid: host tidak terbaca.');
        }

        // Host lokal dicek lebih dulu: di development Almost semua URL sekaligus
        // HTTP dan localhost, dan "set APP_URL" adalah petunjuk yang berguna,
        // bukan "butuh HTTPS".
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost');

        if ($isLocalHost) {
            throw new InstagramApiException('URL gambar masih menunjuk ke localhost (' . $host . '). Set APP_URL ke domain HTTPS publik, karena Instagram mengambil gambar dari server Meta.');
        }

        if (strtolower($m[1]) !== 'https') {
            throw new InstagramApiException('URL gambar harus memakai HTTPS. Instagram menolak gambar yang diambil lewat HTTP biasa.');
        }
    }

    /**
     * @throws InstagramApiException
     */
    public static function createStoryContainer(string $imageUrl, string $caption = ''): string
    {
        $payload = array_filter([
            'image_url' => $imageUrl,
            'media_type' => 'STORIES',
            'caption' => $caption !== '' ? $caption : null,
        ], fn ($v) => $v !== null);

        $response = static::request('post', static::igUserId() . '/media', $payload);

        $creationId = (string) ($response['id'] ?? '');
        if ($creationId === '') {
            throw new InstagramApiException('Instagram tidak mengembalikan container ID untuk story ini.');
        }

        return $creationId;
    }

    /**
     * @throws InstagramApiException
     */
    public static function publishContainer(string $creationId): string
    {
        $response = static::request('post', static::igUserId() . '/media_publish', [
            'creation_id' => $creationId,
        ]);

        $mediaId = (string) ($response['id'] ?? '');
        if ($mediaId === '') {
            throw new InstagramApiException('Container ' . $creationId . ' sudah dibuat tapi tidak dipromosikan jadi story.');
        }

        return $mediaId;
    }

    /**
     * Reach story. Hanya tersedia 24 jam setelah story tayang, dan hanya
     * untuk story yang dibuat lewat API.
     *
     * @return array<string, int>
     */
    public static function insights(string $mediaId): array
    {
        try {
            $response = static::request('get', $mediaId . '/insights', [
                'metric' => 'impressions,reach',
            ]);
        } catch (InstagramApiException) {
            // Insights bukan bagian dari alur publish: jangan gagalkan hanya
            // karena angka views tidak bisa diambil.
            return [];
        }

        $metrics = [];
        foreach ($response['data'] ?? [] as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $metrics[$name] = (int) ($row['values'][0]['value'] ?? 0);
        }

        return $metrics;
    }

    /**
     * Satu-satunya tempat yang menyentuh HTTP, jadi format error Graph API
     * cukup dinormalisasi di satu file.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws InstagramApiException
     */
    protected static function request(string $method, string $path, array $params = []): array
    {
        $query = array_merge($params, ['access_token' => static::accessToken()]);

        $url = static::GRAPH_HOST . '/' . static::version() . '/' . ltrim($path, '/');

        $request = Http::timeout(20)->acceptJson();
        $response = $method === 'get'
            ? $request->get($url, $query)
            : $request->asForm()->post($url, $query);

        if ($response->failed()) {
            $body = $response->json();
            if (!is_array($body)) {
                throw new InstagramApiException('Instagram membalas HTTP ' . $response->status() . ' tanpa body JSON.');
            }

            Log::warning('InstagramService: ' . $method . ' ' . $path . ' gagal', [
                'status' => $response->status(),
                'error' => $body['error']['message'] ?? null,
            ]);

            throw InstagramApiException::fromResponse($body);
        }

        return (array) $response->json();
    }

    /**
     * Versi Graph API bisa diganti dari settings tanpa deploy ulang, karena
     * Meta menaikkan versi secara berkala dan versi lama dimatikan.
     */
    public static function version(): string
    {
        $version = trim((string) (Setting::getVal('ig_graph_version') ?: config('services.instagram.graph_version', 'v21.0')));

        return $version !== '' ? $version : 'v21.0';
    }
}
