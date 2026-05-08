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
        $address = \App\Models\Setting::getVal('admin_address') ?? 'Hubungi Admin via WhatsApp untuk alamat lengkap.';
        $context = "Anda adalah CS RENT SPACE. Bisnis ini menyewakan HP/iPhone. Gaya: Ramah, Enjoy, tapi TO-THE-POINT (Singkat & Padat). Panggil user 'Kak'.\n";
        $context .= "LOKASI TOKO: {$address}\n";
        $context .= "PENTING: Hanya berikan informasi berdasarkan DATA UNIT di bawah ini. DILARANG KERAS menawarkan produk lain yang tidak ada di daftar.\n";
        $context .= "PENJUALAN: Tawarkan tombol [BOOKING] hanya jika relevan, misalnya saat user bertanya harga, ketersediaan stok, jadwal, atau menunjukkan minat serius untuk menyewa. Jangan tawarkan di setiap pesan agar user merasa nyaman.\n\n";
        
        $context .= "DAFTAR UNIT & HARGA SEWA:\n";
        foreach ($units as $u) {
            $unitName = "{$u->seri} ({$u->memori}GB, Warna {$u->warna})";
            $context .= "- {$unitName}: Rp" . number_format($u->harga_per_hari, 0, ',', '.') . "/hari.\n";
        }

        // Add Public Promos
        $now = Carbon::now();
        $promos = \App\Models\PricingRule::where('is_active', 1)
            ->where('is_hidden', 0)
            ->where('is_affiliate_only', 0)
            ->where('requires_referral', 0)
            ->whereNull('target_loyalty_tier')
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->get();

        if ($promos->isNotEmpty()) {
            $context .= "\nPROMO/DISKON YANG SEDANG BERJALAN (HANYA BERIKAN JIKA RELEVAN):\n";
            foreach ($promos as $p) {
                $value = $p->tipe === 'percentage' ? $p->value . "%" : "Rp" . number_format($p->value, 0, ',', '.');
                $context .= "- Kode: {$p->kode_promo} ({$p->nama_promo}). Potongan: {$value}.";
                if ($p->syarat_minimal_durasi > 0) {
                    $context .= " Syarat: Min. sewa {$p->syarat_minimal_durasi} {$p->syarat_tipe_durasi}.";
                }
                $context .= "\n";
            }
        }
        
        $context .= "\nJADWAL & KETERSEDIAAN (BOOKING DATA):\n";
        $start = Carbon::today();
        
        $rentals = Rental::where('status', '!=', 'cancelled')
            ->where('waktu_selesai', '>=', $start)
            ->with('units')
            ->get();

        foreach ($units as $u) {
            $unitName = "{$u->seri} ({$u->memori}GB)";
            $busyDates = [];
            foreach ($rentals as $r) {
                if ($r->units->contains($u->id)) {
                    $busyDates[] = Carbon::parse($r->waktu_mulai)->format('d M') . " s/d " . Carbon::parse($r->waktu_selesai)->format('d M');
                }
            }
            if (empty($busyDates)) {
                $context .= "- {$unitName}: READY.\n";
            } else {
                $context .= "- {$unitName}: DIPESAN " . implode(', ', $busyDates) . ". Selain itu READY.\n";
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
