<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OneSignalService;

class MonthlyReportReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-report-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi push ke admin untuk pengingat rekapan akhir bulan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengirim pengingat rekapan bulanan...');

        try {
            OneSignalService::sendToAll(
                "📅 Akhir bulan telah tiba! Jangan lupa lakukan rekapan laporan keuangan dan ketersediaan unit untuk bulan ini.",
                "📊 PENGINGAT REKAPAN BULANAN",
                route('admin.monitoring') // Atau arahkan ke halaman laporan jika ada
            );
            $this->info('Notifikasi berhasil dikirim.');
        } catch (\Exception $e) {
            $this->error('Gagal mengirim notifikasi: ' . $e->getMessage());
        }
    }
}
