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
        $this->apiKey = config('services.gemini.key');
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
        
        $context .= "\nSTOK (7 Hari ke Depan):\n";
        $start = Carbon::today();
        $end = Carbon::today()->addDays(7);
        
        $rentals = Rental::where('status', '!=', 'cancelled')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('waktu_mulai', [$start, $end])
                  ->orWhereBetween('waktu_selesai', [$start, $end]);
            })
            ->with('units')
            ->get();

        foreach ($units as $u) {
            $busyDates = [];
            foreach ($rentals as $r) {
                if ($r->units->contains($u->id)) {
                    $busyDates[] = Carbon::parse($r->waktu_mulai)->format('d M') . "-" . Carbon::parse($r->waktu_selesai)->format('d M');
                }
            }
            if (empty($busyDates)) {
                $context .= "- {$u->name}: READY.\n";
            } else {
                $context .= "- {$u->name}: BOOKED " . implode(', ', $busyDates) . ". Selaian itu READY.\n";
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
                    'temperature' => 0.8,
                    'maxOutputTokens' => 1000,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf Kak, saya lagi blank. Bisa tanya lagi?";
            }

            $errorDetail = $response->json('error.message') ?? $response->body();
            return "Koneksi terganggu (Status: " . $response->status() . "). Pesan: " . substr($errorDetail, 0, 100);
        } catch (\Exception $e) {
            return "Kesalahan sistem: " . $e->getMessage();
        }
    }
}
