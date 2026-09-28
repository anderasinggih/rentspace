<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Memori percakapan AI (persistent, hemat token).
 *
 * Prinsipnya:
 * 1. Setiap sesi chat ditulis ke tabel (ai_conversations / ai_messages) supaya AI
 *    TETAP INGAT meski cache expired, bot restart, atau obrolan berlangsung
 *    berminggu-minggu ("yang kemarin itu gimana ya?").
 * 2. Yang dikirim balik ke model BUKAN seluruh history (itu bikin token membengkak),
 *    tapi ringkasan: fakta Durable + N turn terakhir dalam batas karakter.
 * 3. Ekstraksi fakta 100% deterministik (regex PHP) — TIDAK ada panggilan LLM
 *    tambahan, jadi tidak menambah token sama sekali.
 * 4. Ada plafon token input per menit (PITPM) supaya bot aman dari Error 429.
 */
class AiMemoryService
{
    /** Perkiraan rasio karakter -> token untuk teks Indonesia. */
    private const CHARS_PER_TOKEN = 3.2;

    /** Batas default token input per menit per channel (bisa diubah di Setting). */
    private const DEFAULT_TPM_LIMIT = 300000;

    /** Di atas 75% plafon, konteks tambahan dipangkas (bukan gagal). */
    private const DEGRADE_RATIO = 0.75;

    /** Jumlah fakta fokus yang disimpan per sesi. */
    private const MAX_FOCUS = 25;

    /** Jumlah topik terakhir yang dipakai sebagai jejak ("terakhir nanya ..."). */
    private const MAX_TOPICS = 6;

    public static function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * Sesi percakapan untuk satu penerima (grup report = satu sesi bersama).
     */
    public static function session(string $channel, string $peerId, ?string $peerName = null): AiConversation
    {
        try {
            return AiConversation::resolve($channel, $peerId, $peerName);
        } catch (\Throwable $e) {
            Log::warning('AiMemoryService::session gagal: ' . $e->getMessage());
            return new AiConversation(['channel' => $channel, 'memory' => []]);
        }
    }

    /**
     * Simpan satu giliran percakapan (user + jawaban model) beserta faktanya.
     */
    public static function saveTurn(
        AiConversation $conv,
        string $userMessage,
        string $answerText,
        string $intent = 'umum',
        int $inputTokens = 0,
        ?string $actor = null,
        array $sections = []
    ): void {
        if (!isset($conv->id)) {
            return;
        }

        try {
            foreach ([['user', $userMessage], ['model', $answerText]] as [$role, $content]) {
                AiMessage::create([
                    'ai_conversation_id' => $conv->id,
                    'role' => $role,
                    'actor' => $role === 'user' ? ($actor ?: $conv->peer_name) : null,
                    'content' => mb_substr($content, 0, 4000),
                    'intent' => mb_substr($intent, 0, 32),
                    'input_tokens' => $role === 'model' ? $inputTokens : 0,
                    'created_at' => now(),
                ]);
            }

            $memory = $conv->memory ?: [];
            $memory = self::extract($memory, $userMessage, $answerText, $intent);
            // Ingat bagian data apa yang dipakai terakhir, supaya pertanyaan
            // lanjutan ("trus yang tadi gimana?") memuat data yang sama lagi.
            // Digabung, bukan diganti, supaya konteks yang masih relevan tidak hilang.
            $merged = array_values(array_unique(array_merge(array_keys($sections), $memory['last_sections'] ?? [])));
            $memory['last_sections'] = array_slice($merged, 0, 5);
            $memory['last_intent'] = $intent;
            $conv->memory = $memory;
            $conv->turn_count = (int) $conv->turn_count + 1;
            $conv->input_tokens = (int) $conv->input_tokens + $inputTokens;
            $conv->last_active_at = now();
            if ($actor) {
                $conv->peer_name = mb_substr($actor, 0, 80);
            }
            $conv->save();

            // Jaga tabel tetap ringan: cukup 40 turn terakhir per sesi.
            $keepIds = AiMessage::where('ai_conversation_id', $conv->id)
                ->orderByDesc('id')
                ->limit(40)
                ->pluck('id');
            AiMessage::where('ai_conversation_id', $conv->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('AiMemoryService::saveTurn gagal: ' . $e->getMessage());
        }
    }

    /**
     * N turn terakhir sebagai pasangan user/model, dipotong per turn supaya tidak
     * ada satu jawaban panjang yang memakan seluruh budget.
     */
    public static function recentTurns(AiConversation $conv, int $maxTurns = 4, int $maxChars = 900): array
    {
        if (!isset($conv->id)) {
            return [];
        }

        try {
            $rows = AiMessage::where('ai_conversation_id', $conv->id)
                ->orderByDesc('id')
                ->limit($maxTurns * 2)
                ->get()
                ->reverse()
                ->values();

            $turns = [];
            $pendingUser = null;
            $budget = $maxChars;
            foreach ($rows as $row) {
                if ($row->role === 'user') {
                    $pendingUser = self::clip($row->content, 220);
                } else {
                    $pair = ['user' => (string) $pendingUser, 'model' => self::clip($row->content, 320)];
                    $cost = mb_strlen($pair['user']) + mb_strlen($pair['model']);
                    if ($budget - $cost < 0) {
                        break;
                    }
                    $budget -= $cost;
                    $turns[] = $pair;
                    $pendingUser = null;
                }
            }

            return array_reverse($turns);
        } catch (\Throwable $e) {
            Log::warning('AiMemoryService::recentTurns gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ringkasan memori yang relevan untuk pertanyaan saat ini.
     *
     * Hanya fakta yang nyambung dengan pertanyaan (kode booking / nama penyewa /
     * topik) yang dimuat, supaya AI ingat tanpa history numpuk.
     */
    public static function contextFor(AiConversation $conv, string $userMessage, int $maxChars = 420): string
    {
        $memory = $conv->memory ?: [];
        if (empty($memory)) {
            return '';
        }

        $question = mb_strtolower($userMessage);
        $lines = [];

        // 1. Catatan permanen dari tim (dipakai selalu, jumlahnya dibatasi kecil).
        foreach (array_slice($memory['notes'] ?? [], -3) as $note) {
            $lines[] = '- Catatan tim: ' . self::clip($note, 160);
        }

        // 2. Fakta yang namanya/kodenya disebut di pertanyaan.
        $matched = [];
        foreach ($memory['focus'] ?? [] as $item) {
            $code = (string) ($item['code'] ?? '');
            $name = (string) ($item['name'] ?? '');
            $hit = ($code !== '' && str_contains($question, mb_strtolower($code)))
                || ($name !== '' && mb_strlen($name) >= 3 && str_contains($question, mb_strtolower($name)));
            if ($hit) {
                $matched[] = $item;
            }
        }

        // 3. Kalau tidak ada yang cocok, bawa 2 fakta terakhir supaya
        //    pertanyaan lanjutan ("yang tadi gimana?") tetap nyambung.
        $focus = $matched ?: array_slice($memory['focus'] ?? [], -2);
        foreach (array_slice($focus, -4) as $item) {
            $lines[] = '- ' . self::focusLine($item);
        }

        // 4. Jejak topik terakhir.
        $topics = array_slice($memory['topics'] ?? [], -4);
        if ($topics) {
            $lines[] = '- Topik obrolan terakhir: ' . implode(' → ', $topics);
        }

        $text = implode("\n", array_filter($lines));
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return self::clip($text, $maxChars);
    }

    /**
     * Bagian data yang dipakai pada giliran sebelumnya (untuk pertanyaan lanjutan).
     */
    public static function lastSections(AiConversation $conv): array
    {
        $sections = $conv->memory['last_sections'] ?? [];
        return is_array($sections) ? $sections : [];
    }

    /**
     * Simpan catatan permanen (perintah /ingat) supaya tidak pernah hilang.
     */
    public static function pinNote(AiConversation $conv, string $note): void
    {
        $memory = $conv->memory ?: [];
        $notes = $memory['notes'] ?? [];
        $note = trim($note);
        if ($note !== '' && !in_array($note, $notes, true)) {
            $notes[] = mb_substr($note, 0, 300);
        }
        $memory['notes'] = array_values(array_slice($notes, -10));
        $conv->memory = $memory;
        $conv->save();
    }

    public static function clearMemory(AiConversation $conv, bool $keepNotes = true): void
    {
        $memory = $conv->memory ?: [];
        $conv->memory = $keepNotes ? ['notes' => $memory['notes'] ?? []] : [];
        $conv->save();

        if (isset($conv->id)) {
            AiMessage::where('ai_conversation_id', $conv->id)->delete();
        }
    }

    /**
     * Ringkasan memori untuk ditampilkan ke admin (perintah /memori).
     */
    public static function snapshot(AiConversation $conv): string
    {
        $memory = $conv->memory ?: [];
        $lines = [];

        $lines[] = '*Channel*: ' . $conv->channel;
        $lines[] = '*Pengguna*: ' . ($conv->peer_name ?: '-');
        $lines[] = '*Total percakapan*: ' . (int) $conv->turn_count . ' turn';
        $lines[] = '*Token input terpakai*: ' . number_format((int) $conv->input_tokens) . ' token';
        $lines[] = '*Aktif terakhir*: ' . ($conv->last_active_at ? $conv->last_active_at->translatedFormat('d M Y H:i') : '-');

        $notes = $memory['notes'] ?? [];
        $lines[] = '*Catatan tim*: ' . (empty($notes) ? '-' : count($notes) . ' catatan');

        $focus = $memory['focus'] ?? [];
        $lines[] = '*Fakta tersimpan*: ' . count($focus) . ' (maks ' . self::MAX_FOCUS . ')';
        foreach (array_slice($focus, -5) as $item) {
            $lines[] = '  ' . self::focusLine($item);
        }

        $topics = $memory['topics'] ?? [];
        if ($topics) {
            $lines[] = '*Topik*: ' . implode(' → ', array_slice($topics, -self::MAX_TOPICS));
        }

        return implode("\n", $lines);
    }

    /**
     * Catat pemakaian token input menit ini (PITPM guard).
     *
     * @return array{used:int,limit:int,ok:bool}
     */
    public static function consumeTokenBudget(string $channel, int $tokens): array
    {
        $limit = (int) Setting::getVal('chatbot_tpm_limit', self::DEFAULT_TPM_LIMIT);
        $limit = $limit > 0 ? $limit : self::DEFAULT_TPM_LIMIT;
        $key = 'ai_tpm_' . $channel;

        try {
            $used = (int) Cache::get($key, 0) + max(0, $tokens);
            Cache::put($key, $used, 60);
        } catch (\Throwable $e) {
            return ['used' => 0, 'limit' => $limit, 'ok' => true];
        }

        return [
            'used' => $used,
            'limit' => $limit,
            'ok' => $used <= (int) ($limit * self::DEGRADE_RATIO),
        ];
    }

    /**
     * Ekstraksi fakta deterministik dari pertanyaan + jawaban.
     * Tidak memanggil LLM, jadi nol token tambahan.
     */
    private static function extract(array $memory, string $userText, string $answerText, string $intent): array
    {
        $focus = $memory['focus'] ?? [];

        // Fakta dari jawaban AI: baris "• [STATUS] ... | Kode: RSXXXX".
        preg_match_all('/Kode:?\s*([A-Z0-9][A-Z0-9\-]{2,})/i', $answerText, $codes);
        foreach (array_unique($codes[1] ?? []) as $code) {
            $code = strtoupper($code);
            $line = self::lineForCode($answerText, $code);
            $item = [
                'code' => $code,
                'name' => self::matchValue($line, 'Penyewa:') ?: self::matchValue($line, '| Nama:') ?: self::matchValue($line, 'Nama:'),
                'status' => self::matchBracketStatus($line) ?: (self::matchValue($line, 'Status:') ?: ''),
                'unit' => self::matchValue($line, 'Unit:') ?: '',
                'info' => self::clip(trim((string) $line), 200),
                'at' => now()->toDateTimeString(),
            ];
            $item['flags'] = self::flags($line);

            $replaced = false;
            foreach ($focus as $i => $old) {
                if (strtoupper((string) ($old['code'] ?? '')) === $code) {
                    $focus[$i] = $item;
                    $replaced = true;
                    break;
                }
            }
            if (!$replaced) {
                $focus[] = $item;
            }
        }

        // Orang yang disebut pertanyaan (dipakai untuk "yang nama itu gimana?").
        $people = $memory['people'] ?? [];
        foreach ($focus as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if (mb_strlen($name) < 3 || self::isNoiseName($name)) {
                continue;
            }
            $people[$name] = [
                'code' => $item['code'] ?? '',
                'last' => $item['at'] ?? now()->toDateTimeString(),
            ];
        }

        $topics = $memory['topics'] ?? [];
        $topics[] = self::intentLabel($intent);
        if (count($topics) > self::MAX_TOPICS) {
            $topics = array_slice($topics, -self::MAX_TOPICS);
        }

        $memory['focus'] = array_values(array_slice($focus, -self::MAX_FOCUS));
        $memory['people'] = array_slice($people, -25, null, true);
        $memory['topics'] = array_values(array_unique($topics));
        $memory['last_question'] = self::clip($userText, 120);
        $memory['updated_at'] = now()->toDateTimeString();

        return $memory;
    }

    private static function intentLabel(string $intent): string
    {
        return match ($intent) {
            'jadwal_hari_ini' => 'Jadwal Hari Ini',
            'pengembalian' => 'Pengembalian',
            'terlambat' => 'Keterlambatan',
            'denda' => 'Denda & Kerusakan',
            'riwayat' => 'Riwayat Sewa',
            'pending' => 'Booking Menunggu',
            'cari_penyewa' => 'Cari Penyewa',
            'pendapatan' => 'Pendapatan',
            'katalog' => 'Data Unit',
            'cara_pesan' => 'Cara Pesan',
            'promo' => 'Promo',
            default => 'Umum',
        };
    }

    private static function focusLine(array $item): string
    {
        $parts = [];
        if (!empty($item['code'])) {
            $parts[] = $item['code'];
        }
        if (!empty($item['name'])) {
            $parts[] = $item['name'];
        }
        if (!empty($item['flags'])) {
            $parts[] = implode('/', $item['flags']);
        }
        $head = implode(' - ', $parts) ?: 'catatan';
        $info = trim((string) ($item['info'] ?? ''));

        return $head . ($info !== '' ? ' → ' . self::clip($info, 150) : '');
    }

    private static function lineForCode(string $text, string $code): string
    {
        foreach (preg_split('/\n/', $text) ?: [] as $line) {
            if (stripos($line, $code) !== false) {
                return $line;
            }
        }
        return '';
    }

    private static function matchValue(string $line, string $needle): ?string
    {
        if ($line === '' || !str_contains($line, $needle)) {
            return null;
        }
        $after = substr($line, strpos($line, $needle) + strlen($needle));
        $after = explode('|', $after)[0];
        return trim($after);
    }

    private static function matchBracketStatus(string $line): ?string
    {
        if (preg_match('/\[([A-Z ]+)\]/u', $line, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private static function flags(string $line): array
    {
        $flags = [];
        if (stripos($line, 'TERLAMBAT') !== false || stripos($line, 'MELEBIHI JADWAL') !== false) {
            $flags[] = 'TERLAMBAT';
        }
        if (stripos($line, 'Denda') !== false) {
            $flags[] = 'DENDA';
        }
        if (stripos($line, 'MENUNGGU') !== false || stripos($line, 'BELUM BAYAR') !== false) {
            $flags[] = 'BELUM BAYAR';
        }
        return $flags;
    }

    private static function isNoiseName(string $name): bool
    {
        $noise = ['-', 'Rp', 'Tidak ada', 'Penyewa', 'Nama', 'Total', 'Kode'];
        foreach ($noise as $n) {
            if (stripos($name, $n) === 0) {
                return true;
            }
        }
        return !preg_match('/[A-Za-z]{3}/u', $name);
    }

    private static function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
