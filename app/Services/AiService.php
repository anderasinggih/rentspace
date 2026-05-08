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
        $context = "Anda adalah Customer Service cerdas dari 'RENT SPACE', tempat penyewaan gadget dan alat fotografi premium.\n";
        $context .= "Gaya bicara Anda: Profesional, ramah, solutif, dan sedikit 'cool' (gunakan panggilan 'Bos' atau 'Kak').\n\n";
        
        $context .= "DAFTAR UNIT KAMI:\n";
        foreach ($units as $u) {
            $context .= "- {$u->name} ({$u->category->name}): Rp" . number_format($u->harga_per_hari, 0, ',', '.') . "/hari. ID: {$u->id}\n";
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
                $context .= "- {$u->name}: READY SETIAP HARI.\n";
            } else {
                $context .= "- {$u->name}: Sibuk pada " . implode(', ', $busyDates) . ". Selain tanggal itu Ready.\n";
            }
        }

        $context .= "\nINSTRUKSI KHUSUS:\n";
        $context .= "1. Jika ditanya stok, jawab berdasarkan data di atas secara spesifik.\n";
        $context .= "2. Jika ditanya status pesanan, minta NIK atau Kode Booking mereka.\n";
        $context .= "3. Selalu arahkan untuk booking melalui website jika mereka sudah mantap.\n";
        $context .= "4. Jawab dalam Bahasa Indonesia yang santai tapi sopan.\n";

        return $context;
    }

    public function ask($message, $history = [])
    {
        if (!$this->apiKey) {
            return "Maaf Bos, fitur AI belum dikonfigurasi (API Key kosong). Silakan hubungi Admin.";
        }

        $systemPrompt = $this->getSystemContext();
        
        $contents = [];
        // User Message
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
                    'maxOutputTokens' => 500,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf Bos, saya lagi blank. Bisa tanya lagi?";
            }

            $errorDetail = $response->json('error.message') ?? $response->body();
            return "Koneksi terganggu (Status: " . $response->status() . "). Pesan: " . substr($errorDetail, 0, 100);
        } catch (\Exception $e) {
            return "Kesalahan sistem: " . $e->getMessage();
        }
    }
}
