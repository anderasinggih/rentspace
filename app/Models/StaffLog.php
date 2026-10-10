<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Database\Eloquent\Prunable;

class StaffLog extends Model
{
    use Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'data_before',
        'data_after',
        'ip_address'
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function target()
    {
        return $this->morphTo();
    }

    /**
     * Nama pelaku aksi yang manusiawi
     */
    public function getFormattedActorAttribute(): string
    {
        return $this->user ? $this->user->name : 'Sistem Otomatis';
    }

    /**
     * Nama peran pengguna dalam Bahasa Indonesia
     */
    public function getFormattedRoleAttribute(): string
    {
        return match ($this->user?->role) {
            'admin' => 'Administrator',
            'staff' => 'Staf Kasir',
            default => 'Sistem'
        };
    }

    /**
     * Badge visual dan label kegiatan yang manusiawi
     */
    public function getActionBadgeAttribute(): array
    {
        $action = $this->action;

        if (str_contains($action, 'paid') || str_contains($action, 'lunas')) {
            return [
                'label' => 'Pembayaran Lunas',
                'class' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
            ];
        }
        if (str_contains($action, 'handover')) {
            return [
                'label' => 'Penyerahan Unit',
                'class' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
            ];
        }
        if (str_contains($action, 'complete')) {
            return [
                'label' => 'Pengembalian Selesai',
                'class' => 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20'
            ];
        }
        if (str_contains($action, 'cancel')) {
            return [
                'label' => 'Transaksi Dibatalkan',
                'class' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
            ];
        }
        if (str_contains($action, 'extend')) {
            return [
                'label' => 'Perpanjangan Sewa',
                'class' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'
            ];
        }
        if (str_contains($action, 'edit_transaction') || str_contains($action, 'update_transaction')) {
            return [
                'label' => 'Ubah Data Sewa',
                'class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
            ];
        }
        if (str_contains($action, 'unit')) {
            return [
                'label' => 'Manajemen Unit',
                'class' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
            ];
        }
        if (str_contains($action, 'promo') || str_contains($action, 'rule')) {
            return [
                'label' => 'Promo & Harga',
                'class' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
            ];
        }

        return [
            'label' => 'Aktivitas Sistem',
            'class' => 'bg-muted text-muted-foreground border-border/80'
        ];
    }

    /**
     * Kalimat deskripsi yang manusiawi dan ramah untuk kasir/admin
     */
    public function getHumanDescriptionAttribute(): string
    {
        $desc = $this->description ?: 'Melakukan pembaruan pada data sistem.';

        $desc = str_ireplace('via Monitoring', 'lewat Menu Monitoring', $desc);
        $desc = str_ireplace('via QuickScan', 'lewat Scan Cepat Barcode', $desc);
        $desc = str_ireplace('via Transaksi', 'lewat Halaman Transaksi', $desc);

        if ($this->target && ($this->target_type === 'App\Models\Rental' || $this->target_type === 'Rental' || str_contains($this->action, 'transaction') || str_contains($this->action, 'rental'))) {
            $rental = $this->target;
            if ($rental && isset($rental->nama, $rental->booking_code)) {
                $rentalInfo = $rental->nama . ' (Kode: ' . $rental->booking_code . ')';
                $desc = preg_replace('/#\d+/', $rentalInfo, $desc);
            }
        }

        return $desc;
    }

    /**
     * Kamus label field database ke istilah kasir
     */
    public static function fieldLabels(): array
    {
        return [
            'nama' => 'Nama Penyewa',
            'subtotal' => 'Harga Sewa Dasar',
            'potongan_diskon' => 'Potongan Diskon',
            'diskon' => 'Potongan Diskon',
            'denda' => 'Denda Keterlambatan',
            'denda_kerusakan' => 'Denda Kerusakan Unit',
            'catatan_kerusakan' => 'Keterangan Kerusakan',
            'grand_total' => 'Total Akhir Pembayaran',
            'status' => 'Status Sewa',
            'metode_pembayaran' => 'Metode Pembayaran',
            'waktu_mulai' => 'Waktu Mulai Sewa',
            'waktu_selesai' => 'Waktu Berakhir Sewa',
            'no_wa' => 'Nomor WhatsApp',
            'alamat' => 'Alamat Penyewa',
            'unit_id' => 'ID Unit',
            'seri' => 'Seri / Model iPhone',
            'warna' => 'Warna Unit',
            'kondisi' => 'Kondisi Unit',
            'harga_per_hari' => 'Tarif Sewa Harian',
            'harga_per_jam' => 'Tarif Sewa Per Jam',
            'is_active' => 'Status Ketersediaan'
        ];
    }

    /**
     * Format nilai data agar tidak mentah angka polos
     */
    public static function formatFieldValue(string $key, mixed $val): string
    {
        if ($val === null || $val === '') {
            if (in_array($key, ['subtotal', 'diskon', 'potongan_diskon', 'denda', 'denda_kerusakan', 'grand_total', 'harga_per_hari', 'harga_per_jam'])) {
                return 'Rp0';
            }
            return '-';
        }

        if (in_array($key, ['subtotal', 'diskon', 'potongan_diskon', 'denda', 'denda_kerusakan', 'grand_total', 'harga_per_hari', 'harga_per_jam'])) {
            return 'Rp' . number_format((float)$val, 0, ',', '.');
        }

        if ($key === 'status') {
            return match ($val) {
                'pending' => 'Menunggu Pembayaran',
                'paid' => 'Sudah Dibayar (Lunas)',
                'renting' => 'Sedang Disewa (Unit di Pelanggan)',
                'completed' => 'Selesai (Unit Sudah Kembali)',
                'cancelled' => 'Dibatalkan',
                default => ucfirst((string)$val)
            };
        }

        if ($key === 'is_active') {
            return $val ? 'Aktif (Bisa Disewa)' : 'Nonaktif';
        }

        if (is_array($val)) {
            return json_encode($val);
        }

        return (string)$val;
    }

    /**
     * Rincian field yang benar-benar mengalami perubahan nilai
     */
    public function getChangedFieldsAttribute(): array
    {
        $before = $this->data_before ?? [];
        $after = $this->data_after ?? [];

        if (empty($before) && empty($after)) {
            return [];
        }

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $labels = static::fieldLabels();
        $changes = [];

        foreach ($allKeys as $key) {
            $bVal = $before[$key] ?? null;
            $aVal = $after[$key] ?? null;

            $formattedBefore = static::formatFieldValue($key, $bVal);
            $formattedAfter = static::formatFieldValue($key, $aVal);

            if ($formattedBefore !== $formattedAfter) {
                $changes[] = [
                    'key' => $key,
                    'label' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                    'before' => $formattedBefore,
                    'after' => $formattedAfter,
                ];
            }
        }

        return $changes;
    }

    /**
     * Apakah log ini memiliki rincian data yang berubah
     */
    public function getHasChangesAttribute(): bool
    {
        return count($this->changed_fields) > 0;
    }
}
