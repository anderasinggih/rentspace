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
            ->where('waktu_selesai', '>', now())
            ->with('units.category')
            ->get();

        if ($activeRentals->isEmpty()) {
            $this->command->warn('No active paid rentals found. Please create a rental first.');
            return;
        }

        foreach ($activeRentals as $rental) {
            foreach ($rental->units as $unit) {
                // Only seed for iPhones (Category ID 1 usually, or check name)
                if ($unit->category && str_contains(strtolower($unit->category->name), 'iphone')) {
                    
                    // Starting point (random-ish around a base coordinate, e.g., Purwokerto)
                    $baseLat = -7.4243 + (rand(-100, 100) / 10000);
                    $baseLng = 109.2303 + (rand(-100, 100) / 10000);

                    $this->command->info("Seeding path for {$unit->seri} ({$rental->nama})...");

                    // Generate a "walking" path of 20 points
                    for ($i = 20; $i >= 0; $i--) {
                        // Small increments to simulate movement
                        $baseLat += (rand(-15, 15) / 100000);
                        $baseLng += (rand(-15, 15) / 100000);

                        UnitLocation::create([
                            'unit_id' => $unit->id,
                            'lat' => $baseLat,
                            'lng' => $baseLng,
                            'address' => 'Simulated Movement Path',
                            'battery_level' => rand(20, 95),
                            'created_at' => Carbon::now()->subMinutes($i * 5), // Points spaced 5 mins apart
                            'updated_at' => Carbon::now()->subMinutes($i * 5),
                        ]);
                    }
                }
            }
        }

        $this->command->info('Unit locations seeded successfully! 🚀');
    }
}
