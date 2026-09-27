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

        $model = Setting::getVal('chatbot_model', 'gemini-2.0-flash-lite');
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

        $systemPrompt = "Kamu adalah Customer Service AI yang ramah, sopan, dan solutif dari 'Rent Space Purwokerto' (layanan rental iPhone, gadget, dan kamera di Purwokerto).
Customer yang sedang chat bernama: {$customerName}.

INFORMASI RENT SPACE:
- Lokasi: {$address}
- WhatsApp Admin: {$adminWa}
- Website Booking Online: https://rentspacepurwokerto.my.id/booking
- Daftar Unit Tersedia Saat Ini:
{$unitListText}

PANDUAN MENJAWAB:
1. Jawab menggunakan bahasa Indonesia yang santun, ramah, dan ringkas layaknya CS manusia di WhatsApp. Panggil customer dengan 'Kak {$customerName}'.
2. Gunakan format WhatsApp (bukan markdown berlebihan). Boleh pakai *tebal* untuk poin penting atau emoji secukupnya. JANGAN gunakan tanda pagar (###) atau markdown tabel/bullet bintang ganda yang aneh di WA.
3. Jika customer bertanya tentang ketersediaan atau ingin booking, arahkan untuk booking online di https://rentspacepurwokerto.my.id/booking atau cek menu ketik KATALOG / CEK [KODE].
4. Jika pertanyaan di luar kewenangan (misal komplain berat, nego harga khusus, denda keterlambatan), persilakan untuk menghubungi Admin langsung di WA {$adminWa}.
5. Jawaban harus padat dan to the point, jangan terlalu panjang.";

        try {
            // Gunakan model yang dipilih oleh admin di pengaturan (tanpa fallback)
            $model = Setting::getVal('chatbot_model', 'gemini-2.0-flash-lite');

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
