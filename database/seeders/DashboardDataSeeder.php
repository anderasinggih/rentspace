<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Rental;
use App\Models\Unit;
use App\Models\RentalItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all();
        if ($units->isEmpty()) {
            $this->command->error('No units found! Please create some units first.');
            return;
        }

        $startDate = Carbon::create(2026, 1, 1, 0, 0, 0);
        $endDate = Carbon::now();

        $numberOfTransactions = rand(150, 250); // Random amount of total orders
        
        $names = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hadi', 'Intan', 'Joko'];
        $locations = ['Surabaya', 'Sidoarjo', 'Malang', 'Gresik', 'Ambil Sendiri'];

        for ($i = 0; $i < $numberOfTransactions; $i++) {
            // Random timestamp between start and end date
            $timestamp = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));
            
            // Rental Duration
            $durationHours = rand(1, 48); // 1 to 48 hours
            $waktuSelesai = $timestamp->copy()->addHours($durationHours);

            // Cost calculation
            $pengiriman = rand(0, 50000);
            $diskon = rand(1, 10) > 8 ? rand(10000, 30000) : 0; // 20% chance of discount
            
            // Usually 1 or 2 units per order
            $amountOfUnits = rand(1, 10) > 8 ? 2 : 1; 
            $selectedUnits = $units->random($amountOfUnits);
            
            $subtotalUnits = 0;
            foreach ($selectedUnits as $u) {
                // Approximate price based on unit type or random base
                $priceHour = rand(20000, 50000);
                $subtotalUnits += ($priceHour * $durationHours);
            }

            $grandTotal = $subtotalUnits + $pengiriman - $diskon;

            // Generate Booking Code manually to simulate real model creation logic
            $code = strtoupper(Str::random(12));

            // Determine status based on dates
            $status = 'completed';
            $completedAt = $waktuSelesai;
            $handedOverAt = $timestamp;
            
            // Simulate some recent ones as currently active
            if ($waktuSelesai->isFuture() && $timestamp->isPast()) {
                $status = 'renting';
                $completedAt = null;
            } elseif ($timestamp->isFuture()) {
                $status = 'paid';
                $completedAt = null;
                $handedOverAt = null;
            }

            $rental = Rental::create([
                'booking_code' => $code,
                'nik' => (string)rand(3510000000000000, 3520000000000000),
                'nama' => $names[array_rand($names)] . ' ' . Str::random(3),
                'no_wa' => '0812' . rand(1000000, 9999999),
                'email' => strtolower($names[array_rand($names)]) . rand(1,99) . '@gmail.com',
                'alamat' => 'Jl. Random No. ' . rand(1, 99) . ', ' . $locations[array_rand($locations)],
                'sosial_media' => '@' . strtolower($names[array_rand($names)]),
                'waktu_mulai' => $timestamp,
                'waktu_selesai' => $waktuSelesai,
                'subtotal_harga' => $subtotalUnits,
                'potongan_diskon' => $diskon,
                'grand_total' => $subtotalUnits - $diskon,
                'status' => $status,
                'metode_pembayaran' => ['qris', 'transfer_bank'][rand(0,1)],
                'completed_at' => $completedAt,
                'handed_over_at' => $handedOverAt,
                'created_at' => $timestamp->copy()->subMinutes(rand(5, 60)),
                'updated_at' => $timestamp
            ]);

            foreach ($selectedUnits as $u) {
                RentalItem::create([
                    'rental_id' => $rental->id,
                    'unit_id' => $u->id,
                    'price_snapshot' => $subtotalUnits / $amountOfUnits // simple split for mock
                ]);
            }
        }

        $this->command->info("Successfully seeded {$numberOfTransactions} rentals from January 2026 to " . Carbon::now()->format('M Y'));
    }
}
