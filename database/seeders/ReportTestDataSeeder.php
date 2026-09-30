<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Rental;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data uji khusus jalur "grup report" (asisten internal).
 *
 * TestDataSeeder yang lama hanya mengisi user, unit, dan log staf — tidak ada
 * satu pun transaksi. Padahal semua bagian data laporan dibangun dari tabel
 * rental: jadwal hari ini, pengembalian, keterlambatan, denda, riwayat. Tanpa
 * seeder ini jalur tak-batas (batas karakter 0) tidak bisa diuji apa adanya,
 * karena setiap bagian akan kosong dan tesnya lolos secara palsu.
 *
 * Isinya sengaja mencakup semua status supaya tiap blok data punya isi nyata:
 * penyewa telat, yang masih jalan, yang sudah selesai, yang kena denda, dan yang
 * belum bayar.
 */
class ReportTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $category = Category::first() ?? Category::create(['name' => 'iPhone', 'slug' => 'iphone']);

        // 3 unit: 1 lagi disewa (bikin terlambat), 1 lagi disewa normal, 1 ready.
        $units = [];
        foreach ([
            ['seri' => 'iPhone 13 Pro', 'warna' => 'Graphite', 'harga' => 150000],
            ['seri' => 'iPhone 12', 'warna' => 'Silver', 'harga' => 120000],
            ['seri' => 'iPhone 15', 'warna' => 'Blue', 'harga' => 180000],
        ] as $i => $spec) {
            $units[$i] = Unit::create([
                'category_id' => $category->id,
                'seri' => $spec['seri'],
                'imei' => 'REPORT-TEST-IMEI-'.($i + 1),
                'memori' => '128GB',
                'warna' => $spec['warna'],
                'kondisi' => 'Mulus',
                'is_active' => true,
                'harga_per_jam' => 15000,
                'harga_per_hari' => $spec['harga'],
            ]);
        }

        // Satu unit non-aktif: harus muncul sebagai catatan, bukan bikin query gagal.
        Unit::create([
            'category_id' => $category->id,
            'seri' => 'iPhone 11 Rusak',
            'imei' => 'REPORT-TEST-IMEI-4',
            'memori' => '64GB',
            'warna' => 'Hitam',
            'kondisi' => 'Layar pecah',
            'is_active' => false,
            'harga_per_jam' => 0,
            'harga_per_hari' => 0,
        ]);

        $rows = [
            // Telat: sudah lewat jadwal 90 menit, masih status renting.
            [
                'unit' => 0, 'nama' => 'Rina Wijaya', 'no_wa' => '081200000001',
                'mulai' => $now->copy()->subHours(6), 'selesai' => $now->copy()->subMinutes(90),
                'status' => 'renting', 'total' => 150000,
            ],
            // Masih jalan, belum selesai.
            [
                'unit' => 1, 'nama' => 'Bagus Prasetyo', 'no_wa' => '081200000002',
                'mulai' => $now->copy()->subHours(2), 'selesai' => $now->copy()->addHours(4),
                'status' => 'renting', 'total' => 120000,
            ],
            // Ambil hari ini, belum bayar.
            [
                'unit' => 2, 'nama' => 'Siti Aminah', 'no_wa' => '081200000003',
                'mulai' => $now->copy()->addHours(2), 'selesai' => $now->copy()->addHours(8),
                'status' => 'pending', 'total' => 180000,
            ],
            // Sudah selesai 10 hari lalu, kena denda + kerusakan.
            [
                'unit' => 1, 'nama' => 'Dimas Nugroho', 'no_wa' => '081200000004',
                'mulai' => $now->copy()->subDays(10)->setTime(9, 0),
                'selesai' => $now->copy()->subDays(10)->setTime(17, 0),
                'status' => 'completed', 'total' => 120000, 'denda' => 25000,
                'denda_kerusakan' => 50000, 'catatan_kerusakan' => 'Kaca kamera retak',
                'handed_over' => $now->copy()->subDays(10)->setTime(17, 45),
            ],
            // Selesai bulan lalu: harus muncul di riwayat 30 hari terakhir.
            [
                'unit' => 0, 'nama' => 'Rina Wijaya', 'no_wa' => '081200000001',
                'mulai' => $now->copy()->subDays(25)->setTime(10, 0),
                'selesai' => $now->copy()->subDays(25)->setTime(14, 0),
                'status' => 'completed', 'total' => 150000,
                'handed_over' => $now->copy()->subDays(25)->setTime(14, 30),
            ],
            // Dibayar bulan ini, tapi sewa-nya mulai bulan lalu. Baris inilah yang
            // membuat omset "versi dashboard web" (paid_at) beda dari omset
            // "tanggal mulai sewa" — tanpa itu, selisihnya di web tidak punya
            // penjelasan nyata dan AI mengarang alasannya.
            [
                'unit' => 0, 'nama' => 'Lestari Ningrum', 'no_wa' => '081200000006',
                'mulai' => $now->copy()->subDays(31)->setTime(9, 0),
                'selesai' => $now->copy()->subDays(29)->setTime(17, 0),
                'status' => 'completed', 'total' => 100000,
                'handed_over' => $now->copy()->subDays(29)->setTime(17, 30),
                'paid_at' => $now->copy()->subDays(26)->setTime(20, 0),
            ],
        ];

        foreach ($rows as $spec) {
            $rental = Rental::create([
                'unit_id' => $units[$spec['unit']]->id,
                'nama' => $spec['nama'],
                'alamat' => 'Purwokerto',
                'no_wa' => $spec['no_wa'],
                'waktu_mulai' => $spec['mulai'],
                'waktu_selesai' => $spec['selesai'],
                'subtotal_harga' => $spec['total'],
                'grand_total' => $spec['total'],
                'status' => $spec['status'],
                'denda' => $spec['denda'] ?? 0,
                'denda_kerusakan' => $spec['denda_kerusakan'] ?? 0,
                'catatan_kerusakan' => $spec['catatan_kerusakan'] ?? null,
                'handed_over_at' => $spec['handed_over'] ?? null,
                'completed_at' => $spec['status'] === 'completed' ? $spec['handed_over'] ?? null : null,
                // Mayoritas dibayar saat mulai sewa; sisanya punya tanggal bayar sendiri.
                'paid_at' => $spec['paid_at'] ?? $spec['mulai'],
            ]);

            DB::table('rental_items')->insert([
                'rental_id' => $rental->id,
                'unit_id' => $units[$spec['unit']]->id,
                'price_snapshot' => $spec['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Data uji grup report siap: 4 unit (1 non-aktif), 6 transaksi.');
    }
}
