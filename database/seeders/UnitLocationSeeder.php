<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\UnitLocation;
use App\Models\Rental;
use Carbon\Carbon;

class UnitLocationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Find active paid rentals with iPhone units
        $activeRentals = Rental::where('status', 'paid')
            ->with(['units' => function($q) {
                $q->whereHas('category', function($cq) {
                    $cq->where('name', 'like', '%iphone%');
                });
            }, 'units.category'])
            ->get();

        if ($activeRentals->isEmpty()) {
            $this->command->warn('No active paid rentals found for iPhone units.');
            return;
        }

        foreach ($activeRentals as $rental) {
            foreach ($rental->units as $unit) {
                // Starting point: Purwokerto Base with slight random offset
                $currentLat = -7.4243 + (rand(-500, 500) / 100000);
                $currentLng = 109.2303 + (rand(-500, 500) / 100000);
                $battery = rand(80, 100);

                $this->command->info("Generating Advanced Path for {$unit->seri}...");

                // Clean existing locations for this unit first if needed or just add more
                // UnitLocation::where('unit_id', $unit->id)->delete();

                // Generate 50 points (the limit of our radar)
                for ($i = 50; $i >= 0; $i--) {
                    // Small "directional" movement to make it look like a path
                    $currentLat += (rand(-30, 30) / 100000);
                    $currentLng += (rand(-30, 30) / 100000);
                    
                    // Slightly drain battery
                    if ($i % 5 == 0 && $battery > 5) $battery--;

                    UnitLocation::create([
                        'unit_id' => $unit->id,
                        'lat' => $currentLat,
                        'lng' => $currentLng,
                        'address' => 'Simulated Movement Path V2',
                        'battery_level' => $battery,
                        'created_at' => Carbon::now()->subMinutes($i * 3), // Spaced 3 mins apart
                        'updated_at' => Carbon::now()->subMinutes($i * 3),
                    ]);
                }
            }
        }

        $this->command->info('Advanced Unit locations seeded successfully! 🚀');
    }
}
