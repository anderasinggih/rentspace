<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi internal tim (grup WhatsApp) untuk aktivitas booking.
 *
 * SENGAJA tanpa AI: pesan disusun dari template statis supaya formatnya
 * konsisten, cepat, dan tidak pernah gagal hanya karena model sedang kena
 * rate limit. Chat customer tetap lewat GeminiAIService; yang di sini murni
 * pengingat operasional untuk karyawan.
 */
class NotifService
{
    /**
     * Label status + emoji untuk tiap transisi.
     *
     * Transisi yang tidak ada di sini sengaja diam: mis. `pending` hasil
     * reset pembayaran, itu hanya bising untuk karyawan.
     */
    private const STATUS_EVENTS = [
        'pending_confirmation' => [
            'emoji' => '🧾',
            'title' => 'BUKTI BAYAR MASUK',
            'note' => 'Segera cek & konfirmasi pembayaran customer.',
        ],
        'paid' => [
            'emoji' => '✅',
            'title' => 'PEMBAYARAN LUNAS',
            'note' => 'Unit siap divalidasi saat customer datang.',
        ],
        'renting' => [
            'emoji' => '📦',
            'title' => 'UNIT DISERAHTERIMAKAN',
            'note' => 'Pantau waktu pengembalian unit.',
        ],
        'completed' => [
            'emoji' => '🎉',
            'title' => 'SEWA SELESAI',
            'note' => 'Unit kembali ke stok. Jangan lupa cek kondisi unit.',
        ],
        'cancelled' => [
            'emoji' => '❌',
            'title' => 'PESANAN DIBATALKAN',
            'note' => 'Unit kembali tersedia untuk disewa.',
        ],
    ];

    /**
     * Pesanan baru masuk dari customer.
     */
    public static function bookingCreated(Rental $rental): bool
    {
        if (!static::enabled('notif_group_booking')) {
            return false;
        }

        $body = static::identityBlock($rental);
        $body .= static::scheduleBlock($rental);
        $body .= "💰 *Total*: " . static::rupiah($rental->grand_total ?: $rental->subtotal_harga) . "\n";
        $body .= "⏳ *Status*: Menunggu Pembayaran";

        return static::send("🆕 *PESANAN BARU* — Rent Space Purwokerto\n" . static::rule() . $body, $rental, 'booking_created');
    }

    /**
     * Status pesanan berubah (validasi bayar, serah terima, selesai, batal).
     *
     * Sengaja dipanggil dari observer Rental supaya tidak ada jalur update
     * status yang lolos tanpa memberi tahu tim.
     */
    public static function statusChanged(Rental $rental, string $from, string $to): bool
    {
        $event = self::STATUS_EVENTS[$to] ?? null;
        if ($event === null) {
            return false;
        }

        if (!static::enabled('notif_group_status')) {
            return false;
        }

        $body = static::identityBlock($rental);
        $body .= static::scheduleBlock($rental);
        $body .= "💰 *Total*: " . static::rupiah($rental->grand_total ?: $rental->subtotal_harga) . "\n";
        $body .= "🔁 *Status*: " . static::statusLabel($from) . " → *" . static::statusLabel($to) . "*\n";
        $body .= "👤 *Oleh*: " . static::actor() . "\n";
        $body .= "_" . $event['note'] . "_";

        return static::send(
            $event['emoji'] . ' *' . $event['title'] . "*\n" . static::rule() . $body,
            $rental,
            'status_' . $to
        );
    }

    /**
     * Pengingat H-1 unit akan diambil customer.
     */
    public static function reminderPickup(Rental $rental, int $minutes = 60): bool
    {
        if (!static::enabled('notif_group_reminder')) {
            return false;
        }

        $body = static::identityBlock($rental);
        $body .= "📦 *Unit*: " . static::unitNames($rental) . "\n";
        $body .= "🗓 *Jadwal Ambil*: " . static::waktu($rental->waktu_mulai) . "\n";
        $body .= "⏰ *Tersisa*: " . static::remainingLabel($rental->waktu_mulai) . "\n";
        $body .= "📍 *Lokasi*: " . (Setting::getVal('admin_address') ?: 'Rent Space Purwokerto');

        return static::send(
            '⏰ *PENGAMBILAN ' . static::jamLabel($minutes) . ' LAGI*\n' . static::rule() . $body,
            $rental,
            'reminder_pickup'
        );
    }

    /**
     * Pengingat H-1 unit harus dikembalikan.
     */
    public static function reminderReturn(Rental $rental, int $minutes = 60): bool
    {
        if (!static::enabled('notif_group_reminder')) {
            return false;
        }

        $denda = (int) ($rental->denda ?: 0) + (int) ($rental->denda_kerusakan ?: 0);

        $body = static::identityBlock($rental);
        $body .= "📦 *Unit*: " . static::unitNames($rental) . "\n";
        $body .= "🗓 *Jadwal Kembali*: " . static::waktu($rental->waktu_selesai) . "\n";
        $body .= "⏰ *Tersisa*: " . static::remainingLabel($rental->waktu_selesai) . "\n";
        $body .= "📝 *Catatan*: " . (($rental->catatan_kerusakan ?: '—') . "\n");
        $body .= "💵 *Denda berjalan*: " . static::rupiah($denda);

        return static::send(
            '⏰ *PENGEMBALIAN ' . static::jamLabel($minutes) . ' LAGI*\n' . static::rule() . $body,
            $rental,
            'reminder_return'
        );
    }

    /**
     * Customer sudah lewat jam ambil tapi unit belum masuk status renting.
     */
    public static function alertPickupLate(Rental $rental, int $minutesLate = 0): bool
    {
        if (!static::enabled('notif_group_reminder')) {
            return false;
        }

        $body = static::identityBlock($rental);
        $body .= "📦 *Unit*: " . static::unitNames($rental) . "\n";
        $body .= "🗓 *Seharusnya*: " . static::waktu($rental->waktu_mulai) . "\n";
        $body .= "⚠️ *Telat*: " . static::remainingLabel($rental->waktu_mulai) . "\n";
        $body .= "👉 _Cek customer sudah datang atau belum, lalu validasi pengambilan._";

        return static::send(
            '⏰ *CUSTOMER BELUM AMBIL UNIT*\n' . static::rule() . $body,
            $rental,
            'alert_pickup_late'
        );
    }

    /**
     * Unit sudah lewat jadwal kembali tapi belum selesai.
     */
    public static function alertReturnLate(Rental $rental): bool
    {
        if (!static::enabled('notif_group_reminder')) {
            return false;
        }

        $body = static::identityBlock($rental);
        $body .= "📦 *Unit*: " . static::unitNames($rental) . "\n";
        $body .= "🗓 *Seharusnya*: " . static::waktu($rental->waktu_selesai) . "\n";
        $body .= "⚠️ *Telat*: " . static::remainingLabel($rental->waktu_selesai) . "\n";
        $body .= "💵 *Denda berjalan*: " . static::rupiah((int) ($rental->denda ?: 0) + (int) ($rental->denda_kerusakan ?: 0)) . "\n";
        $body .= "👉 _Hubungi customer lewat WA, lalu proses pengembalian unit._";

        return static::send(
            '⏰ *UNIT BELUM DIKEMBALIKAN*\n' . static::rule() . $body,
            $rental,
            'alert_return_late'
        );
    }

    /**
     * Kirim ke grup notifikasi, atau ke nomor WA admin kalau grup belum diisi.
     */
    protected static function send(string $message, ?Rental $rental = null, string $context = 'notif'): bool
    {
        $targets = static::targets();
        if (empty($targets)) {
            Log::info('NotifService: dilewati, tidak ada target notifikasi tim.', [
                'context' => $context,
                'booking_code' => $rental?->booking_code,
            ]);
            return false;
        }

        $link = $rental ? "\n🔗 " . route('admin.transactions') : '';
        $wa = app(WhatsAppService::class);

        $ok = true;
        foreach ($targets as $target) {
            $ok = $wa->sendMessage($target, $message . $link) && $ok;
        }

        Log::info('NotifService: pesan dikirim ke ' . count($targets) . ' target.', [
            'context' => $context,
            'booking_code' => $rental?->booking_code,
            'success' => $ok,
        ]);

        return $ok;
    }

    /**
     * Tujuan notifikasi: grup notifikasi kalau ada, jika tidak nomor WA admin.
     */
    public static function targets(): array
    {
        $group = Setting::sanitizeJid(Setting::getVal('admin_notify_group_id', ''));
        if ($group !== '') {
            return [$group];
        }

        $adminWa = (string) Setting::getVal('admin_wa', '');
        return $adminWa !== '' ? [$adminWa] : [];
    }

    protected static function enabled(string $key): bool
    {
        return Setting::getVal($key, '1') === '1';
    }

    protected static function rule(): string
    {
        return "------------------------------\n";
    }

    protected static function identityBlock(Rental $rental): string
    {
        $wa = preg_replace('/[^0-9]/', '', (string) $rental->no_wa);
        $chatLink = $wa !== '' ? "\n💬 https://wa.me/" . (str_starts_with($wa, '0') ? '62' . substr($wa, 1) : $wa) : '';

        return "🧾 *Kode*: {$rental->booking_code}\n"
            . "👤 *Nama*: " . ($rental->nama ?: '-') . "\n"
            . "📱 *WA*: " . ($rental->no_wa ?: '-') . $chatLink . "\n";
    }

    protected static function scheduleBlock(Rental $rental): string
    {
        return "📦 *Unit*: " . static::unitNames($rental) . "\n"
            . "🗓 *Jadwal*: " . static::waktu($rental->waktu_mulai) . " s/d " . static::waktu($rental->waktu_selesai) . "\n";
    }

    protected static function unitNames(Rental $rental): string
    {
        $rental->loadMissing('units');
        $names = $rental->units
            ->map(fn ($u) => $u->nama_lengkap ?: $u->seri)
            ->filter()
            ->implode(', ');

        return $names !== '' ? $names : 'Unit Rental';
    }

    protected static function waktu($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->translatedFormat('d M Y H:i') : '-';
    }

    protected static function rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    protected static function statusLabel(string $status): string
    {
        return [
            'pending' => 'Menunggu Pembayaran',
            'pending_confirmation' => 'Menunggu Konfirmasi',
            'paid' => 'Lunas',
            'renting' => 'Disewa',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ][$status] ?? $status;
    }

    /**
     * "2 jam 15 menit lagi" / "45 menit lalu".
     */
    protected static function remainingLabel($value): string
    {
        if (!$value) {
            return '-';
        }

        $minutes = (int) round(\Carbon\Carbon::now()->diffInMinutes(\Carbon\Carbon::parse($value), false));
        $suffix = $minutes >= 0 ? 'lagi' : 'lalu';
        $minutes = abs($minutes);

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours > 0 && $rest > 0) {
            return "{$hours} jam {$rest} menit {$suffix}";
        }
        if ($hours > 0) {
            return "{$hours} jam {$suffix}";
        }

        return "{$rest} menit {$suffix}";
    }

    /**
     * "1 jam" untuk judul pesan, "45 menit" kalau bukan kelipatan jam.
     */
    protected static function jamLabel(int $minutes): string
    {
        if ($minutes % 60 === 0) {
            $hours = $minutes / 60;
            return $hours . ' JAM';
        }

        return $minutes . ' MENIT';
    }

    /**
     * Siapa yang mengubah status. Kalau tidak ada user (webhook/command),
     * berarti sistem.
     */
    protected static function actor(): string
    {
        $user = auth()->user();
        if ($user && !empty($user->name)) {
            $role = ucfirst((string) ($user->role ?? 'staff'));
            return "{$user->name} ({$role})";
        }

        return 'Sistem';
    }
}
