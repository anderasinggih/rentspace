<?php

namespace Database\Seeders;

use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@rentspace.com'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // Affiliate user
        $affiliate = User::updateOrCreate(
            ['email' => 'mas@affiliate.com'],
            [
                'name'     => 'Mas Affiliate',
                'password' => Hash::make('password'),
                'role'     => 'affiliator',
            ]
        );

        $affiliateProfile = \App\Models\AffiliatorProfile::updateOrCreate(
            ['user_id' => $affiliate->id],
            [
                'nik' => '3312012345670001',
                'no_hp' => '081223344556',
                'alamat' => 'Sokanegara, Purwokerto Timur',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Mas Affiliate',
                'commission_rate' => 10,
                'status' => 'approved',
                'referral_code' => \App\Models\AffiliatorProfile::generateCode('Mas Affiliate'),
            ]
        );

        // Units (skip if already seeded)
        if (Unit::count() === 0) {
            $units = [
                ['seri' => 'iPhone 15 Pro Max', 'imei' => '351234567890001', 'warna' => 'Natural Titanium', 'memori' => '256 GB', 'kondisi' => 'Mulus, BH 97%', 'harga_per_jam' => 25000, 'harga_per_hari' => 150000, 'is_active' => true],
                ['seri' => 'iPhone 15 Pro',     'imei' => '351234567890002', 'warna' => 'Blue Titanium',    'memori' => '128 GB', 'kondisi' => 'Mulus, BH 95%', 'harga_per_jam' => 20000, 'harga_per_hari' => 120000, 'is_active' => true],
                ['seri' => 'iPhone 14 Pro Max', 'imei' => '351234567890003', 'warna' => 'Space Black',      'memori' => '256 GB', 'kondisi' => 'Scratch kecil, BH 91%', 'harga_per_jam' => 18000, 'harga_per_hari' => 100000, 'is_active' => true],
                ['seri' => 'iPhone 14',         'imei' => '351234567890004', 'warna' => 'Midnight',         'memori' => '128 GB', 'kondisi' => 'Baik, BH 90%',     'harga_per_jam' => 15000, 'harga_per_hari' => 80000,  'is_active' => true],
            ];
            foreach ($units as $u) {
                Unit::create($u);
            }
        }

        $unitIds = Unit::pluck('id')->toArray();
        if (empty($unitIds)) return;

        $names   = ['Budi Santoso', 'Siti Rahayu', 'Andi Wijaya', 'Dewi Lestari', 'Rudi Hermawan', 'Ani Suryani', 'Hendra Kusuma', 'Maya Putri', 'Dian Permata', 'Fajar Nugroho'];
        $methods = ['qris', 'cash', 'qris', 'transfer', 'cash', 'qris'];

        // Generate realistic transactions from Jan 2026 to Apr 2026
        // More in recent months, creating a natural growth curve
        $batches = [
            // [start_date, end_date, count]
            ['2026-01-01', '2026-01-31', 18],  // Jan
            ['2026-02-01', '2026-02-28', 22],  // Feb
            ['2026-03-01', '2026-03-31', 30],  // Mar
            ['2026-04-01', '2026-04-11', 12],  // Apr (partial)
        ];

        $i = 0;
        foreach ($batches as [$from, $to, $count]) {
            $start = Carbon::parse($from);
            $end   = Carbon::parse($to);
            $days  = $start->diffInDays($end);

            for ($j = 0; $j < $count; $j++) {
                $daysOffset = rand(0, $days);
                $hours      = rand(2, 8);
                $startTime  = (clone $start)->addDays($daysOffset)->setHour(rand(8, 19))->setMinute(0)->setSecond(0);
                $endTime    = (clone $startTime)->addHours($hours);
                $unitId     = $unitIds[array_rand($unitIds)];
                $unit       = Unit::find($unitId);
                $subtotal   = $unit->harga_per_jam * $hours;
                $kode       = rand(100, 999);
                $grand      = $subtotal + $kode;
                $method     = $methods[array_rand($methods)];

                // Last ~10% per batch are pending/paid, rest completed
                $status = ($j >= $count - 2 && $from === '2026-04-01') ? 'pending'
                        : ($j >= $count - 3 && $from === '2026-04-01' ? 'paid' : 'completed');

                $rental = Rental::create([
                    'unit_id'              => $unitId,
                    'nik'                  => '3312' . str_pad(rand(1, 999999999999), 12, '0', STR_PAD_LEFT),
                    'nama'                 => $names[array_rand($names)],
                    'alamat'               => 'Jl. Contoh No. ' . rand(1, 99) . ', Purwokerto',
                    'no_wa'                => '08' . rand(100000000, 999999999),
                    'waktu_mulai'          => $startTime,
                    'waktu_selesai'        => $endTime,
                    'subtotal_harga'       => $subtotal,
                    'potongan_diskon'      => 0,
                    'kode_unik_pembayaran' => $kode,
                    'grand_total'          => $grand,
                    'status'               => $status,
                    'metode_pembayaran'    => $method,
                    'created_at'           => $startTime,
                    'updated_at'           => $startTime,
                    'affiliator_id'        => ($i % 5 === 0) ? $affiliate->id : null, // Every 5th rental is referred
                    'affiliate_code'       => ($i % 5 === 0) ? $affiliateProfile->referral_code : null,
                ]);

                if ($rental->affiliator_id && $status === 'completed') {
                    \App\Models\AffiliateCommission::create([
                        'affiliator_id' => $rental->affiliator_id,
                        'rental_id' => $rental->id,
                        'amount' => $rental->subtotal_harga * 0.1,
                        'status' => 'earned',
                        'created_at' => $startTime,
                    ]);
                }

                $i++;
            }
        }
    }
}
