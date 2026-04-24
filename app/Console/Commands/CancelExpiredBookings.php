<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan pesanan online yang sudah melewati batas waktu pembayaran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Running CancelExpiredBookings Command...");

        // Ambil semua pesanan pending yang metodenya BUKAN cash
        $pendingBookings = Rental::where('status', 'pending')
            ->where('metode_pembayaran', '!=', 'cash')
            ->get();

        $cancelledCount = 0;
        $now = Carbon::now('Asia/Jakarta');

        foreach ($pendingBookings as $rental) {
            $expiryTime = null;

            // 1. Cek apakah sudah ada expiry_time dari Midtrans
            if ($rental->payment_details && isset($rental->payment_details['expiry_time'])) {
                $expiryTime = Carbon::parse($rental->payment_details['expiry_time'], 'Asia/Jakarta');
            } else {
                // 2. Jika belum pilih bank, kasih toleransi 15 menit dari sejak dibuat
                $expiryTime = $rental->created_at->addMinutes(15);
            }

            // 3. Jika sekarang sudah melewati batas waktu, eksekusi pembatalan
            if ($now->greaterThan($expiryTime)) {
                $rental->update([
                    'status' => 'cancelled',
                    'payment_details' => array_merge($rental->payment_details ?? [], [
                        'cancel_reason' => 'Automatically cancelled due to payment timeout'
                    ])
                ]);

                // Opsional: Batalkan juga di Midtrans jika ada order_id
                if (isset($rental->payment_details['order_id'])) {
                    try {
                        \Midtrans\Transaction::cancel($rental->payment_details['order_id']);
                    } catch (\Exception $e) {
                        // Abaikan jika gagal
                    }
                }

                $cancelledCount++;
                Log::info("Booking #{$rental->booking_code} cancelled due to timeout.");
            }
        }

        $this->info("Successfully cancelled {$cancelledCount} expired bookings.");
    }
}
