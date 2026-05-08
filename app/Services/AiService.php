<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $dbKey = \App\Models\Setting::getVal('chatbot_api_key');
        $this->apiKey = !empty($dbKey) ? $dbKey : config('services.gemini.key');
    }

    /**
     * Get the system prompt and business context.
     */
    public function getSystemContext()
    {
        $units = Unit::with('category')->get();
        $context = "Anda adalah CS RENT SPACE. Gaya: Ramah, Enjoy, tapi TO-THE-POINT (Singkat & Padat). Panggil user 'Kak'.\n";
        $context .= "Jangan bertele-tele. Langsung berikan informasi inti yang diminta.\n\n";
        
        $context .= "UNIT & HARGA:\n";
        foreach ($units as $u) {
            $context .= "- {$u->name}: Rp" . number_format($u->harga_per_hari, 0, ',', '.') . "/hari.\n";
        }
        
        $context .= "\nJADWAL & KETERSEDIAAN (BOOKING DATA):\n";
        $start = Carbon::today();
        
        $rentals = Rental::where('status', '!=', 'cancelled')
            ->where('waktu_selesai', '>=', $start)
            ->with('units')
            ->get();

        foreach ($units as $u) {
            $busyDates = [];
            foreach ($rentals as $r) {
                if ($r->units->contains($u->id)) {
                    $busyDates[] = Carbon::parse($r->waktu_mulai)->format('d M') . " s/d " . Carbon::parse($r->waktu_selesai)->format('d M');
                }
            }
            if (empty($busyDates)) {
                $context .= "- {$u->name}: STATUS READY (Belum ada booking).\n";
            } else {
                $context .= "- {$u->name}: SUDAH DIPESAN pada tanggal " . implode(', ', $busyDates) . ". Di luar tanggal tersebut statusnya READY.\n";
            }
        }

        $context .= "\nATURAN:\n";
        $context .= "1. Jawab SINGKAT & PADAT. Gunakan **bold** untuk poin inti.\n";
        $context .= "2. Jika ditanya stok, langsung jawab statusnya.\n";
        $context .= "3. Jika Kakak rasa user butuh bantuan manusia, atau user minta 'hubungi admin/orang asli', sampaikan bahwa Kakak akan menghubungkan mereka, lalu WAJIB sertakan kode ini di akhir jawaban: [CHAT_WA]\n";
        $context .= "4. Jangan gunakan kalimat basa-basi yang terlalu panjang.\n";

        return $context;
    }

    public function ask($message, $history = [])
    {
        if (!$this->apiKey) {
            return "Maaf Kak, fitur AI belum dikonfigurasi (API Key kosong). Silakan hubungi Admin.";
        }

        $systemPrompt = $this->getSystemContext();
        
        $contents = [];
        
        // Add History
        foreach ($history as $chat) {
            $contents[] = [
                'role' => ($chat['role'] === 'user' ? 'user' : 'model'),
                'parts' => [['text' => $chat['content']]]
            ];
        }

        // Add current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        try {
            $response = Http::post($this->baseUrl . '?key=' . $this->apiKey, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1500,
                ],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf Kak, saat ini saya sedang sedikit bingung. Bisa tanya lagi?";
            }

            // Handle Quota Limit or other errors gracefully
            if ($response->status() === 429) {
                return "Aduh Kak, maaf banget. Saat ini kuota chat saya lagi penuh nih. 🙏\n\nBiar cepet, Kakak bisa langsung tanya ke Admin lewat WhatsApp ya! [CHAT_WA]";
            }

            return "Waduh, koneksi saya lagi agak terganggu nih Kak. 😅\n\nLangsung chat Admin aja yuk biar dibantu manual! [CHAT_WA]";
        } catch (\Exception $e) {
            return "Maaf Kak, ada kendala teknis sebentar. Silakan hubungi Admin via WhatsApp ya! [CHAT_WA]";
        }
    }
}
