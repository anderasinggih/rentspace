<?php

namespace Tests\Feature;

use App\Models\Rental;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\NotifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notifikasi tim harus bekerja di semua jalur, bukan cuma di halaman admin.
 *
 * Status rental berubah dari banyak tempat: panel admin, webhook Midtrans, QR
 * scan, halaman bayar customer, dan command pembatalan otomatis. Kalau
 * notifikasi dipasang per halaman, satu jalur yang terlewat berarti tim tidak
 * tahu pesanan sudah dibayar atau dibatalkan. Karena itu perubahan status
 * dicek dari model observer.
 *
 * Semua pesan di sini template statis — kalau ada panggilan Gemini, tes
 * `test_pesan_tidak_memakai_ai` akan gagal.
 */
class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const GROUP_JID = '120363000000000000@g.us';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 29, 10, 0, 0));

        Setting::updateOrCreate(['key' => 'admin_notify_group_id'], ['value' => self::GROUP_JID]);
        Setting::updateOrCreate(['key' => 'admin_wa'], ['value' => '08123456789']);

        Http::fake([
            '*/send-message' => Http::response(['status' => true, 'message_id' => 'x'], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function unit(string $seri = 'iPhone 13'): Unit
    {
        static $seq = 0;
        $seq++;

        return Unit::create([
            'seri' => $seri,
            'imei' => 'imei-' . $seq,
            'memori' => '256GB',
            'warna' => 'Biru',
            'harga_per_jam' => 12500,
            'harga_per_hari' => 250000,
            'is_active' => true,
        ]);
    }

    private function rental(array $overrides = []): Rental
    {
        $rental = Rental::create(array_merge([
            'nama' => 'Rina',
            'alamat' => 'Purwokerto',
            'no_wa' => '081298765432',
            'waktu_mulai' => now()->addHours(3),
            'waktu_selesai' => now()->addHours(6),
            'subtotal_harga' => 250000,
            'grand_total' => 250000,
            'status' => 'pending',
        ], $overrides));

        $rental->units()->attach($this->unit(), ['price_snapshot' => 250000]);

        return $rental->fresh();
    }

    private function onlyStatusChanges(): void
    {
        // Fokus tes ini ke perubahan status; notifikasi pesanan baru diuji
        // terpisah supaya penghitung pesan tidak saling mengganggu.
        Setting::updateOrCreate(['key' => 'notif_group_booking'], ['value' => '0']);
    }

    /** @return array<int, array{phone: string, message: string}> */
    private function sentMessages(): array
    {
        $messages = [];
        Http::recorded(function ($request) use (&$messages) {
            $messages[] = [
                'phone' => (string) ($request['phone'] ?? ''),
                'message' => (string) ($request['message'] ?? ''),
            ];
            return true;
        });

        return $messages;
    }

    private function lastMessage(): string
    {
        $messages = $this->sentMessages();

        return (string) end($messages)['message'];
    }

    private function runReminderCommand(): void
    {
        $this->artisan('app:remind-staff')->assertSuccessful();
    }

    public function test_pesanan_baru_dikirim_ke_grup_notifikasi(): void
    {
        $rental = $this->rental();

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertSame(self::GROUP_JID, $messages[0]['phone'], 'tujuan harus grup notifikasi, bukan nomor customer');
        $this->assertStringContainsString('PESANAN BARU', $messages[0]['message']);
        $this->assertStringContainsString($rental->booking_code, $messages[0]['message']);
        $this->assertStringContainsString('Rina', $messages[0]['message']);
        $this->assertStringContainsString('Rp 250.000', $messages[0]['message']);
    }

    public function test_status_lunas_dan_batal_punya_pesan_tersendiri(): void
    {
        $this->onlyStatusChanges();
        $rental = $this->rental();

        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        $this->assertStringContainsString('PEMBAYARAN LUNAS', $this->lastMessage());
        $this->assertStringContainsString('Menunggu Pembayaran → *Lunas*', $this->lastMessage());

        $rental->update(['status' => 'cancelled']);
        $this->assertStringContainsString('PESANAN DIBATALKAN', $this->lastMessage());

        $this->assertCount(2, $this->sentMessages());
    }

    public function test_perubahan_status_lain_tidak_mengirim_pesan(): void
    {
        $this->onlyStatusChanges();
        $rental = $this->rental();

        $rental->update(['catatan_kerusakan' => 'Baret penyok']);
        $rental->update(['denda' => 25000]);

        $this->assertCount(0, $this->sentMessages(), 'hanya perubahan status yang perlu dilaporkan');
    }

    public function test_status_yang_diabaikan_tidak_mengirim_pesan(): void
    {
        $this->onlyStatusChanges();
        $rental = $this->rental(['status' => 'pending_confirmation']);

        $rental->update(['status' => 'pending']);

        $this->assertCount(0, $this->sentMessages(), 'reset pembayaran hanya ruido untuk karyawan');
    }

    public function test_penandaan_pengingat_tidak_memicu_notifikasi_lagi(): void
    {
        $this->onlyStatusChanges();
        $rental = $this->rental();

        $rental->update(['status' => 'paid']);
        $this->assertCount(1, $this->sentMessages());

        // Command pengingat menandai kolom penanda. Kalau penandaan itu memicu
        // observer, tiap 5 menit grup akan dibanjiri ulang pesan yang sama.
        $rental->forceFill(['reminder_pickup_sent_at' => now()])->saveQuietly();
        $rental->update(['reminder_return_sent_at' => now()]);

        $this->assertCount(1, $this->sentMessages());
    }

    public function test_toggle_mematikan_tidak_mengirim_pesan(): void
    {
        $this->onlyStatusChanges();
        Setting::updateOrCreate(['key' => 'notif_group_status'], ['value' => '0']);

        $rental = $this->rental();
        $rental->update(['status' => 'paid']);

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_tanpa_grup_notifikasi_dikirim_ke_nomor_admin(): void
    {
        $this->onlyStatusChanges();
        Setting::updateOrCreate(['key' => 'admin_notify_group_id'], ['value' => '']);

        $rental = $this->rental();
        $rental->update(['status' => 'paid']);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('08123456789', $messages[0]['phone']);
    }

    public function test_tujuan_kosong_tidak_melempar_error(): void
    {
        $this->onlyStatusChanges();
        Setting::updateOrCreate(['key' => 'admin_notify_group_id'], ['value' => '']);
        Setting::updateOrCreate(['key' => 'admin_wa'], ['value' => '']);

        $rental = $this->rental();
        $rental->update(['status' => 'paid']);

        $this->assertSame([], NotifService::targets());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_pengingat_ambil_dikirim_sekali_saja(): void
    {
        $this->onlyStatusChanges();
        $this->rental(['status' => 'paid', 'waktu_mulai' => now()->addMinutes(45)]);

        $this->runReminderCommand();
        $this->assertStringContainsString('PENGAMBILAN 1 JAM LAGI', $this->lastMessage());
        $this->assertStringContainsString('45 menit lagi', $this->lastMessage());

        $this->runReminderCommand();
        $this->assertCount(1, $this->sentMessages(), 'pengingat hanya boleh sekali per pesanan');
    }

    public function test_pengingat_kembali_hanya_untuk_unit_yang_disewa(): void
    {
        $this->onlyStatusChanges();

        // Sudah lunas tapi belum diambil: belum adarois soal pengembalian.
        $this->rental(['status' => 'paid', 'waktu_selesai' => now()->addMinutes(45)]);
        $this->runReminderCommand();
        $this->assertCount(0, $this->sentMessages());

        $this->rental([
            'status' => 'renting',
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->addMinutes(45),
        ]);
        $this->runReminderCommand();
        $this->assertStringContainsString('PENGEMBALIAN 1 JAM LAGI', $this->lastMessage());
    }

    public function test_jadwal_yang_sudah_lewat_tidak_dikirim_pengingat(): void
    {
        $this->onlyStatusChanges();

        // Mulai 10 menit lalu, toleransi telat 30 menit: belum perlu alert.
        $this->rental(['status' => 'paid', 'waktu_mulai' => now()->subMinutes(10)]);

        $this->runReminderCommand();

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_alert_unit_belum_diambil_dan_belum_dikembalikan(): void
    {
        $this->onlyStatusChanges();

        $this->rental(['status' => 'paid', 'waktu_mulai' => now()->subHour()]);
        $this->runReminderCommand();
        $this->assertStringContainsString('CUSTOMER BELUM AMBIL UNIT', $this->lastMessage());

        $this->rental([
            'status' => 'renting',
            'waktu_mulai' => now()->subHours(4),
            'waktu_selesai' => now()->subHour(),
            'denda' => 25000,
        ]);
        $this->runReminderCommand();
        $this->assertStringContainsString('UNIT BELUM DIKEMBALIKAN', $this->lastMessage());
        $this->assertStringContainsString('Rp 25.000', $this->lastMessage());
    }

    public function test_menit_pengingat_bisa_diatur_dari_pengaturan(): void
    {
        $this->onlyStatusChanges();
        Setting::updateOrCreate(['key' => 'notif_staff_pickup_minutes'], ['value' => '120']);

        $this->rental(['status' => 'paid', 'waktu_mulai' => now()->addMinutes(90)]);

        $this->runReminderCommand();
        $this->assertStringContainsString('PENGAMBILAN 2 JAM LAGI', $this->lastMessage());
    }

    public function test_pesan_tidak_memakai_ai(): void
    {
        $this->onlyStatusChanges();
        $rental = $this->rental();
        $rental->update(['status' => 'renting']);

        $this->assertCount(1, $this->sentMessages());
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com');
        });
    }
}
