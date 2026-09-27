<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedKey = config('services.whatsapp.api_key', 'rentspace_secret_wa_token_2026');

        if ($apiKey !== $expectedKey) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $phone = $request->input('phone');
        $name = $request->input('name', 'Kak');
        $text = trim((string) $request->input('text', ''));

        // 1. Cek perintah KATALOG / LIST / DAFTAR HARGA
        if (preg_match('/^(katalog|pricelist|harga|list|daftar\s*harga|1)$/i', $text)) {
            $reply = $this->buildCatalogResponse();
            return response()->json(['status' => true, 'reply' => $reply]);
        }

        // 2. Cek status booking (Format: "CEK RS-XXXX" atau "CEK [KODE]" atau ketik angka 2)
        if (preg_match('/^(cek|status|order)(\s+(.*))?$/i', $text, $matches) || $text === '2') {
            $codeQuery = isset($matches[3]) ? trim($matches[3]) : '';

            // Jika user hanya ketik "CEK" atau "2", coba cari rental terakhir berdasarkan nomor WA customer
            if (empty($codeQuery)) {
                $formattedPhone = $this->normalizePhone($phone);
                $rental = Rental::with('units')
                    ->where(function ($q) use ($phone, $formattedPhone) {
                        $q->where('no_wa', $phone)
                          ->orWhere('no_wa', $formattedPhone)
                          ->orWhere('no_wa', 'like', '%' . substr($formattedPhone, -9));
                    })
                    ->latest()
                    ->first();

                if ($rental) {
                    $reply = $this->formatRentalStatusResponse($rental, $name);
                    return response()->json(['status' => true, 'reply' => $reply]);
                }

                $reply = "Halo Kak *{$name}*, untuk mengecek status pesanan, silakan ketik:\n" .
                    "*CEK [KODE_BOOKING]*\n\n" .
                    "Contoh: *CEK RS123456*\n\n" .
                    "Kode booking bisa dilihat pada email invoice atau bukti pemesanan Kakak ya! 😊";
                return response()->json(['status' => true, 'reply' => $reply]);
            }

            // Jika mencantumkan kode booking
            $rental = Rental::with('units')
                ->where('booking_code', 'like', '%' . $codeQuery . '%')
                ->first();

            if (!$rental) {
                $reply = "Maaf Kak *{$name}*, pesanan dengan kode *{$codeQuery}* tidak ditemukan.\n\n" .
                    "Mohon pastikan kode booking sudah benar ya Kak! 🙏";
                return response()->json(['status' => true, 'reply' => $reply]);
            }

            $reply = $this->formatRentalStatusResponse($rental, $name);
            return response()->json(['status' => true, 'reply' => $reply]);
        }

        // Default: kembalikan null agar bot Node.js menampilkan menu bantuan
        return response()->json(['status' => true, 'reply' => null]);
    }

    private function buildCatalogResponse(): string
    {
        $units = Unit::where('is_active', true)->with('category')->get();

        if ($units->isEmpty()) {
            return "Halo Kak! Saat ini belum ada unit yang tersedia di katalog. Silakan cek website kami di https://rentspacepurwokerto.my.id 🙏";
        }

        $grouped = $units->groupBy(function ($u) {
            return $u->category ? $u->category->nama : 'Lainnya';
        });

        $res = "📋 *KATALOG & DAFTAR HARGA RENT SPACE* 🎮📷\n\n";

        foreach ($grouped as $catName => $items) {
            $res .= "📂 *Kategori: {$catName}*\n";
            foreach ($items as $item) {
                $price12h = $item->harga_12_jam ? 'Rp ' . number_format($item->harga_12_jam, 0, ',', '.') . '/12 jam' : null;
                $price24h = $item->harga_24_jam ? 'Rp ' . number_format($item->harga_24_jam, 0, ',', '.') . '/24 jam' : null;
                $prices = array_filter([$price12h, $price24h]);
                $priceStr = !empty($prices) ? implode(' | ', $prices) : 'Hubungi Admin';

                $res .= "• *{$item->nama}* ({$item->seri})\n";
                $res .= "  💰 {$priceStr}\n";
            }
            $res .= "\n";
        }

        $res .= "✨ *Booking Online sekarang:* https://rentspacepurwokerto.my.id/booking\n";
        $res .= "Ketik *MENU* untuk kembali ke menu utama.";

        return $res;
    }

    private function formatRentalStatusResponse(Rental $rental, string $name): string
    {
        $statusLabels = [
            'pending' => '⏳ Menunggu Pembayaran',
            'pending_confirmation' => '🔍 Menunggu Konfirmasi Admin',
            'paid' => '✅ Pembayaran Diterima / Terverifikasi',
            'renting' => '🚀 Sedang Berlangsung (Disewa)',
            'completed' => '🎉 Sewa Selesai',
            'cancelled' => '❌ Dibatalkan',
        ];

        $statusStr = $statusLabels[$rental->status] ?? ucfirst($rental->status);
        $unitNames = $rental->units->pluck('nama')->implode(', ');
        if (empty($unitNames)) {
            $unitNames = 'Unit Rental';
        }

        $start = $rental->waktu_mulai ? Carbon::parse($rental->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
        $end = $rental->waktu_selesai ? Carbon::parse($rental->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
        $total = 'Rp ' . number_format($rental->total_harga ?? 0, 0, ',', '.');

        $res = "📦 *DETAIL STATUS BOOKING*\n";
        $res .= "━━━━━━━━━━━━━━━━━━━━\n";
        $res .= "• *Kode Booking:* {$rental->booking_code}\n";
        $res .= "• *Nama:* {$rental->nama}\n";
        $res .= "• *Unit:* {$unitNames}\n";
        $res .= "• *Waktu Sewa:* {$start} s/d {$end}\n";
        $res .= "• *Total Biaya:* {$total}\n";
        $res .= "• *Status:* *{$statusStr}*\n";
        $res .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($rental->status === 'pending') {
            $res .= "👉 Kakak dapat melanjutkan pembayaran di:\nhttps://rentspacepurwokerto.my.id/payment/{$rental->id}\n\n";
        }

        $res .= "Terima kasih telah mempercayai Rent Space Purwokerto! 🙏";

        return $res;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '62')) {
            return '0' . substr($clean, 2);
        }
        return $clean;
    }
}
