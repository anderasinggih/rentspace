<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:cleanup-location-logs')->daily();
Schedule::command('app:prune-ai-memory')->dailyAt('04:30');
Schedule::command('app:monthly-report-reminder')->lastDayOfMonth('23:00');

// Pengingat ambil & kembalikan unit untuk karyawan. Menempel ke jadwal yang
// sudah ada, jadi cukup satu cron `schedule:run` per menit di server.
Schedule::command('app:remind-staff')
    ->everyFiveMinutes()
    ->withoutOverlapping();
