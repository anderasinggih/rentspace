<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TvMusicFeed
{
    public function tracks(): array
    {
        $minutes = max(5, (int) config('tv.cache_minutes', 30));

        return Cache::remember('tv.music.tracks', now()->addMinutes($minutes), function () {
            $tracks = [];

            foreach ((array) config('tv.sources', []) as $source) {
                foreach ($this->fetch($source) as $track) {
                    $tracks[] = $track;
                }
            }

            $tracks = $this->dedupe($tracks);

            if (empty($tracks)) {
                return Cache::get('tv.music.fallback', []);
            }

            Cache::put('tv.music.fallback', $tracks, now()->addDays(7));

            return $tracks;
        });
    }

    protected function fetch(array $source): array
    {
        $type = ($source['type'] ?? 'channel') === 'playlist' ? 'playlist_id' : 'channel_id';
        $id = (string) ($source['id'] ?? '');

        if ($id === '') {
            return [];
        }

        try {
            $xml = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'RentSpaceTV/1.0'])
                ->get("https://www.youtube.com/feeds/videos.xml?{$type}=".urlencode($id))
                ->throw()
                ->body();
        } catch (\Throwable $e) {
            Log::warning('TV music feed gagal: '.$source['name'].' - '.$e->getMessage());

            return [];
        }

        if (! preg_match_all('#<entry>(.*?)</entry>#s', $xml, $matches)) {
            return [];
        }

        $tracks = [];

        foreach ($matches[1] as $entry) {
            if (! preg_match('#<yt:videoId>([^<]+)</yt:videoId>#', $entry, $video)) {
                continue;
            }

            $title = '';

            if (preg_match('#<media:group>.*?<media:title>(.*?)</media:title>#s', $entry, $media)) {
                $title = $media[1];
            } elseif (preg_match('#<title>(.*?)</title>#s', $entry, $plain)) {
                $title = $plain[1];
            }

            $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($title === '') {
                continue;
            }

            $tracks[] = [
                'video_id' => trim($video[1]),
                'title' => mb_substr($title, 0, 120),
                'source' => (string) ($source['name'] ?? 'YouTube'),
            ];
        }

        return $tracks;
    }

    protected function dedupe(array $tracks): array
    {
        $seen = [];
        $unique = [];

        foreach ($tracks as $track) {
            if (isset($seen[$track['video_id']])) {
                continue;
            }

            $seen[$track['video_id']] = true;
            $unique[] = $track;
        }

        return $unique;
    }
}