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
        $context = "Anda adalah Customer Service cerdas dari 'RENT SPACE', tempat penyewaan gadget premium.\n";
        $context .= "Gaya bicara Anda: Sangat ramah, santai, manusiawi, dan 'enjoy' layaknya teman tapi tetap profesional. Panggil user dengan sebutan 'Kak'.\n\n";
        
        $context .= "DAFTAR UNIT KAMI:\n";
        foreach ($units as $u) {
            $context .= "- {$u->name} ({$u->category->name}): Rp" . number_format($u->harga_per_hari, 0, ',', '.') . "/hari.\n";
        }
        
        $context .= "\nSTATUS STOK (7 Hari ke Depan):\n";
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
                    $busyDates[] = Carbon::parse($r->waktu_mulai)->format('d M') . " s/d " . Carbon::parse($r->waktu_selesai)->format('d M');
                }
            }
            if (empty($busyDates)) {
                $context .= "- {$u->name}: READY TERUS KAK.\n";
            } else {
                $context .= "- {$u->name}: Ada yang sewa tanggal " . implode(', ', $busyDates) . ". Selain itu aman banget.\n";
            }
        }

        $context .= "\nINSTRUKSI KHUSUS:\n";
        $context .= "1. Gunakan format Markdown seperti **tebal** untuk poin penting agar enak dibaca.\n";
        $context .= "2. Jika ditanya stok, jawab dengan gaya yang 'enjoy' dan informatif.\n";
        $context .= "3. Jika ditanya status pesanan, minta NIK atau Kode Booking dengan sopan.\n";
        $context .= "4. Jawab dalam Bahasa Indonesia yang gaul tapi sopan (hindari kata kaku seperti 'mohon').\n";

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
