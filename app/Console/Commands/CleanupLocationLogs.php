<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UnitLocation;
use Carbon\Carbon;

class CleanupLocationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-location-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unit location logs older than 15 days to keep database clean';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of location logs...');

        $date = Carbon::now()->subDays(15);
        $count = UnitLocation::where('created_at', '<', $date)->delete();

        $this->info("Successfully deleted {$count} outdated location logs.");
    }
}
