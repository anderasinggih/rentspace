<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use App\Models\Setting;
use Livewire\Component;
use Midtrans\Config;
use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;

class Success extends Component
{
    public $rental;
    public $tolerance;
    public $admin_wa;
    public $waUrl;
    public $isOwner = false;
    public $debugError = null;

    public function boot()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
    }

    public function mount($booking_code)
    {
        $this->rental = Rental::with('units')
            ->where('booking_code', $booking_code)
            ->firstOrFail();
            
        $this->tolerance = (int) Setting::getVal('late_tolerance_minutes', 60);
        $this->admin_wa = Setting::getVal('admin_wa', '6281234567890');
        $this->isOwner = in_array($booking_code, session('owned_bookings', []));

        // 1. CEK MIDTRANS DULU (Prioritas Utama)
        if ($this->rental->status === 'pending' && $this->rental->metode_pembayaran !== 'cash' && $this->rental->metode_pembayaran !== 'online') {
            $this->checkMidtransStatus(); // Update DB kalau emang sebenernya sudah bayar
            $this->rental->refresh();
        }

        // 2. GARI POLISI: Baru cek apakah sudah basi (Hanya jika masih pending)
        $isExpired = (now()->timestamp - $this->rental->created_at->timestamp >= 900);
        if ($this->rental->status === 'pending' && $isExpired) {
            // --- JURUS SAPU JAGAT: CANCEL SEMUA KEMUNGKINAN BANK ---
            $banks = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'QRIS'];
            foreach ($banks as $bank) {
                try {
                    $potentialId = $this->rental->booking_code . '-' . $bank;
                    \Midtrans\Transaction::cancel($potentialId);
                } catch (\Exception $e) { }
            }

            $this->rental->update(['status' => 'cancelled']);
            $this->rental = $this->rental->fresh();
        }

        // 3. Auto-login sesi untuk Cek Pesanan agar user tidak perlu ngetik NIK lagi nantinya
        session()->put('customer_session', [
            'nik'          => $this->rental->nik,
            'no_wa'        => $this->rental->no_wa,
            'logged_in_at' => now()->toISOString(),
            'expires_at'   => now()->addHours(6)->timestamp,
        ]);

        $this->waUrl = $this->generateWaUrl();
    }

    public function refreshStatus()
    {
        $this->rental = $this->rental->fresh();
        
        // 1. CEK MIDTRANS DULU
        if ($this->rental->status === 'pending' && $this->rental->metode_pembayaran !== 'cash' && $this->rental->metode_pembayaran !== 'online') {
            $this->checkMidtransStatus();
            $this->rental->refresh();
        }

        // 2. CEK TIMER (Hanya jika di Midtrans belum dibayar)
        if ($this->rental->status === 'pending' && (now()->timestamp - $this->rental->created_at->timestamp >= 900)) {
            // --- JURUS SAPU JAGAT ---
            $banks = ['BCA', 'BRI', 'BNI', 'MANDIRI', 'QRIS'];
            foreach ($banks as $bank) {
                try {
                    $potentialId = $this->rental->booking_code . '-' . $bank;
                    \Midtrans\Transaction::cancel($potentialId);
                } catch (\Exception $e) { }
            }

            $this->rental->update(['status' => 'cancelled']);
            $this->rental = $this->rental->fresh();
            return;
        }
    }

    private function checkMidtransStatus()
    {
        try {
            $details = $this->rental->payment_details;
            
            // Jika Cash, abaikan pengecekan Midtrans
            if ($this->rental->metode_pembayaran === 'cash') {
                return;
            }

            // Jika status masih 'online' (User belum milih bank di halaman depan)
            if ($this->rental->metode_pembayaran === 'online' && empty($details)) {
                return;
            }

            if (empty($details)) {
                $this->debugError = "Kolom payment_details di database KOSONG.";
                return;
            }

            $orderId = $details['order_id'] ?? null;
            
            if ($orderId) {
                $status = Transaction::status($orderId);
                $transactionStatus = $status->transaction_status;

                // Sync data detail dari Midtrans (termasuk expiry_time terbaru)
                $updatedDetails = array_merge($this->rental->payment_details ?? [], (array) $status);
                $this->rental->update(['payment_details' => $updatedDetails]);

                if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
                    $this->rental->update(['status' => 'paid']);
                } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                    $this->rental->update(['status' => 'cancelled']);
                }
            } else {
                $this->debugError = "Data ada tapi 'order_id' tidak ditemukan. Isi: " . json_encode($details);
            }
        } catch (\Exception $e) {
            // Jika Error 404 (Transaction doesn't exist), sembunyikan saja karena itu wajar di rilis awal
            if (str_contains($e->getMessage(), '404')) {
                $this->debugError = null;
                return;
            }
            $this->debugError = "Error: " . $e->getMessage();
        }
    }

    public function validateOrder()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') return;
        $this->rental->update(['status' => 'paid']);
        $this->rental = $this->rental->fresh();
    }

    public function cancelOrder()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') return;
        $this->rental->update(['status' => 'cancelled']);
        $this->rental = $this->rental->fresh();
    }

    private function generateWaUrl()
    {
        $orderId = str_pad($this->rental->id, 5, '0', STR_PAD_LEFT);
        $unitNames = $this->rental->units->pluck('seri')->join(', ');
        $startTime = $this->rental->waktu_mulai->format('d M Y, H:i');
        $endTime = $this->rental->waktu_selesai ? $this->rental->waktu_selesai->format('d M Y, H:i') : '-';
        $statusText = $this->rental->status === 'paid' ? 'LUNAS' : 'MENUNGGU PEMBAYARAN';
        $metode = strtoupper($this->rental->metode_pembayaran ?? 'Online');

        $text = "Halo Admin, saya ingin konfirmasi pesanan di *RENT SPACE*:\n\n" .
                "*Kode Booking:* {$this->rental->booking_code}\n" .
                "*Status:* {$statusText}\n" .
                "*Metode:* {$metode}\n" .
                "*Nama:* {$this->rental->nama}\n" .
                "*Unit:* {$unitNames}\n" .
                "*Waktu Sewa:* {$startTime} s/d {$endTime}\n" .
                "*Total Bayar:* Rp " . number_format($this->rental->grand_total, 0, ',', '.') . "\n" .
                "*Ref:* " . ($this->rental->affiliate_code ?: '-') . "\n\n" .
                "*Link Detail:* " . route('public.success', $this->rental->booking_code) . "\n\n" .
                "Mohon bantuannya untuk proses selanjutnya. Terima kasih!";

        return "https://wa.me/{$this->admin_wa}?text=" . urlencode($text);
    }

    public function render()
    {
        return view('livewire.front.success')->layout('layouts.app');
    }
}
