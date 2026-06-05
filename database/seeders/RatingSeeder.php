<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rental;
use Illuminate\Support\Str;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feedbacks = [
            ['name' => 'Ahmad Dani', 'rating' => 5, 'text' => 'Tempatnya bersih banget dan wangi! Nyaman buat nongkrong lama-lama.'],
            ['name' => 'Siska Amelia', 'rating' => 4, 'text' => 'Koneksi internet kencang, cocok buat WFC. Cuma parkiran agak sempit dikit.'],
            ['name' => 'Budi Santoso', 'rating' => 5, 'text' => 'Pelayanan ramah banget, AC-nya dingin, mantap pokoknya!'],
            ['name' => 'Dewi Lestari', 'rating' => 5, 'text' => 'Ribuan pengalaman manis bersama Rent Space memang bukan jargon doang. Recommended!'],
            ['name' => 'Rizky Fauzi', 'rating' => 3, 'text' => 'Lumayan sih, tapi sayang kemaren sempet mati lampu bentar.'],
            ['name' => 'Maya Putri', 'rating' => 5, 'text' => 'Sering sewa di sini buat meeting sama klien, selalu puas.'],
            ['name' => 'Anton Wijaya', 'rating' => 1, 'text' => 'Jelek banget! Kapok sewa di sini lagi ugh (Tes Hapus)'],
            ['name' => 'Lina Marlina', 'rating' => 2, 'text' => 'Adminnya slow response banget parah.'],
            ['name' => 'Doni Kusuma', 'rating' => 4, 'text' => 'Harga terjangkau, unit terawat. Oke punya.'],
            ['name' => 'Indah Permata', 'rating' => 5, 'text' => 'Proses booking gampang, sukses terus Rent Space!'],
        ];

        foreach ($feedbacks as $item) {
            $price = rand(50000, 200000);
            Rental::create([
                'booking_code' => strtoupper(Str::random(12)),
                'nama' => $item['name'],
                'nik' => rand(1111111111111111, 9999999999999999),
                'alamat' => 'Alamat Dummy ' . rand(1, 100),
                'no_wa' => '08' . rand(111111111, 999999999),
                'subtotal_harga' => $price,
                'grand_total' => $price + rand(100, 500),
                'kode_unik_pembayaran' => rand(100, 999),
                'status' => 'completed',
                'rating' => $item['rating'],
                'feedback' => $item['text'],
                'waktu_mulai' => now()->subDays(rand(1, 30)),
                'waktu_selesai' => now()->subDays(rand(1, 30))->addHours(2),
            ]);
        }
    }
}
