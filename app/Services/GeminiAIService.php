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
    public static function reply(string $userMessage, string $customerName = 'Kak'): ?string
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
        Log::info("GeminiAIService: Menjawab pesan customer '{$customerName}' menggunakan model {$model}");

        // Siapkan ringkasan data rental untuk knowledge base AI
        $units = Unit::where('is_active', true)->with('category')->get();
        $unitListText = "";
        foreach ($units as $u) {
            $cat = $u->category ? $u->category->name : 'Unit';
            $p24 = $u->harga_per_hari ? 'Rp ' . number_format($u->harga_per_hari, 0, ',', '.') . '/24 jam' : '-';
            $p12 = $u->harga_per_jam ? 'Rp ' . number_format($u->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : '-';
            $unitListText .= "- {$u->nama_lengkap} (Kategori: {$cat}, Harga: {$p24} atau {$p12})\n";
        }

        $address = Setting::getVal('admin_address', 'Purwokerto');
        $adminWa = Setting::getVal('admin_wa', '0881082411878');

        // Ambil Memori / Aturan Khusus Tambahan dari Database (chatbot_custom_knowledge)
        $rawMemories = Setting::getVal('chatbot_custom_knowledge', '[]');
        $customMemories = json_decode($rawMemories, true) ?: [];
        $memoryText = "";
        if (!empty($customMemories)) {
            $memoryText .= "\nATURAN KHUSUS & KNOWLEDGE TAMBAHAN (PENTING):\n";
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

        $systemPrompt = "Kamu adalah Customer Service WhatsApp di 'Rent Space Purwokerto' (rental iPhone, gadget, kamera di Purwokerto).
Customer yang chat: {$customerName}.

INFORMASI RENT SPACE:
- Lokasi Toko: {$address}
- WhatsApp Admin: {$adminWa}
- Website Booking Online: https://rentspacepurwokerto.my.id/booking
- Unit Yang Tersedia:
{$unitListText}{$memoryText}
GAYA BAHASA & CARA JAWAB (SANGAT PENTING):
1. JAWAB SEPERTI MANUSIA ASLI (ADMIN CS WA INDONESIA), BUKAN ROBOT/AI. Pakai gaya santai, ramah, sopan, dan singkat. Panggil dengan 'Kak {$customerName}' atau 'Kak'.
2. JANGAN PERNAH menyertakan kalimat formal seperti 'Jika ada yang ingin ditanyakan lagi silakan hubungi nomor ini...' atau 'Adakah hal lain yang bisa dibantu?'. Nomor admin hanya diberikan jika customer eksplisit minta bantuan admin/komplain/nego khusus.
3. JAWAB TO THE POINT:
   - Jika tanya ketersediaan/ready di tanggal tertentu (misal: 'ip 13 tgl 5-6 ready ga?'):
     Jawab singkat & lugas, contoh: 'Ready Kak! Bisa langsung diamankan & dipesan lewat web ya: https://rentspacepurwokerto.my.id/booking'
   - Jika tanya cara sewa: kasih tahu langsung booking online via website dengan pilih tanggal & unit.
   - Patuhi seluruh 'ATURAN KHUSUS & KNOWLEDGE TAMBAHAN' di atas jika relevan dengan pertanyaan customer.
4. Format pesan WhatsApp: Singkat (maksimal 2-4 baris), boleh pakai bold *tebal* untuk poin penting, emoji 1-2 saja secukupnya. JANGAN gunakan format markdown aneh seperti tanda pagar (###) atau bullets panjang.";

        try {
            $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nPesan Customer: \"" . $userMessage . "\"\n\nJawaban CS:"]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 250,
                ]
            ]);

            if ($response && $response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    $text = trim($candidates[0]['content']['parts'][0]['text']);
                    // Bersihkan tanda markdown header (###) jika ada
                    $text = preg_replace('/^#+\s*/m', '', $text);
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
