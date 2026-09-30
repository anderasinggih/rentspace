<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Models\Setting;
use App\Services\NotifService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pengingat jadwal untuk karyawan, dikirim ke grup WhatsApp notifikasi.
 *
 * Dijadwalkan tiap 5 menit. Setiap jenis pengingat punya kolom penanda di
 * tabel `rentals`, jadi satu pesanan tidak akan menerima pesan yang sama dua
 * kali walau command ini berjalan berulang.
 *
 * Pesan disusun dari template statis (lihat NotifService), tanpa AI.
 */
class RemindStaffSchedule extends Command
{
    protected $signature = 'app:remind-staff
        {--pickup= : Menit sebelum jadwal ambil unit (default dari Pengaturan)}
        {--return= : Menit sebelum jadwal pengembalian unit (default dari Pengaturan)}
        {--late-after= : Menit toleransi sebelum alerting unit belum diambil/dikembalikan (default dari Pengaturan)}';

    protected $description = 'Kirim pengingat ambil & kembali unit ke grup WhatsApp tim';

    public function handle()
    {
        $pickupMinutes = max(5, (int) ($this->option('pickup') ?: Setting::getVal('notif_staff_pickup_minutes', '60')));
        $returnMinutes = max(5, (int) ($this->option('return') ?: Setting::getVal('notif_staff_return_minutes', '60')));
        $lateAfter = max(5, (int) ($this->option('late-after') ?: Setting::getVal('notif_staff_late_minutes', '30')));

        if (NotifService::targets() === []) {
            $this->warn('Tidak ada target notifikasi. Isi "ID Grup WA Notifikasi" atau nomor WA admin di Pengaturan > WhatsApp.');
            return self::SUCCESS;
        }

        $sent = 0;
        $sent += $this->remindPickup($pickupMinutes, $lateAfter);
        $sent += $this->remindReturn($returnMinutes, $lateAfter);
        $sent += $this->alertPickupLate($lateAfter);
        $sent += $this->alertReturnLate();

        $this->info("Pengingat dikirim: {$sent} pesan.");
        return self::SUCCESS;
    }

    /**
     * "1 jam lagi ada pengambilan" — hanya untuk pesanan lunas yang unitnya
     * belum diambil.
     */
    protected function remindPickup(int $minutes, int $lateAfter): int
    {
        $now = Carbon::now();

        $rentals = Rental::where('status', 'paid')
            ->whereNull('reminder_pickup_sent_at')
            ->whereNull('alert_pickup_late_sent_at')
            ->whereBetween('waktu_mulai', [$now->copy()->subMinutes($lateAfter), $now->copy()->addMinutes($minutes)])
            ->get();

        $sent = 0;
        foreach ($rentals as $rental) {
            // Sudah lewat jadwal? Cukup jadi alert telat, jangan kirim dua-duanya.
            if ($rental->waktu_mulai->lessThanOrEqualTo($now)) {
                continue;
            }

            if (NotifService::reminderPickup($rental, $minutes)) {
                $this->markSent($rental, 'reminder_pickup_sent_at');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * "1 jam lagi ada pengembalian" — hanya untuk unit yang sedang disewa.
     */
    protected function remindReturn(int $minutes, int $lateAfter): int
    {
        $now = Carbon::now();

        $rentals = Rental::where('status', 'renting')
            ->whereNull('reminder_return_sent_at')
            ->whereNull('alert_return_late_sent_at')
            ->whereBetween('waktu_selesai', [$now->copy()->subMinutes($lateAfter), $now->copy()->addMinutes($minutes)])
            ->get();

        $sent = 0;
        foreach ($rentals as $rental) {
            if ($rental->waktu_selesai->lessThanOrEqualTo($now)) {
                continue;
            }

            if (NotifService::reminderReturn($rental, $minutes)) {
                $this->markSent($rental, 'reminder_return_sent_at');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Lewat jadwal ambil tapi masih `paid` — customer kemungkinan belum datang.
     */
    protected function alertPickupLate(int $lateAfter): int
    {
        $rentals = Rental::where('status', 'paid')
            ->whereNull('alert_pickup_late_sent_at')
            ->where('waktu_mulai', '<=', Carbon::now()->subMinutes($lateAfter))
            ->get();

        $sent = 0;
        foreach ($rentals as $rental) {
            if (NotifService::alertPickupLate($rental, $lateAfter)) {
                $this->markSent($rental, 'alert_pickup_late_sent_at');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Lewat jadwal kembali tapi belum `completed`.
     */
    protected function alertReturnLate(): int
    {
        $rentals = Rental::where('status', 'renting')
            ->whereNull('alert_return_late_sent_at')
            ->where('waktu_selesai', '<=', Carbon::now())
            ->get();

        $sent = 0;
        foreach ($rentals as $rental) {
            if (NotifService::alertReturnLate($rental)) {
                $this->markSent($rental, 'alert_return_late_sent_at');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Tandai sudah dikirim tanpa memicu observer status.
     */
    protected function markSent(Rental $rental, string $column): void
    {
        $rental->forceFill([$column => now()])->saveQuietly();
    }
}
