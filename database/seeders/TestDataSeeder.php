<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AffiliatorProfile;
use App\Models\AffiliatePayout;
use App\Models\Unit;
use App\Models\Category;
use App\Models\StaffLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users (for Customer/Settings management)
        echo "Seeding Users...\n";
        for ($i = 1; $i <= 30; $i++) {
            User::create([
                'name' => "User Test $i",
                'email' => "user$i@test.com",
                'password' => Hash::make('password'),
                'role' => $i % 5 == 0 ? 'staff' : ($i % 10 == 0 ? 'admin' : 'viewer'),
                'created_at' => now()->subDays(30 - $i),
            ]);
        }

        // 2. Seed Affiliate Profiles (Onboarding Requests)
        echo "Seeding Affiliate Profiles...\n";
        $affiliators = User::where('role', '!=', 'admin')->limit(15)->get();
        foreach ($affiliators as $index => $user) {
            AffiliatorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'no_hp' => '62812345678' . $index,
                    'nik' => '320101' . rand(100000, 999999) . rand(1000, 9999),
                    'alamat' => 'Alamat Test No. ' . ($index + 1),
                    'referral_code' => 'TEST' . Str::random(4) . ($index + 1), // Avoid duplicates if re-run
                    'commission_rate' => 10,
                    'bank_name' => 'BCA',
                    'bank_account_number' => '12345678' . $index,
                    'bank_account_name' => $user->name,
                    'status' => $index < 10 ? 'approved' : 'pending',
                    'created_at' => now()->subHours(15 - $index),
                ]
            );
        }

        // 3. Seed Payout Requests
        echo "Seeding Payout Requests...\n";
        $approvedProfiles = AffiliatorProfile::where('status', 'approved')->with('user')->get();
        foreach ($approvedProfiles as $index => $profile) {
            AffiliatePayout::create([
                'affiliator_id' => $profile->user_id,
                'amount' => 50000 * ($index + 1),
                'status' => $index % 2 == 0 ? 'pending' : 'processed',
                'created_at' => now()->subHours(20 - $index),
            ]);
        }

        // 4. Seed Units
        echo "Seeding Units...\n";
        $category = Category::first() ?? Category::create(['name' => 'iPhone', 'slug' => 'iphone']);
        for ($i = 1; $i <= 25; $i++) {
            Unit::create([
                'category_id' => $category->id,
                'seri' => "iPhone " . (11 + ($i % 5)) . " Pro Test $i",
                'imei' => 'IMEI' . rand(1000000, 9999999),
                'memori' => '128GB',
                'warna' => $i % 2 == 0 ? 'Graphite' : 'Silver',
                'kondisi' => 'Mulus',
                'is_active' => true,
                'harga_per_jam' => 15000,
                'harga_per_hari' => 150000,
            ]);
        }

        // 5. Seed Staff Logs
        echo "Seeding Staff Logs...\n";
        $staffs = User::whereIn('role', ['admin', 'staff'])->limit(5)->get();
        if ($staffs->count() > 0) {
            for ($i = 1; $i <= 100; $i++) {
                $staff = $staffs->random();
                StaffLog::create([
                    'user_id' => $staff->id,
                    'action' => $i % 3 == 0 ? 'update_rental' : ($i % 5 == 0 ? 'cancel_booking' : 'edit_unit'),
                    'description' => "Melakukan perubahan pada sistem (Log #$i)",
                    'ip_address' => '127.0.0.1',
                    'created_at' => now()->subHours(rand(1, 720)), // Random dates within last month
                ]);
            }
        }

        echo "Seeding completed successfully!\n";
    }
}
