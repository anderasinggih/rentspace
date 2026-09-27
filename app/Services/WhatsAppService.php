<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.url', 'http://localhost:3001');
        $this->apiKey = config('services.whatsapp.api_key', 'rentspace_secret_wa_token_2026');
    }

    /**
     * Kirim pesan teks WhatsApp ke nomor tujuan
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/send-message", [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsAppService: Gagal mengirim pesan', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsAppService Exception: ' . $e->getMessage(), [
                'phone' => $phone,
            ]);
            return false;
        }
    }

    /**
     * Kirim notifikasi WhatsApp saat booking baru dibuat
     */
    public function sendBookingCreatedNotification(\App\Models\Rental $rental): bool
    {
        $phone = $rental->no_wa;
        if (!$phone) return false;

        $rental->loadMissing('units');
        $unitNames = $rental->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->filter()->implode(', ');
        if (empty($unitNames)) {
            $unitNames = 'Unit Rental';
        }

        $start = $rental->waktu_mulai ? \Carbon\Carbon::parse($rental->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
        $end = $rental->waktu_selesai ? \Carbon\Carbon::parse($rental->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
        $total = 'Rp ' . number_format($rental->grand_total ?: $rental->total_harga, 0, ',', '.');
        $paymentUrl = route('public.payment', $rental->booking_code);

        $msg = "Halo Kak *{$rental->nama}*! 👋\n\n" .
            "Terima kasih telah melakukan pemesanan di *Rent Space Purwokerto* 🎮📷\n\n" .
            "📋 *DETAIL BOOKING:*\n" .
            "• Kode Booking: *{$rental->booking_code}*\n" .
            "• Unit: *{$unitNames}*\n" .
            "• Jadwal Sewa: {$start} s/d {$end}\n" .
            "• Total Pembayaran: *{$total}*\n" .
            "• Status: *Menunggu Pembayaran*\n\n" .
            "Silakan selesaikan pembayaran Anda melalui tautan berikut:\n" .
            "👉 {$paymentUrl}\n\n" .
            "_Pesan ini dikirim otomatis oleh sistem Rent Space._";

        return $this->sendMessage($phone, $msg);
    }

    /**
     * Kirim notifikasi WhatsApp saat pembayaran lunas
     */
    public function sendPaymentSuccessNotification(\App\Models\Rental $rental): bool
    {
        $phone = $rental->no_wa;
        if (!$phone) return false;

        $rental->loadMissing('units');
        $unitNames = $rental->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->filter()->implode(', ');
        if (empty($unitNames)) {
            $unitNames = 'Unit Rental';
        }

        $start = $rental->waktu_mulai ? \Carbon\Carbon::parse($rental->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
        $end = $rental->waktu_selesai ? \Carbon\Carbon::parse($rental->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
        $total = 'Rp ' . number_format($rental->grand_total ?: $rental->total_harga, 0, ',', '.');
        $invoiceUrl = route('public.success', $rental->booking_code);

        $msg = "Halo Kak *{$rental->nama}*! 🎉\n\n" .
            "Pembayaran untuk pemesanan Anda telah *BERHASIL DITERIMA / LUNAS* ✅\n\n" .
            "📋 *DETAIL PESANAN:*\n" .
            "• Kode Booking: *{$rental->booking_code}*\n" .
            "• Unit: *{$unitNames}*\n" .
            "• Jadwal Sewa: {$start} s/d {$end}\n" .
            "• Total Lunas: *{$total}*\n\n" .
            "Invoice & Bukti Pembayaran dapat dilihat di:\n" .
            "👉 {$invoiceUrl}\n\n" .
            "Unit pesanan Kakak telah kami amankan. Sampai jumpa di waktu pengambilan unit ya Kak! ✨\n\n" .
            "_Rent Space Purwokerto - Sewa Game & Kamera Terbaik_";

        return $this->sendMessage($phone, $msg);
    }
}
