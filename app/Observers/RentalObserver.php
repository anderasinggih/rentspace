<?php

namespace App\Observers;

use App\Models\Rental;
use App\Services\NotifService;
use Illuminate\Support\Facades\Log;

/**
 * Setiap perubahan status pesanan dilaporkan ke tim.
 *
 * Observer dipakai (bukan pemanggilan manual di tiap halaman) karena status
 * rental berubah dari banyak jalur: panel admin, webhook Midtrans, QR scan,
 * halaman pembayaran customer, sampai command pembatalan otomatis. Kalau
 * notifikasi dipasang per halaman, satu jalur yang terlewat berarti tim tidak
 * tahu pesanan sudah dibatalkan atau customer sudah bayar.
 */
class RentalObserver
{
    public function created(Rental $rental): void
    {
        // `created` di dalam model event, jadi unit belum tentu sudah di-attach
        // (BookingForm menempelkannya setelah Rental::create). Tunda ke akhir
        // request supaya relasi units sudah terisi.
        $this->defer(function () use ($rental) {
            NotifService::bookingCreated($rental->fresh() ?? $rental);
        });
    }

    public function updated(Rental $rental): void
    {
        if (!$rental->wasChanged('status')) {
            return;
        }

        $from = (string) $rental->getOriginal('status');
        $to = (string) $rental->status;

        $this->defer(function () use ($rental, $from, $to) {
            try {
                NotifService::statusChanged($rental, $from, $to);
            } catch (\Throwable $e) {
                // Notifikasi tidak boleh menggagalkan transaksi yang sudah
                // tersimpan di database.
                Log::warning('RentalObserver: gagal mengirim notifikasi status: ' . $e->getMessage(), [
                    'booking_code' => $rental->booking_code,
                    'from' => $from,
                    'to' => $to,
                ]);
            }
        });
    }

    /**
     * Di request HTTP, notifikasi ditunda ke akhir request: relasi `units`
     * sudah terpasang dan karyawan tidak ikut menunggu balasan WhatsApp.
     *
     * Di luar HTTP (artisan, queue worker) tidak ada "akhir request" yang
     * bisa diandalkan, jadi langsung kirim supaya notifikasi tidak hilang
     * kalau prosesnya di-terminate sebelum selesai.
     */
    protected function defer(callable $callback): void
    {
        try {
            if (app()->runningInConsole()) {
                $callback();
                return;
            }

            app()->terminating($callback);
        } catch (\Throwable $e) {
            Log::warning('RentalObserver: gagal menjadwalkan notifikasi: ' . $e->getMessage());
        }
    }
}
