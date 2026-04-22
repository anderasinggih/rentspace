<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Rental;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Disable Foreign Keys
        Schema::disableForeignKeyConstraints();

        // 1. Truncate Tables
        User::truncate();
        Category::truncate();
        Unit::truncate();
        PricingRule::truncate();
        Rental::truncate();
        Setting::truncate();
        DB::table('rental_items')->truncate();
        DB::table('affiliator_profiles')->truncate();
        DB::table('affiliate_commissions')->truncate();
        DB::table('affiliate_payouts')->truncate();

        // 2. Define Categories
        Category::create(['id' => 1, 'name' => 'iPhone', 'slug' => 'iphone', 'icon' => 'smartphone']);
        Category::create(['id' => 3, 'name' => 'Aksesoris', 'slug' => 'aksesoris', 'icon' => 'headphones']);

        // 3. User Data
        $userData = [
            ["id"=> 1, "name"=> "Administrator", "email"=> "admin@rentspace.com", "role"=> "admin", "created_at"=> "2026-04-14 11:13:29"],
            ["id"=> 2, "name"=> "SINGGIH", "email"=> "singgih@gmail.com", "role"=> "admin", "created_at"=> "2026-04-15 06:19:05"],
            ["id"=> 3, "name"=> "RYAN CEO", "email"=> "ceoryan@gmail.com", "role"=> "viewer", "created_at"=> "2026-04-15 06:20:04"],
            ["id"=> 6, "name"=> "WILDA NASHUHA DWIMULYANTI", "email"=> "wildanashuha@gmail.com", "role"=> "affiliator", "created_at"=> "2026-04-21 08:19:08"]
        ];

        foreach ($userData as $u) {
            User::create([
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'role' => $u['role'],
                'password' => Hash::make('password'),
                'created_at' => $u['created_at'],
                'updated_at' => $u['created_at']
            ]);
            
            if ($u['role'] === 'affiliator') {
                \App\Models\AffiliatorProfile::create([
                    'user_id' => $u['id'],
                    'status' => 'approved',
                    'referral_code' => 'WI516',
                    'nik' => '3303116010070001',
                    'no_hp' => '085645121183',
                    'alamat' => 'CILONGOK,CIPETE',
                    'bank_name' => 'BCA',
                    'bank_account_number' => '12345678',
                    'bank_account_name' => $u['name'],
                    'commission_rate' => 10
                ]);
            }
        }

        // 4. Units Data
        $unitsData = [
            ["id"=> 5, "category_id"=> 1, "seri"=> "iPhone XR", "imei"=> "-", "memori"=> "64 GB", "warna"=> "WHITE", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 3000.00, "harga_per_hari"=> 70000.00, "is_active"=> true],
            ["id"=> 6, "category_id"=> 1, "seri"=> "iPhone 11", "imei"=> "29", "memori"=> "64 GB", "warna"=> "WHITE", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 3400.00, "harga_per_hari"=> 80000.00, "is_active"=> true],
            ["id"=> 7, "category_id"=> 1, "seri"=> "iPhone 11 Pro", "imei"=> "837", "memori"=> "64 GB", "warna"=> "BLACK", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 3700.00, "harga_per_hari"=> 88000.00, "is_active"=> true],
            ["id"=> 8, "category_id"=> 1, "seri"=> "iPhone 12", "imei"=> "938", "memori"=> "64 GB", "warna"=> "WHITE", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 3750.00, "harga_per_hari"=> 90000.00, "is_active"=> true],
            ["id"=> 9, "category_id"=> 1, "seri"=> "iPhone 13", "imei"=> "98", "memori"=> "128 GB", "warna"=> "PINK", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 5300.00, "harga_per_hari"=> 125000.00, "is_active"=> true],
            ["id"=> 10, "category_id"=> 1, "seri"=> "iPhone 14", "imei"=> "93", "memori"=> "128 GB", "warna"=> "PURPLE", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 5900.00, "harga_per_hari"=> 140000.00, "is_active"=> true],
            ["id"=> 11, "category_id"=> 1, "seri"=> "iPhone 15", "imei"=> "09", "memori"=> "128 GB", "warna"=> "PINK", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 8000.00, "harga_per_hari"=> 190000.00, "is_active"=> true],
            ["id"=> 13, "category_id"=> 1, "seri"=> "iPhone XR", "imei"=> "1231", "memori"=> "64 GB", "warna"=> "White", "kondisi"=> "MULUS", "specs"=> [], "harga_per_jam"=> 3000.00, "harga_per_hari"=> 70000.00, "is_active"=> true]
        ];

        foreach ($unitsData as $ud) {
            Unit::create($ud);
        }

        // 5. Pricing Rules
        $pricingData = [
            ["id"=> 1, "nama_promo"=> "APRIL HEMAT", "tipe"=> "hari_gratis", "value"=> 1.00, "syarat_minimal_durasi"=> 24, "syarat_tipe_durasi"=> "jam", "is_active"=> true, "start_date"=> "2026-04-01", "end_date"=> "2026-04-30"],
            ["id"=> 2, "nama_promo"=> "1 MINGGU DISKON 15%", "tipe"=> "diskon_persen", "value"=> 15.00, "syarat_minimal_durasi"=> 7, "syarat_tipe_durasi"=> "hari", "is_active"=> true, "start_date"=> "2026-04-01", "end_date"=> "2026-05-31"],
            ["id"=> 4, "nama_promo"=> "DISKON 5%", "kode_promo"=> "DISKON2026", "is_hidden"=> true, "tipe"=> "diskon_persen", "value"=> 5.00, "syarat_minimal_durasi"=> 12, "syarat_tipe_durasi"=> "jam", "is_active"=> true]
        ];

        foreach ($pricingData as $pd) {
            PricingRule::create($pd);
        }

        // 6. Rentals Data (ALL 13 Transactions)
        $rentalsData = [
            ["id"=> 84, "unit_id"=> 7, "nik"=> "3308201812960004", "nama"=> "THOBA MUSTOFA", "alamat"=> "PASIR MUNCANG", "no_wa"=> "087837032444", "waktu_mulai"=> "2026-04-15 07:00:00", "waktu_selesai"=> "2026-04-17 07:00:00", "subtotal_harga"=> 95000.00, "potongan_diskon"=> 15000.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 483, "grand_total"=> 80483.00, "status"=> "completed", "metode_pembayaran"=> "qris"],
            ["id"=> 87, "unit_id"=> 7, "nik"=> "3308201812960004", "nama"=> "THOBA MUSTOFA", "alamat"=> "GANG 3 RT 03 PASIRMUNCANG", "no_wa"=> "087837032444", "waktu_mulai"=> "2026-04-17 06:15:00", "waktu_selesai"=> "2026-04-20 06:15:00", "subtotal_harga"=> 176000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 368, "grand_total"=> 176368.00, "status"=> "completed", "metode_pembayaran"=> "qris"],
            ["id"=> 90, "unit_id"=> 8, "nik"=> "3302146511080004", "nama"=> "NOVANZA CALISTA PUTRI ", "alamat"=> "PURWOKERTO SELATAN", "no_wa"=> "088983032475", "waktu_mulai"=> "2026-04-18 09:00:00", "waktu_selesai"=> "2026-04-20 09:00:00", "subtotal_harga"=> 90000.00, "potongan_diskon"=> 10000.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 387, "grand_total"=> 80387.00, "status"=> "completed", "metode_pembayaran"=> "qris"],
            ["id"=> 98, "unit_id"=> 6, "nik"=> "3302174903080001", "nama"=> "WILDA NASHUHA DWI MULYANTI", "alamat"=> "CILONGOKCIPETE", "no_wa"=> "081945951871", "waktu_mulai"=> "2026-05-01 07:30:00", "waktu_selesai"=> "2026-05-03 07:30:00", "subtotal_harga"=> 80000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 862, "grand_total"=> 80862.00, "status"=> "pending", "metode_pembayaran"=> "qris"],
            ["id"=> 99, "unit_id"=> 5, "nik"=> "3302146511080004", "nama"=> "NOVANZA CALISTA PUTRI", "alamat"=> "PURWOKERTO SELATAN", "no_wa"=> "088983032475", "waktu_mulai"=> "2026-04-27 07:00:00", "waktu_selesai"=> "2026-04-30 07:00:00", "subtotal_harga"=> 140000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 819, "grand_total"=> 140819.00, "status"=> "cancelled", "metode_pembayaran"=> "qris"],
            ["id"=> 100, "unit_id"=> 7, "nik"=> "3302176803080006", "nama"=> " LISAMARLIANA", "alamat"=> "CILONGOKBATUANTEN", "no_wa"=> "6288983660814", "waktu_mulai"=> "2026-05-01 07:30:00", "waktu_selesai"=> "2026-05-03 07:30:00", "subtotal_harga"=> 88000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 503, "grand_total"=> 88503.00, "status"=> "pending", "metode_pembayaran"=> "qris"],
            ["id"=> 114, "unit_id"=> 7, "nik"=> "3308201812960004", "nama"=> "THOBA MUSTOFA", "alamat"=> "GANG 3 RT 03 PASIRMUNCANG", "no_wa"=> "087837032444", "waktu_mulai"=> "2026-04-20 04:03:00", "waktu_selesai"=> "2026-04-22 04:03:00", "subtotal_harga"=> 88000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 362, "grand_total"=> 88362.00, "status"=> "paid", "metode_pembayaran"=> "qris", "booking_code" => "RFTHVUSXRKL5"],
            ["id"=> 116, "unit_id"=> 13, "nik"=> "3302184112090001", "nama"=> "CALLYSTA DZIHNII DEVISTA ", "alamat"=> "TAMANSARI, KARANGLEWAS...", "no_wa"=> "088983626900", "waktu_mulai"=> "2026-04-27 07:45:00", "waktu_selesai"=> "2026-04-29T07:45:00", "subtotal_harga"=> 140000.00, "potongan_diskon"=> 0.00, "kode_unik_pembayaran"=> 104, "grand_total"=> 140104.00, "status"=> "pending", "metode_pembayaran"=> "qris", "booking_code" => "1DXEEWAK0RT8"],
            ["id"=> 118, "unit_id"=> 7, "nik"=> "3302146511080004", "nama"=> "NOVANZA CALISTA PUTRI", "alamat"=> "PURWOKERTO SELATAN", "no_wa"=> "088983032475", "waktu_mulai"=> "2026-04-27T08:50:00", "waktu_selesai"=> "2026-04-30T08:50:00", "subtotal_harga"=> 176000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 158, "grand_total"=> 176158.00, "status"=> "pending", "metode_pembayaran"=> "qris", "booking_code" => "TD99HMMCLUQN"],
            ["id"=> 122, "unit_id"=> 6, "nik"=> "3303116010070001", "nama"=> "SITI TANZILURROHMAH ", "alamat"=> "CILONGOK,CIPETE", "no_wa"=> "085645121183", "waktu_mulai"=> "2026-04-30T07:00:00", "waktu_selesai"=> "2026-05-02T07:00:00", "subtotal_harga"=> 80000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 411, "grand_total"=> 80411.00, "status"=> "pending", "metode_pembayaran"=> "qris", "affiliator_id" => 6, "affiliate_code" => "WI516", "booking_code" => "KEULR7RSTNLJ"],
            ["id"=> 131, "unit_id"=> 5, "nik"=> "3302200405040004", "nama"=> "ANDERA SINGGIH PRATAMA DEWI PUSPITA SARI", "alamat"=> "LEDUG", "no_wa"=> "0881082411878", "waktu_mulai"=> "2026-04-21T19:50:00", "waktu_selesai"=> "2026-04-23T19:51:00", "subtotal_harga"=> 70000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 611, "grand_total"=> 70611.00, "status"=> "cancelled", "metode_pembayaran"=> "qris", "booking_code" => "RX727WH2K238"],
            ["id"=> 132, "unit_id"=> 5, "nik"=> "3302200405040004", "nama"=> "ANDERA SINGGIH PRATAMA DEWI PUSPITA SARI", "alamat"=> "LEDUG", "no_wa"=> "0881082411878", "waktu_mulai"=> "2026-04-21T20:21:00", "waktu_selesai"=> "2026-04-23T20:21:00", "subtotal_harga"=> 70000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 775, "grand_total"=> 70775.00, "status"=> "completed", "metode_pembayaran"=> "qris", "booking_code" => "QITVKMFRNUEA"],
            ["id"=> 133, "unit_id"=> 5, "nik"=> "3302116303090003", "nama"=> "RITA SELVIANI", "alamat"=> "PASINGGANGAN RT 2/7 BANYUMAS", "no_wa"=> "085642405674", "waktu_mulai"=> "2026-04-27T08:00:00", "waktu_selesai"=> "2026-04-30T08:01:00", "subtotal_harga"=> 140000.00, "potongan_diskon"=> 0.00, "applied_promo_name"=> "APRIL HEMAT", "hari_bonus"=> 1, "kode_unik_pembayaran"=> 160, "grand_total"=> 140160.00, "status"=> "pending", "metode_pembayaran"=> "qris", "booking_code" => "VRGGOHLFPZKW"]
        ];

        foreach ($rentalsData as $rd) {
            $unitId = $rd['unit_id'];
            unset($rd['unit_id']); // Remove unit_id from rental model if it's not a direct field
            
            $rental = Rental::create($rd);
            
            // Seed rental_items relationship
            DB::table('rental_items')->insert([
                'rental_id' => $rental->id,
                'unit_id' => $unitId,
                'price_snapshot' => $rental->subtotal_harga, // Corrected column name
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. Settings Data
        $settingsData = [
            ["key"=> "qris", "value"=> "qris_1776795721.jpeg"],
            ["key"=> "hero", "value"=> "hero_1776258696.jpg"],
            ["key"=> "home_title", "value"=> "Sewa Mudah, Cepat, dan Aman"],
            ["key"=> "home_description", "value"=> "Pilih sesuai kebutuhan Anda. Bebas atur jadwal sewa, harga bersahabat, tanpa syarat ribet!"],
            ["key"=> "late_tolerance_minutes", "value"=> "60"],
            ["key"=> "admin_wa", "value"=> "62881082411878"],
            ["key"=> "admin_address", "value"=> "Jl. BP Pereng, Pereng, Sokanegara, Kec. Purwokerto Tim., Kabupaten Banyumas, Jawa Tengah 53115"],
            ["key"=> "payment_methods", "value"=> "{\"qris\":true,\"cash\":true,\"transfer\":false}"]
        ];

        foreach ($settingsData as $sd) {
            Setting::updateOrCreate(['key' => $sd['key']], ['value' => $sd['value']]);
        }

        Schema::enableForeignKeyConstraints();
        
        if (config('database.default') === 'pgsql') {
            DB::select("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");
            DB::select("SELECT setval('units_id_seq', (SELECT MAX(id) FROM units))");
            DB::select("SELECT setval('rentals_id_seq', (SELECT MAX(id) FROM rentals))");
        }
    }
}
