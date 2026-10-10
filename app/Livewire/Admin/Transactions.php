<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Mail\PaymentConfirmedNotification;
use App\Mail\OrderCancelledNotification;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination, \App\Traits\LogsStaffActivity;

    public $search = '';
    public $filterStatus = 'all';

    public function getPaymentMethodsProperty()
    {
        $allMethods = [
            'cash' => 'CASH / TUNAI',
            'qris' => 'QRIS',
            'bca' => 'BANK BCA',
            'mandiri' => 'BANK MANDIRI',
            'bni' => 'BANK BNI',
            'bri' => 'BANK BRI',
            'permata' => 'BANK PERMATA',
            'bsi' => 'BANK BSI',
            'cimb' => 'BANK CIMB',
        ];

        // Ambil pengaturan metode yang aktif
        $savedPayment = \App\Models\Setting::getVal('payment_methods', '[]');
        $activeMethods = json_decode($savedPayment, true) ?: [];

        // Untuk Admin, kita tampilkan SEMUA metode master yang ada, 
        // tapi kita bisa nambahin yang baru kalau memang terdaftar di settings tapi belum ada di master
        foreach ($activeMethods as $id => $isActive) {
            if (!isset($allMethods[$id])) {
                $allMethods[$id] = strtoupper(str_replace('_', ' ', $id));
            }
        }

        return $allMethods;
    }
    public $dateStart = '';
    public $dateEnd = '';
    public $perPage = 25;

    // Edit & Completion Properties
    public $isEditingTrx = false;
    public $editTrxId = null;
    public $edit_nama, $edit_email, $edit_no_wa, $edit_alamat, $edit_sosial_media;
    public $edit_waktu_mulai, $edit_waktu_selesai;
    public $edit_subtotal, $edit_diskon, $edit_denda, $edit_denda_kerusakan;
    public $edit_status, $edit_metode_pembayaran, $edit_catatan_kerusakan;
    public $edit_unit_ids = [];
    public $allUnitsList = [];
    public $availableVouchersList = [];
    public $edit_promo_id = null;
    public $editOriginalData = []; // Untuk simpan snapshot harga & data awal
    public $isConfirmingEdit = false;
    public $editDiffs = [];
    public $isConfirmingExtend = false;
    public $extendPendingSendWa = false;
    public $extendDiffData = [];

    public $completingTrxId = null;
    public $dendaAmount = 0;
    public $dendaKerusakanAmount = 0;
    public $catatanKerusakan = '';
    public $dendaMethod = 'cash';
    public $lateDurationText = '';
    public $isOverdue = false;

    // Quick Extend Properties
    public $isExtendingTrx = false;
    public $extendTrxId = null;
    public $extendPreset = '24'; // '12', '24', '48', 'custom'
    public $extendHours = 24;
    public $extendCurrentSelesai = '';
    public $extendNewSelesai = '';
    public $extendBiayaSewa = 0;
    public $extendDendaTelat = 0;
    public $extendDiskon = 0;
    public $extendTotalTagihan = 0;
    public $extendCatatan = '';
    public $extendUnitsRateInfo = [];
    public $extendWaUrl = '';

    // Inspect Modal
    public $inspectTrxId = null;
    public $inspectTrx = null;
    public $sortField = 'waktu_mulai';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'dateStart' => ['except' => ''],
        'dateEnd' => ['except' => ''],
        'sortField' => ['except' => 'waktu_mulai'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingDateStart() { $this->resetPage(); }
    public function updatingDateEnd() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function markAsPaid($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'pending_confirmation'])) {
            $before = ['status' => $rental->status];
            $rental->update(['status' => 'paid', 'paid_at' => now()]);
            $after = ['status' => 'paid'];
            
            $rentalLabel = $rental->nama ? "{$rental->nama} ({$rental->booking_code})" : $rental->booking_code;
            $this->logActivity('mark_as_paid', $rental, "Memvalidasi pembayaran transaksi {$rentalLabel}", $before, $after);
            
            $this->calculateAffiliateCommission($rental);

            // Send Email Notification
            $this->sendEmailNotification($rental, 'paid');

            // --- PUSH NOTIFICATION KE ADMIN ---
            try {
                \App\Services\OneSignalService::sendToAdmins(
                    "✅ Pembayaran Divalidasi Manual: " . strtoupper($rental->nama) . " (Rp " . number_format($rental->grand_total, 0, ',', '.') . ") oleh " . auth()->user()->name,
                    "💰 PEMBAYARAN TERVALIDASI",
                    route('admin.monitoring')
                );
            } catch (\Exception $e) { }
        }
    }

    public function handover($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'paid') {
            $before = ['status' => $rental->status];
            $rental->update(['status' => 'renting', 'handed_over_at' => now()]);
            $after = ['status' => 'renting'];
            $rentalLabel = $rental->nama ? "{$rental->nama} ({$rental->booking_code})" : $rental->booking_code;
            $this->logActivity('handover_unit', $rental, "Validasi ambil unit untuk transaksi {$rentalLabel} (via Transaksi)", $before, $after);
            session()->flash('message', 'Unit berhasil divalidasi ambil.');
        }
    }

    public function cancel($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid', 'pending_confirmation'])) {
            $before = ['status' => $rental->status];
            $rental->update(['status' => 'cancelled']);
            $after = ['status' => 'cancelled'];
            $rentalLabel = $rental->nama ? "{$rental->nama} ({$rental->booking_code})" : $rental->booking_code;
            $this->logActivity('cancel_transaction', $rental, "Membatalkan transaksi {$rentalLabel}", $before, $after);

            // Send Email Notification
            $this->sendEmailNotification($rental, 'cancelled');

            // --- PUSH NOTIFICATION KE ADMIN ---
            try {
                \App\Services\OneSignalService::sendToAdmins(
                    "❌ Pesanan Dibatalkan Admin: " . strtoupper($rental->nama) . " oleh " . auth()->user()->name,
                    "⚠️ PESANAN BATAL",
                    route('admin.monitoring')
                );
            } catch (\Exception $e) { }
        }
    }

    private function sendEmailNotification($rental, $type)
    {
        $isAdminEmailEnabled = \App\Models\Setting::getVal('is_email_active', '1') == '1';
        $isUserEmailEnabled = \App\Models\Setting::getVal('is_user_email_active', '1') == '1';
        
        if (!$isAdminEmailEnabled && !$isUserEmailEnabled) return;

        try {
            // 1. Prepare recipients
            $emails = [];
            
            if ($isAdminEmailEnabled) {
                $adminEmail = \App\Models\Setting::getVal('admin_email_recipients');
                if (!$adminEmail) {
                    $adminEmail = config('mail.admin_email') ?: config('mail.from.address');
                }
                if ($adminEmail) {
                    $emails = array_merge($emails, array_map('trim', explode(',', $adminEmail)));
                }
            }

            if ($isUserEmailEnabled && $rental->email) {
                $emails[] = $rental->email;
            }

            // 2. Send the right notification
            if (!empty($emails)) {
                if ($type === 'paid') {
                    \App\Helpers\MailHelper::logAndQueue($emails, new \App\Mail\PaymentConfirmedNotification($rental), 'Payment Confirmation');
                } elseif ($type === 'cancelled') {
                    \App\Helpers\MailHelper::logAndQueue($emails, new \App\Mail\OrderCancelledNotification($rental), 'Order Cancellation');
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TRANSACTION EMAIL FAILED: " . $e->getMessage());
        }
    }

    public function openDendaModal($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $trx = Rental::findOrFail($id);
        $this->completingTrxId = $id;
        $this->dendaAmount = 0;
        $this->dendaKerusakanAmount = 0;
        $this->catatanKerusakan = '';
        $this->dendaMethod = 'cash';

        // Calculate late duration
        $end = \Carbon\Carbon::parse($trx->waktu_selesai);
        $diff = now()->diff($end);
        $this->isOverdue = now() > $end;

        $parts = [];
        if ($diff->d > 0) $parts[] = $diff->d . 'd';
        if ($diff->h > 0) $parts[] = $diff->h . 'h';
        if ($diff->i > 0) $parts[] = $diff->i . 'm';
        $this->lateDurationText = !empty($parts) ? implode(' ', $parts) : '0m';
    }

    public function closeDendaModal()
    {
        $this->completingTrxId = null;
    }

    public function confirmDenda()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $this->validate([
            'dendaAmount' => 'required|numeric|min:0',
            'dendaKerusakanAmount' => 'required|numeric|min:0',
            'dendaMethod' => 'required|in:cash,qris',
        ]);

        if ($this->completingTrxId) {
            $rental = Rental::findOrFail($this->completingTrxId);
            if ($rental->status === 'renting') {
                $before = [
                    'status' => $rental->status,
                    'denda' => $rental->denda,
                    'denda_kerusakan' => $rental->denda_kerusakan,
                    'grand_total' => $rental->grand_total,
                ];

                $newGrandTotal = $rental->grand_total + (int)$this->dendaAmount + (int)$this->dendaKerusakanAmount;
                $rental->update([
                    'status' => 'completed',
                    'denda' => (int)$this->dendaAmount,
                    'denda_kerusakan' => (int)$this->dendaKerusakanAmount,
                    'catatan_kerusakan' => $this->catatanKerusakan,
                    'grand_total' => $newGrandTotal,
                    'denda_payment_method' => ($this->dendaAmount > 0 || $this->dendaKerusakanAmount > 0) ? $this->dendaMethod : null,
                    'completed_at' => now(),
                ]);

                $after = [
                    'status' => 'completed',
                    'denda' => (int)$this->dendaAmount,
                    'denda_kerusakan' => (int)$this->dendaKerusakanAmount,
                    'grand_total' => $newGrandTotal,
                ];

                $this->calculateAffiliateCommission($rental);
                
                $rentalLabel = $rental->nama ? "{$rental->nama} ({$rental->booking_code})" : $rental->booking_code;
                $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa {$rentalLabel} dengan total denda Rp" . number_format($this->dendaAmount + $this->dendaKerusakanAmount, 0, ',', '.'), $before, $after);
            }
        }

        $this->closeDendaModal();
    }

    public function finishWithoutDenda($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'renting') {
            $rental->update([
                'status' => 'completed',
                'denda' => 0,
                'denda_payment_method' => null,
                'completed_at' => now(),
            ]);
            $this->calculateAffiliateCommission($rental);
            
            $rentalLabel = $rental->nama ? "{$rental->nama} ({$rental->booking_code})" : $rental->booking_code;
            $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa {$rentalLabel} tanpa denda");
        }
    }

    // ==========================================
    // QUICK EXTEND (PERPANJANG SEWA)
    // ==========================================
    public function openExtendModal($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $trx = Rental::with('units')->findOrFail($id);
        $this->extendTrxId = $trx->id;
        $this->isExtendingTrx = true;
        $this->extendPreset = '24';
        $this->extendHours = 24;
        $this->extendCurrentSelesai = $trx->waktu_selesai->format('Y-m-d\TH:i');
        $this->extendCatatan = '';
        $this->extendDiskon = 0;
        $this->extendWaUrl = '';

        // Auto calculate late fine jika saat klik extend, waktu selesai sudah terlewat
        $currentEnd = \Carbon\Carbon::parse($trx->waktu_selesai);
        $tolerance = (int) \App\Models\Setting::getVal('late_tolerance_minutes', 60);
        if (now() > $currentEnd->copy()->addMinutes($tolerance)) {
            $hoursLate = ceil(now()->diffInMinutes($currentEnd) / 60);
            $totalHourlyRate = 0;
            foreach ($trx->units as $u) {
                $totalHourlyRate += ($u->harga_per_jam ?: round($u->harga_per_hari / 24));
            }
            $this->extendDendaTelat = $hoursLate * $totalHourlyRate;
        } else {
            $this->extendDendaTelat = 0;
        }

        // Simpan info rate unit untuk panduan admin di modal
        $this->extendUnitsRateInfo = [];
        foreach ($trx->units as $u) {
            $this->extendUnitsRateInfo[] = [
                'seri' => $u->seri,
                'per_hari' => $u->harga_per_hari,
                'per_jam' => $u->harga_per_jam,
            ];
        }

        $this->recalculateExtendCalculation();
    }

    public function updatedExtendPreset($val)
    {
        if ($val === 'custom') {
            // Keep current extendNewSelesai or preset default
            $this->recalculateExtendCalculation();
            return;
        }

        $this->extendHours = (int) $val;
        $currentEnd = \Carbon\Carbon::parse($this->extendCurrentSelesai);
        $this->extendNewSelesai = $currentEnd->copy()->addHours($this->extendHours)->format('Y-m-d\TH:i');
        $this->recalculateExtendCalculation();
    }

    public function updatedExtendHours()
    {
        if ($this->extendPreset !== 'custom') {
            $currentEnd = \Carbon\Carbon::parse($this->extendCurrentSelesai);
            $this->extendNewSelesai = $currentEnd->copy()->addHours((int)$this->extendHours)->format('Y-m-d\TH:i');
        }
        $this->recalculateExtendCalculation();
    }

    public function updatedExtendNewSelesai($val)
    {
        if ($this->extendPreset === 'custom' && $val) {
            try {
                $currentEnd = \Carbon\Carbon::parse($this->extendCurrentSelesai);
                $newEnd = \Carbon\Carbon::parse($val);
                if ($newEnd > $currentEnd) {
                    $this->extendHours = max(1, $currentEnd->diffInHours($newEnd));
                }
            } catch (\Exception $e) {}
        }
        $this->recalculateExtendCalculation();
    }

    public function updatedExtendBiayaSewa()
    {
        $this->recalculateExtendGrandTotal();
    }

    public function updatedExtendDendaTelat()
    {
        $this->recalculateExtendGrandTotal();
    }

    public function updatedExtendDiskon()
    {
        $this->recalculateExtendGrandTotal();
    }

    public function recalculateExtendCalculation()
    {
        if (!$this->extendTrxId) return;

        $trx = Rental::with('units')->find($this->extendTrxId);
        if (!$trx) return;

        $currentEnd = \Carbon\Carbon::parse($this->extendCurrentSelesai);
        if ($this->extendPreset !== 'custom') {
            $this->extendNewSelesai = $currentEnd->copy()->addHours((int)$this->extendHours)->format('Y-m-d\TH:i');
        }

        // Kalkulasi tarif sewa otomatis berdasarkan durasi perpanjangan
        $hours = (int) $this->extendHours;
        $days = floor($hours / 24);
        $remHours = $hours % 24;

        $computedCost = 0;
        foreach ($trx->units as $u) {
            $computedCost += ($days * $u->harga_per_hari) + ($remHours * ($u->harga_per_jam ?: round($u->harga_per_hari / 24)));
        }

        $this->extendBiayaSewa = $computedCost;
        $this->recalculateExtendGrandTotal();
    }

    public function recalculateExtendGrandTotal()
    {
        $biaya = (float) ($this->extendBiayaSewa ?: 0);
        $denda = (float) ($this->extendDendaTelat ?: 0);
        $diskon = (float) ($this->extendDiskon ?: 0);

        $this->extendTotalTagihan = max(0, $biaya + $denda - $diskon);
    }

    public function closeExtendModal()
    {
        $this->isExtendingTrx = false;
        $this->extendTrxId = null;
        $this->extendWaUrl = '';
        $this->isConfirmingExtend = false;
        $this->extendPendingSendWa = false;
        $this->extendDiffData = [];
    }

    public function confirmExtend($andSendWa = false)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $this->validate([
            'extendNewSelesai' => 'required',
            'extendBiayaSewa' => 'required|numeric|min:0',
            'extendDendaTelat' => 'nullable|numeric|min:0',
            'extendDiskon' => 'nullable|numeric|min:0',
        ]);

        $trx = Rental::with('units')->findOrFail($this->extendTrxId);
        $currentEnd = \Carbon\Carbon::parse($trx->waktu_selesai);
        $newEnd = \Carbon\Carbon::parse($this->extendNewSelesai);

        if ($newEnd <= $currentEnd) {
            $this->addError('extendNewSelesai', 'Waktu perpanjangan baru harus setelah jadwal selesai sebelumnya.');
            return;
        }

        $biayaSewa = (float) $this->extendBiayaSewa;
        $dendaTelat = (float) ($this->extendDendaTelat ?: 0);
        $diskon = (float) ($this->extendDiskon ?: 0);
        $selisihTagihan = $biayaSewa + $dendaTelat - $diskon;

        $this->extendPendingSendWa = $andSendWa;
        $this->extendDiffData = [
            'booking_code' => $trx->booking_code,
            'nama' => $trx->nama,
            'unit_names' => $trx->units->pluck('seri')->implode(', ') ?: ($trx->unit->seri ?? 'Unit'),
            'old_selesai' => $currentEnd->format('d M Y, H:i'),
            'new_selesai' => $newEnd->format('d M Y, H:i'),
            'durasi_tambah' => $this->extendHours,
            'biaya_tambah' => $biayaSewa,
            'denda_telat' => $dendaTelat,
            'diskon' => $diskon,
            'total_tambah' => $selisihTagihan,
            'grand_total_lama' => $trx->grand_total,
            'grand_total_baru' => $trx->grand_total + $selisihTagihan,
            'catatan' => $this->extendCatatan,
        ];

        $this->isConfirmingExtend = true;
    }

    public function cancelConfirmExtend()
    {
        $this->isConfirmingExtend = false;
    }

    public function saveExtend($andSendWa = null)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;

        $actualSendWa = is_null($andSendWa) ? $this->extendPendingSendWa : $andSendWa;

        $this->validate([
            'extendNewSelesai' => 'required',
            'extendBiayaSewa' => 'required|numeric|min:0',
            'extendDendaTelat' => 'nullable|numeric|min:0',
            'extendDiskon' => 'nullable|numeric|min:0',
        ]);

        $trx = Rental::with('units')->findOrFail($this->extendTrxId);

        $currentEnd = \Carbon\Carbon::parse($trx->waktu_selesai);
        $newEnd = \Carbon\Carbon::parse($this->extendNewSelesai);

        if ($newEnd <= $currentEnd) {
            $this->addError('extendNewSelesai', 'Waktu perpanjangan baru harus setelah jadwal selesai sebelumnya.');
            return;
        }

        $biayaSewa = (float) $this->extendBiayaSewa;
        $dendaTelat = (float) ($this->extendDendaTelat ?: 0);
        $diskon = (float) ($this->extendDiskon ?: 0);
        $selisihTagihan = $biayaSewa + $dendaTelat - $diskon;

        $before = [
            'waktu_selesai' => $trx->waktu_selesai->format('Y-m-d H:i:s'),
            'subtotal_harga' => $trx->subtotal_harga,
            'denda' => $trx->denda,
            'potongan_diskon' => $trx->potongan_diskon,
            'grand_total' => $trx->grand_total,
        ];

        // Update rental: perpanjang waktu selesai dan tambahkan komponen biaya
        $newSubtotal = $trx->subtotal_harga + $biayaSewa;
        $newDenda = $trx->denda + $dendaTelat;
        $newDiskon = $trx->potongan_diskon + $diskon;
        $newGrandTotal = $trx->grand_total + $selisihTagihan;

        $trx->update([
            'waktu_selesai' => $newEnd,
            'subtotal_harga' => $newSubtotal,
            'denda' => $newDenda,
            'potongan_diskon' => $newDiskon,
            'grand_total' => $newGrandTotal,
        ]);

        $after = [
            'waktu_selesai' => $newEnd->format('Y-m-d H:i:s'),
            'subtotal_harga' => $newSubtotal,
            'denda' => $newDenda,
            'potongan_diskon' => $newDiskon,
            'grand_total' => $newGrandTotal,
            'perpanjangan_biaya' => $biayaSewa,
            'perpanjangan_denda' => $dendaTelat,
            'perpanjangan_diskon' => $diskon,
            'catatan' => $this->extendCatatan,
        ];

        $unitNames = $trx->units->pluck('seri')->implode(', ') ?: ($trx->unit->seri ?? 'Unit');
        $rentalLabel = $trx->nama ? "{$trx->nama} ({$trx->booking_code})" : $trx->booking_code;
        $logDesc = "Perpanjang sewa {$rentalLabel} (+{$this->extendHours} jam s/d {$newEnd->format('d/m/Y H:i')}) tagihan baru: Rp" . number_format($selisihTagihan, 0, ',', '.');
        if ($dendaTelat > 0) {
            $logDesc .= " (termasuk denda telat Rp" . number_format($dendaTelat, 0, ',', '.') . ")";
        }
        if ($diskon > 0) {
            $logDesc .= " (diskon Rp" . number_format($diskon, 0, ',', '.') . ")";
        }
        $this->logActivity('extend_rental', $trx, $logDesc, $before, $after);

        // Siapkan Template Pesan WhatsApp
        $waMessage = "Halo Kak *" . ($trx->nama ?: 'Kak') . "*,\n";
        $waMessage .= "Perpanjangan sewa untuk unit *" . $unitNames . "* (" . $trx->booking_code . ") telah berhasil diproses! ⚡\n\n";
        $waMessage .= "📅 *Jadwal Selesai Baru:*\n" . $newEnd->format('d M Y, H:i') . " WIB\n\n";
        $waMessage .= "💰 *Rincian Biaya Tambahan:*\n";
        $waMessage .= "• Biaya Sewa Tambahan: Rp " . number_format($biayaSewa, 0, ',', '.') . "\n";
        if ($dendaTelat > 0) {
            $waMessage .= "• Denda Keterlambatan: Rp " . number_format($dendaTelat, 0, ',', '.') . "\n";
        }
        if ($diskon > 0) {
            $waMessage .= "• Potongan Diskon: -Rp " . number_format($diskon, 0, ',', '.') . "\n";
        }
        $waMessage .= "-----------------------------\n";
        $waMessage .= "*Total Tagihan Perpanjangan: Rp " . number_format($selisihTagihan, 0, ',', '.') . "*\n\n";
        if ($this->extendCatatan) {
            $waMessage .= "📝 *Catatan:* " . $this->extendCatatan . "\n\n";
        }
        $waMessage .= "Lihat detail invoice: " . route('public.success', $trx->booking_code) . "\n\n";
        $waMessage .= "Silakan lakukan pembayaran sesuai nominal di atas. Terima kasih telah mempercayakan sewa di Rent Space! 🙏✨";

        $waNumber = \App\Helpers\CustomerHelper::formatWa($trx->no_wa);
        $waUrl = "https://wa.me/" . $waNumber . "?text=" . rawurlencode($waMessage);

        session()->flash('message', "Sewa {$trx->booking_code} berhasil diperpanjang s/d {$newEnd->format('d/m/Y H:i')}.");

        if ($actualSendWa && $waNumber) {
            $this->dispatch('open-url', url: $waUrl);
        }

        $this->closeExtendModal();
    }

    private function calculateAffiliateCommission($rental)
    {
        if ($rental->affiliator_id) {
            // Prevent double crediting
            $exists = \App\Models\AffiliateCommission::where('rental_id', $rental->id)->exists();
            if ($exists) {
                return;
            }

            $profile = \App\Models\AffiliatorProfile::where('user_id', $rental->affiliator_id)->first();
            if ($profile && $profile->status === 'approved') {
                // Commission is usually based on subtotal_harga (the rental price excluding Unique Code/Denda)
                $amount = $rental->subtotal_harga * ($profile->commission_rate / 100);
                
                \App\Models\AffiliateCommission::create([
                    'affiliator_id' => $rental->affiliator_id,
                    'rental_id' => $rental->id,
                    'amount' => $amount,
                    'status' => 'earned'
                ]);

                // Absolute Recalculation (The Ultimate Fix)
                $totalEarned = \App\Models\AffiliateCommission::where('affiliator_id', $rental->affiliator_id)->sum('amount');
                $totalWithdrawn = \App\Models\AffiliatePayout::where('affiliator_id', $rental->affiliator_id)->sum('amount');
                
                $profile->balance = $totalEarned - $totalWithdrawn;
                $profile->save();
            }
        }
    }

    public function deleteRow($id)
    {
        if (auth()->user()->role !== 'admin')
            return;

        // Clear any active UI state if the being deleted ID matches
        if ($this->inspectTrxId == $id) {
            $this->closeInspect();
        }
        if ($this->editTrxId == $id) {
            $this->closeEditModal();
        }
        if ($this->completingTrxId == $id) {
            $this->closeDendaModal();
        }
        if ($this->extendTrxId == $id) {
            $this->closeExtendModal();
        }

        // Use where()->delete() instead of findOrFail()->delete()
        // If it's already in trash, this will just call soft delete again (no effect)
        $rental = Rental::find($id);
        Rental::where('id', $id)->delete();

        if ($rental && $rental->affiliator_id) {
            $this->syncAffiliateBalance($rental->affiliator_id);
        }
        
        session()->flash('message', 'Transaksi dipindahkan ke kotak sampah.');
    }

    public function restore($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $rental = Rental::withTrashed()->find($id);
        Rental::withTrashed()->where('id', $id)->restore();

        if ($rental && $rental->affiliator_id) {
            $this->syncAffiliateBalance($rental->affiliator_id);
        }

        session()->flash('message', 'Transaksi berhasil dikembalikan.');
    }

    public function forceDelete($id)
    {
        if (auth()->user()->role !== 'admin') return;

        $rental = Rental::withTrashed()->find($id);
        Rental::withTrashed()->where('id', $id)->forceDelete();

        if ($rental && $rental->affiliator_id) {
            $this->syncAffiliateBalance($rental->affiliator_id);
        }

        session()->flash('message', 'Transaksi telah dihapus permanen.');
    }

    private function syncAffiliateBalance($userId)
    {
        $profile = \App\Models\AffiliatorProfile::where('user_id', $userId)->first();
        if ($profile) {
            // Commissions only from non-deleted rentals
            $totalEarned = \App\Models\AffiliateCommission::where('affiliator_id', $userId)
                ->whereHas('rental')
                ->sum('amount');
            
            $totalWithdrawn = \App\Models\AffiliatePayout::where('affiliator_id', $userId)->sum('amount');
            
            $profile->balance = $totalEarned - $totalWithdrawn;
            $profile->save();
        }
    }

    public function openInspect($id)
    {
        if ($this->inspectTrxId === $id) {
            $this->closeInspect();
            return;
        }
        $this->inspectTrxId = $id;
        $this->inspectTrx = Rental::with(['units', 'affiliator.affiliateProfile', 'commissions'])->find($id);
    }

    public function closeInspect()
    {
        $this->inspectTrxId = null;
        $this->inspectTrx = null;
    }

    public function editTrx($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $trx = Rental::with('units')->findOrFail($id);
        $this->editTrxId = $trx->id;
        $this->edit_nama = $trx->nama;
        $this->edit_email = $trx->email;
        $this->edit_no_wa = $trx->no_wa;
        $this->edit_alamat = $trx->alamat;
        $this->edit_sosial_media = $trx->sosial_media;
        $this->edit_waktu_mulai = $trx->waktu_mulai->format('Y-m-d\TH:i');
        $this->edit_waktu_selesai = $trx->waktu_selesai->format('Y-m-d\TH:i');
        $this->edit_subtotal = $trx->subtotal_harga;
        $this->edit_diskon = $trx->potongan_diskon;
        $this->edit_denda = $trx->denda;
        $this->edit_denda_kerusakan = $trx->denda_kerusakan;
        $this->edit_catatan_kerusakan = $trx->catatan_kerusakan;
        $this->edit_status = $trx->status;
        $this->edit_metode_pembayaran = strtolower($trx->metode_pembayaran);
        $this->edit_unit_ids = $trx->units->pluck('id')->toArray();
        $this->allUnitsList = \App\Models\Unit::orderBy('seri')->get();
        $this->availableVouchersList = \App\Models\PricingRule::orderBy('nama_promo')->get();
        $this->edit_promo_id = $trx->applied_promo_id ? (int)$trx->applied_promo_id : null;

        // Simpan data awal untuk referensi staf agar tahu harga awal & diff
        $this->editOriginalData = [
            'promo_id' => $this->edit_promo_id,
            'promo_name' => $trx->applied_promo_name ?: ($trx->appliedPromo?->nama_promo ?: '-'),
            'affiliate_code' => $trx->affiliate_code ?: null,
            'booking_code' => $trx->booking_code,
            'nama' => $trx->nama,
            'email' => $trx->email,
            'no_wa' => $trx->no_wa,
            'alamat' => $trx->alamat,
            'waktu_mulai' => $this->edit_waktu_mulai,
            'waktu_selesai' => $this->edit_waktu_selesai,
            'subtotal' => (float)$trx->subtotal_harga,
            'diskon' => (float)$trx->potongan_diskon,
            'denda' => (float)$trx->denda,
            'denda_kerusakan' => (float)$trx->denda_kerusakan,
            'grand_total' => (float)$trx->grand_total,
            'status' => $trx->status,
            'metode_pembayaran' => strtolower($trx->metode_pembayaran),
            'unit_ids' => $this->edit_unit_ids,
            'unit_names' => $trx->units->pluck('seri')->implode(', ') ?: 'Unit',
        ];

        $this->isConfirmingEdit = false;
        $this->editDiffs = [];
        $this->isEditingTrx = true;
    }

    public function updatedEditPromoId($val)
    {
        if (empty($val)) {
            return;
        }

        $voucher = \App\Models\PricingRule::find($val);
        if (!$voucher) return;

        if ($voucher->tipe === 'diskon_persen') {
            $percent = min(100, max(0, (float)$voucher->value));
            $this->edit_diskon = round(((float)$this->edit_subtotal * $percent) / 100);
        } elseif ($voucher->tipe === 'diskon_nominal') {
            $this->edit_diskon = min((float)$this->edit_subtotal, (float)$voucher->value);
        }
    }

    public function recalculateEditSubtotal()
    {
        if (empty($this->edit_unit_ids) || !$this->edit_waktu_mulai || !$this->edit_waktu_selesai) {
            return;
        }

        try {
            $start = \Carbon\Carbon::parse($this->edit_waktu_mulai);
            $end = \Carbon\Carbon::parse($this->edit_waktu_selesai);
            $diffInHours = max(1, $start->diffInHours($end));
            $days = floor($diffInHours / 24);
            $remainingHours = $diffInHours % 24;

            $selectedUnits = \App\Models\Unit::whereIn('id', $this->edit_unit_ids)->get();
            $newSubtotal = 0;
            foreach ($selectedUnits as $u) {
                $newSubtotal += ($days * $u->harga_per_hari) + ($remainingHours * $u->harga_per_jam);
            }
            $this->edit_subtotal = $newSubtotal;
        } catch (\Exception $e) {}
    }

    public function closeEditModal()
    {
        $this->isEditingTrx = false;
        $this->editTrxId = null;
        $this->edit_unit_ids = [];
        $this->isConfirmingEdit = false;
        $this->editDiffs = [];
        $this->editOriginalData = [];
    }

    public function confirmEdit()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;

        $this->validate([
            'edit_nama' => 'required',
            'edit_waktu_mulai' => 'required',
            'edit_waktu_selesai' => 'required',
            'edit_subtotal' => 'required|numeric|min:0',
            'edit_diskon' => 'nullable|numeric|min:0',
            'edit_denda' => 'nullable|numeric|min:0',
            'edit_denda_kerusakan' => 'nullable|numeric|min:0',
        ]);

        $trx = Rental::with('units')->findOrFail($this->editTrxId);
        $diffs = [];

        if (trim(strtoupper($this->edit_nama)) !== trim(strtoupper($trx->nama))) {
            $diffs['Nama Pelanggan'] = ['old' => $trx->nama, 'new' => strtoupper($this->edit_nama)];
        }
        if (trim($this->edit_no_wa) !== trim($trx->no_wa)) {
            $diffs['No. WhatsApp'] = ['old' => $trx->no_wa, 'new' => $this->edit_no_wa];
        }
        if (trim($this->edit_email) !== trim($trx->email)) {
            $diffs['Email'] = ['old' => $trx->email ?: '-', 'new' => $this->edit_email ?: '-'];
        }

        $oldStart = $trx->waktu_mulai->format('Y-m-d\TH:i');
        if ($this->edit_waktu_mulai !== $oldStart) {
            $diffs['Waktu Mulai'] = [
                'old' => \Carbon\Carbon::parse($oldStart)->format('d M Y, H:i'),
                'new' => \Carbon\Carbon::parse($this->edit_waktu_mulai)->format('d M Y, H:i'),
            ];
        }

        $oldEnd = $trx->waktu_selesai->format('Y-m-d\TH:i');
        if ($this->edit_waktu_selesai !== $oldEnd) {
            $diffs['Waktu Selesai'] = [
                'old' => \Carbon\Carbon::parse($oldEnd)->format('d M Y, H:i'),
                'new' => \Carbon\Carbon::parse($this->edit_waktu_selesai)->format('d M Y, H:i'),
            ];
        }

        $oldUnitIds = $trx->units->pluck('id')->sort()->values()->toArray();
        $newUnitIds = collect($this->edit_unit_ids)->sort()->values()->toArray();
        if ($oldUnitIds !== $newUnitIds) {
            $newUnitNames = \App\Models\Unit::whereIn('id', $this->edit_unit_ids)->pluck('seri')->implode(', ');
            $oldUnitNames = $trx->units->pluck('seri')->implode(', ');
            $diffs['Pilihan Unit'] = ['old' => $oldUnitNames, 'new' => $newUnitNames ?: '-'];
        }

        if ((float)$this->edit_subtotal !== (float)$trx->subtotal_harga) {
            $diffs['Biaya Sewa / Subtotal'] = [
                'old' => 'Rp ' . number_format($trx->subtotal_harga, 0, ',', '.'),
                'new' => 'Rp ' . number_format($this->edit_subtotal, 0, ',', '.'),
            ];
        }

        $oldPromoId = $trx->applied_promo_id ? (int)$trx->applied_promo_id : null;
        $newPromoId = $this->edit_promo_id ? (int)$this->edit_promo_id : null;
        if ($oldPromoId !== $newPromoId) {
            $oldPromoName = $trx->applied_promo_name ?: ($trx->appliedPromo?->nama_promo ?: 'Tanpa Voucher');
            $newPromo = $this->edit_promo_id ? \App\Models\PricingRule::find($this->edit_promo_id) : null;
            $newPromoName = $newPromo ? ($newPromo->nama_promo . ($newPromo->kode_promo ? ' (' . $newPromo->kode_promo . ')' : '')) : 'Tanpa Voucher';
            $diffs['Voucher / Promo'] = [
                'old' => $oldPromoName,
                'new' => $newPromoName,
            ];
        }

        if ((float)($this->edit_diskon ?: 0) !== (float)$trx->potongan_diskon) {
            $diffs['Potongan Diskon'] = [
                'old' => 'Rp ' . number_format($trx->potongan_diskon, 0, ',', '.'),
                'new' => 'Rp ' . number_format($this->edit_diskon ?: 0, 0, ',', '.'),
            ];
        }

        if ((float)($this->edit_denda ?: 0) !== (float)$trx->denda) {
            $diffs['Denda Telat'] = [
                'old' => 'Rp ' . number_format($trx->denda, 0, ',', '.'),
                'new' => 'Rp ' . number_format($this->edit_denda ?: 0, 0, ',', '.'),
            ];
        }

        if ((float)($this->edit_denda_kerusakan ?: 0) !== (float)$trx->denda_kerusakan) {
            $diffs['Denda Kerusakan'] = [
                'old' => 'Rp ' . number_format($trx->denda_kerusakan, 0, ',', '.'),
                'new' => 'Rp ' . number_format($this->edit_denda_kerusakan ?: 0, 0, ',', '.'),
            ];
        }

        $newGrandTotal = (float)$this->edit_subtotal - (float)($this->edit_diskon ?: 0) + (float)($this->edit_denda ?: 0) + (float)($this->edit_denda_kerusakan ?: 0) + $trx->kode_unik_pembayaran;
        if ($newGrandTotal !== (float)$trx->grand_total) {
            $diffs['Total Akhir (Grand Total)'] = [
                'old' => 'Rp ' . number_format($trx->grand_total, 0, ',', '.'),
                'new' => 'Rp ' . number_format($newGrandTotal, 0, ',', '.'),
            ];
        }

        if ($this->edit_status !== $trx->status) {
            $diffs['Status Transaksi'] = ['old' => strtoupper($trx->status), 'new' => strtoupper($this->edit_status)];
        }

        if (strtolower($this->edit_metode_pembayaran) !== strtolower($trx->metode_pembayaran)) {
            $diffs['Metode Bayar'] = [
                'old' => strtoupper($trx->metode_pembayaran),
                'new' => strtoupper($this->edit_metode_pembayaran),
            ];
        }

        $this->editDiffs = $diffs;
        $this->isConfirmingEdit = true;
    }

    public function cancelConfirmEdit()
    {
        $this->isConfirmingEdit = false;
    }

    public function updateTrx()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;

        $this->validate([
            'edit_nama' => 'required',
            'edit_waktu_mulai' => 'required',
            'edit_waktu_selesai' => 'required',
            'edit_subtotal' => 'required|numeric|min:0',
            'edit_diskon' => 'nullable|numeric|min:0',
            'edit_denda' => 'nullable|numeric|min:0',
            'edit_denda_kerusakan' => 'nullable|numeric|min:0',
        ]);

        $trx = Rental::findOrFail($this->editTrxId);

        $before = [
            'nama' => $trx->nama,
            'subtotal' => $trx->subtotal_harga,
            'diskon' => $trx->potongan_diskon,
            'denda' => $trx->denda,
            'denda_kerusakan' => $trx->denda_kerusakan,
            'grand_total' => $trx->grand_total,
            'status' => $trx->status,
        ];

        // Recalculate Grand Total
        $grandTotal = (float)$this->edit_subtotal - (float)($this->edit_diskon ?: 0) + (float)($this->edit_denda ?: 0) + (float)($this->edit_denda_kerusakan ?: 0) + $trx->kode_unik_pembayaran;

        $selectedPromo = $this->edit_promo_id ? \App\Models\PricingRule::find($this->edit_promo_id) : null;

        $trx->update([
            'nama' => strtoupper($this->edit_nama),
            'email' => $this->edit_email,
            'no_wa' => $this->edit_no_wa,
            'alamat' => strtoupper($this->edit_alamat),
            'sosial_media' => $this->edit_sosial_media,
            'waktu_mulai' => $this->edit_waktu_mulai,
            'waktu_selesai' => $this->edit_waktu_selesai,
            'subtotal_harga' => $this->edit_subtotal,
            'potongan_diskon' => (float)($this->edit_diskon ?: 0),
            'denda' => (float)($this->edit_denda ?: 0),
            'denda_kerusakan' => (float)($this->edit_denda_kerusakan ?: 0),
            'catatan_kerusakan' => $this->edit_catatan_kerusakan,
            'grand_total' => $grandTotal,
            'status' => $this->edit_status,
            'metode_pembayaran' => strtolower($this->edit_metode_pembayaran),
            'applied_promo_id' => $selectedPromo?->id,
            'applied_promo_name' => $selectedPromo?->nama_promo,
        ]);

        if ($selectedPromo) {
            $trx->appliedPromos()->sync([$selectedPromo->id]);
        } else {
            $trx->appliedPromos()->detach();
        }

        if (!empty($this->edit_unit_ids)) {
            $syncData = [];
            $units = \App\Models\Unit::whereIn('id', $this->edit_unit_ids)->get();
            foreach ($units as $u) {
                $syncData[$u->id] = ['price_snapshot' => $u->harga_per_hari];
            }
            $trx->units()->sync($syncData);
        }

        $after = [
            'nama' => strtoupper($this->edit_nama),
            'subtotal' => (float)$this->edit_subtotal,
            'diskon' => (float)$this->edit_diskon,
            'denda' => (float)$this->edit_denda,
            'denda_kerusakan' => (float)$this->edit_denda_kerusakan,
            'grand_total' => (float)$grandTotal,
            'status' => $this->edit_status,
        ];

        $rentalLabel = $trx->nama ? "{$trx->nama} ({$trx->booking_code})" : $trx->booking_code;
        $this->logActivity('edit_transaction', $trx, "Mengedit data transaksi {$rentalLabel}", $before, $after);

        $this->closeEditModal();
        session()->flash('message', 'Transaksi berhasil diperbarui.');
    }

    public function exportCsv()
    {
        if (auth()->user()->role !== 'admin') return;

        $transactions = Rental::with(['units', 'affiliator', 'commissions'])
            ->when($this->filterStatus === 'trashed', fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
                $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->orWhere('booking_code', 'like', '%' . $this->search . '%')
                ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filterStatus && $this->filterStatus !== 'trashed', function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->dateStart, function ($q) {
                $q->whereDate('waktu_mulai', '>=', $this->dateStart);
            })
            ->when($this->dateEnd, function ($q) {
                $q->whereDate('waktu_mulai', '<=', $this->dateEnd);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=mutasi_transaksi_" . now()->format('Y-m-d_His') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID INVOICE', 
                'KODE BOOKING',
                'TGL PESAN',
                'NAMA PENYEWA',
                'WHATSAPP',
                'ALAMAT',
                'UNIT SEWA',
                'WAKTU MULAI', 
                'WAKTU SELESAI', 
                'DURASI (JAM)',
                'SUBTOTAL (RP)', 
                'DISKON (RP)', 
                'KODE UNIK (RP)',
                'DENDA TELAT (RP)',
                'DENDA RUSAK (RP)',
                'GRAND TOTAL (RP)', 
                'METODE BAYAR',
                'STATUS',
                'AFFILIATOR',
                'KOMISI AFF (RP)',
                'PROFIT NETTO (RP)',
                'TGL SELESAI AKTUAL',
                'PROMO APPLIED'
            ], ";");

            foreach ($transactions as $trx) {
                $units = $trx->units->pluck('seri')->implode(', ') ?: ($trx->unit->seri ?? '-');
                $commission = $trx->commissions->sum('amount');
                $netProfit = $trx->grand_total - $commission;
                
                // Duration calculation
                $durasi = 0;
                if ($trx->waktu_mulai && $trx->waktu_selesai) {
                    $durasi = abs(\Carbon\Carbon::parse($trx->waktu_selesai)->diffInHours(\Carbon\Carbon::parse($trx->waktu_mulai)));
                }

                fputcsv($file, [
                    'INV-' . str_pad($trx->id, 5, '0', STR_PAD_LEFT),
                    $trx->booking_code,
                    $trx->created_at->format('d/m/Y H:i'),
                    strtoupper($trx->nama),
                    "'" . $trx->no_wa, // Prepend quote to preserve Phone leading zeros
                    strtoupper($trx->alamat),
                    $units,
                    $trx->waktu_mulai->format('d/m/Y H:i'),
                    $trx->waktu_selesai->format('d/m/Y H:i'),
                    $durasi,
                    $trx->subtotal_harga,
                    $trx->potongan_diskon,
                    $trx->kode_unik_pembayaran,
                    $trx->denda,
                    $trx->denda_kerusakan,
                    $trx->grand_total,
                    $trx->metode_pembayaran,
                    strtoupper($trx->status),
                    $trx->affiliator->name ?? '-',
                    $commission,
                    $netProfit,
                    $trx->completed_at ? $trx->completed_at->format('d/m/Y H:i') : '-',
                    $trx->applied_promo_name ?? '-'
                ], ";");
            }
            fclose($file);
        };

        return response()->streamDownload($callback, "export_transaksi_" . now()->format('Ymd_Hi') . ".csv", $headers);
    }

    public function setFilterStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function render()
    {
        $statusCounts = [
            'all' => Rental::count(),
            'pending' => Rental::whereIn('status', ['pending', 'pending_confirmation'])->count(),
            'paid' => Rental::where('status', 'paid')->count(),
            'renting' => Rental::where('status', 'renting')->count(),
            'completed' => Rental::where('status', 'completed')->count(),
            'cancelled' => Rental::where('status', 'cancelled')->count(),
            'trashed' => auth()->check() && auth()->user()->role === 'admin' ? Rental::onlyTrashed()->count() : 0,
        ];

        $query = Rental::with(['units', 'affiliator', 'commissions'])
            ->when($this->filterStatus === 'trashed', fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
                $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%')
                    ->orWhere('booking_code', 'like', '%' . $this->search . '%')
                    ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filterStatus && $this->filterStatus !== 'all' && $this->filterStatus !== 'trashed', function ($q) {
                if ($this->filterStatus === 'pending') {
                    $q->whereIn('status', ['pending', 'pending_confirmation']);
                } else {
                    $q->where('status', $this->filterStatus);
                }
            })
            ->when($this->dateStart, function ($q) {
                $q->whereDate('waktu_mulai', '>=', $this->dateStart);
            })
            ->when($this->dateEnd, function ($q) {
                $q->whereDate('waktu_mulai', '<=', $this->dateEnd);
            })
            ->orderByRaw("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END ASC")
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.transactions', [
            'transactions' => $query,
            'statusCounts' => $statusCounts,
        ])->layout('layouts.admin');
    }
}