<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    /**
     * Generate jawaban AI untuk customer chat WhatsApp
     */
    /**
     * Generate jawaban AI untuk customer chat WhatsApp dengan konteks percakapan & database monitoring
     */
    public static function reply(string $userMessage, string $customerName = 'Kak', ?string $senderJid = null, ?string $customerPhone = null): ?string
    {
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key'));

        if (!$apiKey) {
            Log::info('GeminiAIService: API Key belum diisi di Pengaturan.');
            return null;
        }

        $model = Setting::getVal('chatbot_model', 'gemini-3.5-flash-lite');
        // Jika model masih berisi model lama yang sudah deprecated, sesuaikan ke gemini-3.5-flash-lite
        if (in_array($model, ['gemini-2.0-flash-lite', 'gemini-1.5-flash-8b', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            $model = 'gemini-3.5-flash-lite';
        }

        // 1. Ambil session percakapan sebelumnya untuk user ini (Conversation Memory)
        $sessionKey = 'wa_chat_history_' . md5($senderJid ?: ($customerPhone ?: $customerName));
        $history = \Illuminate\Support\Facades\Cache::get($sessionKey, []);

        // 2. Data Master Unit
        $units = Unit::where('is_active', true)->with('category')->get();
        $unitListText = "";
        foreach ($units as $u) {
            $cat = $u->category ? $u->category->name : 'Unit';
            $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24 jam' : '-';
            $p12 = $u->harga_per_jam ? 'Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : '-';
            $unitListText .= "- [ID: {$u->id}] {$u->nama_lengkap} ({$cat}, {$p24} / {$p12})\n";
        }

        // 3. Konteks Monitoring Rental Saat Ini & Jadwal Terisi (Hingga 7 Hari ke Depan)
        // Ambil rental aktif (renting/paid) mulai dari hari ini
        $now = now();
        $nextWeek = now()->addDays(7);
        $activeRentals = \App\Models\Rental::with('units')
            ->whereIn('status', ['paid', 'renting', 'pending_confirmation'])
            ->where('waktu_selesai', '>=', $now)
            ->where('waktu_mulai', '<=', $nextWeek)
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        $scheduleText = "";
        if ($activeRentals->isNotEmpty()) {
            foreach ($activeRentals as $r) {
                $uNames = $r->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->implode(', ');
                $startStr = $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->translatedFormat('d M H:i') : '-';
                $endStr = $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->translatedFormat('d M H:i') : '-';
                $statusIndo = $r->status === 'renting' ? 'Sedang Dipakai' : 'Sudah Dibooking';
                $scheduleText .= "- {$uNames} ({$statusIndo} dari {$startStr} s/d {$endStr} WIB)\n";
            }
        } else {
            $scheduleText = "Semua unit saat ini belum ada booking terjadwal (bebas disewa).\n";
        }

        $address = Setting::getVal('admin_address', 'Purwokerto');
        $adminWa = Setting::getVal('admin_wa', '0881082411878');

        // 4. Ambil Memori / Aturan Khusus Tambahan dari Database (chatbot_custom_knowledge)
        $rawMemories = Setting::getVal('chatbot_custom_knowledge', '[]');
        $customMemories = json_decode($rawMemories, true) ?: [];
        $memoryText = "";
        if (!empty($customMemories)) {
            $memoryText .= "\nATURAN KHUSUS & KNOWLEDGE TAMBAHAN TOKO (PENTING):\n";
            foreach ($customMemories as $index => $mem) {
                $k = is_array($mem) ? ($mem['key'] ?? '') : '';
                $v = is_array($mem) ? ($mem['value'] ?? '') : (string) $mem;
                if ($k && $v) {
                    $memoryText .= "- {$k}: {$v}\n";
                } elseif ($v) {
                    $memoryText .= "- {$v}\n";
                }
            }
        }

        $currentTimeStr = now()->translatedFormat('l, d F Y H:i') . ' WIB';

        $systemPrompt = "Kamu adalah Customer Service WhatsApp di 'Rent Space Purwokerto' (rental iPhone, gadget, kamera di Purwokerto).
Waktu saat ini: {$currentTimeStr}.
Customer yang sedang chat bernama: {$customerName}.

INFORMASI RENT SPACE:
- Lokasi Toko: {$address}
- WhatsApp Admin: {$adminWa}
- Website Booking Online: https://rentspacepurwokerto.my.id/booking

DAFTAR UNIT TOKO:
{$unitListText}
STATUS JADWAL UNIT YANG SEDANG DISEWA / SUDAH DIBOOKING (REAL-TIME DARI DATABASE MONITORING):
{$scheduleText}
{$memoryText}
PANDUAN MENJAWAB (SANGAT PENTING):
1. GAYA BAHASA CS MANUSIA ASLI:
   - Jawab santai, ramah, to the point layaknya admin toko WA asli. Panggil 'Kak {$customerName}' atau 'Kak'.
   - JANGAN LEBAY! Maksimal 1 emoji saja atau tanpa emoji, JANGAN tabur banyak emoticon (hindari 😊✨🙏 sekaligus).
   - JANGAN PERNAH menyertakan kalimat penutup template seperti 'Jika ada yang ditanyakan lagi hubungi admin...' atau 'Ada yang bisa dibantu lagi?'. Cukup jawab pertanyaannya secara solutif.
2. INGAT PERCAKAPAN SEBELUMNYA (CONVERSATION CONTEXT):
   - Jika customer bertanya pertanyaan lanjutan seperti 'jam berapa?', 'kapan?', 'warnanya apa?', 'caranya?', LIHAT riwayat percakapan sebelumnya. Pahami unit mana yang sedang dibicarakan!
   - Contoh: Customer baru tanya 'ip 11 ready?', lalu tanya 'jam berapa?', artinya customer menanyakan jam berapa unit ip 11 tersebut bisa diambil atau jam buka toko/jam sewa unit tersebut! Jawab nyambung sesuai konteks unit tadi.
3. KETEPATAN JADWAL & MONITORING:
   - Periksa 'STATUS JADWAL UNIT' di atas. Jika ada jadwal sewa pada unit dan jam/tanggal yang ditanyakan, infokan dengan jujur kapan unit itu baru selesai/kembali.
   - Jika unit kosong di jam/tanggal tersebut, katakan ready dan arahkan booking langsung di web https://rentspacepurwokerto.my.id/booking.
4. Jawab singkat (2-3 kalimat saja).";

        try {
            // Bangun percakapan multi-turn dengan riwayat sebelumnya
            $contents = [];
            // Masukkan pesan sistem dan riwayat (maksimal 6 percakapan terakhir)
            $recentHistory = array_slice($history, -6);

            // Turn pertama diawali prompt sistem
            $firstMessageText = $systemPrompt;
            if (!empty($recentHistory)) {
                $firstTurn = true;
                foreach ($recentHistory as $turn) {
                    if ($firstTurn) {
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $systemPrompt . "\n\nPesan Customer: \"" . $turn['user'] . "\""]
                            ]
                        ];
                        $contents[] = [
                            'role' => 'model',
                            'parts' => [
                                ['text' => $turn['model']]
                            ]
                        ];
                        $firstTurn = false;
                    } else {
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $turn['user']]
                            ]
                        ];
                        $contents[] = [
                            'role' => 'model',
                            'parts' => [
                                ['text' => $turn['model']]
                            ]
                        ];
                    }
                }
                // Tambahkan pertanyaan saat ini
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ];
            } else {
                // Percakapan baru
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nPesan Customer: \"" . $userMessage . "\"\n\nJawaban CS:"]
                    ]
                ];
            }

            $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.6,
                    'maxOutputTokens' => 200,
                ]
            ]);

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    $text = trim($candidates[0]['content']['parts'][0]['text']);
                    $text = preg_replace('/^#+\s*/m', '', $text);

                    // Simpan ke riwayat session (berlaku 2 jam)
                    $history[] = [
                        'user' => $userMessage,
                        'model' => $text,
                        'time' => now()->toDateTimeString()
                    ];
                    // Simpan maksimal 10 riwayat
                    if (count($history) > 10) {
                        $history = array_slice($history, -10);
                    }
                    \Illuminate\Support\Facades\Cache::put($sessionKey, $history, 7200);

                    return $text;
                }
            } else {
                Log::warning('GeminiAIService Error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('GeminiAIService Exception: ' . $e->getMessage());
        }

        return null;
    }
}
