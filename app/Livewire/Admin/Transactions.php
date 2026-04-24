<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination, \App\Traits\LogsStaffActivity;

    public $search = '';
    public $filterStatus = '';
    public $dateStart = '';
    public $dateEnd = '';
    public $perPage = 25;

    // Edit & Completion Properties
    public $isEditingTrx = false;
    public $editTrxId = null;
    public $edit_nama, $edit_nik, $edit_no_wa, $edit_alamat;
    public $edit_waktu_mulai, $edit_waktu_selesai;
    public $edit_subtotal, $edit_diskon, $edit_denda, $edit_denda_kerusakan;
    public $edit_status, $edit_metode_pembayaran, $edit_catatan_kerusakan;

    public $completingTrxId = null;
    public $dendaAmount = 0;
    public $dendaKerusakanAmount = 0;
    public $catatanKerusakan = '';
    public $dendaMethod = 'cash';
    public $lateDurationText = '';

    // Inspect Modal
    public $inspectTrxId = null;
    public $inspectTrx = null;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'dateStart' => ['except' => ''],
        'dateEnd' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
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
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
            $this->calculateAffiliateCommission($rental);
            
            $this->logActivity('mark_as_paid', $rental, "Memvalidasi pembayaran transaksi #{$rental->id}");
        }
    }

    public function cancel($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'cancelled']);
            $this->logActivity('cancel_transaction', $rental, "Membatalkan transaksi #{$rental->id}");
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
        if (now() > $end) {
            $parts = [];
            if ($diff->d > 0)
                $parts[] = $diff->d . ' hari';
            if ($diff->h > 0)
                $parts[] = $diff->h . ' jam';
            if ($diff->i > 0)
                $parts[] = $diff->i . ' menit';
            $this->lateDurationText = implode(' ', $parts);
        }
        else {
            $this->lateDurationText = 'Tidak telat (Dalam masa sewa)';
        }
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
            if (in_array($rental->status, ['pending', 'paid'])) {
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
                $this->calculateAffiliateCommission($rental);
                
                $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa #{$rental->id} dengan denda Rp" . number_format($this->dendaAmount + $this->dendaKerusakanAmount, 0, ',', '.'));
            }
        }

        $this->closeDendaModal();
    }

    public function finishWithoutDenda($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update([
                'status' => 'completed',
                'denda' => 0,
                'denda_payment_method' => null,
                'completed_at' => now(),
            ]);
            $this->calculateAffiliateCommission($rental);
            
            $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa #{$rental->id} tanpa denda");
        }
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
        $trx = Rental::findOrFail($id);
        $this->editTrxId = $trx->id;
        $this->edit_nama = $trx->nama;
        $this->edit_nik = $trx->nik;
        $this->edit_no_wa = $trx->no_wa;
        $this->edit_alamat = $trx->alamat;
        $this->edit_waktu_mulai = $trx->waktu_mulai->format('Y-m-d\TH:i');
        $this->edit_waktu_selesai = $trx->waktu_selesai->format('Y-m-d\TH:i');
        $this->edit_subtotal = $trx->subtotal_harga;
        $this->edit_diskon = $trx->potongan_diskon;
        $this->edit_denda = $trx->denda;
        $this->edit_denda_kerusakan = $trx->denda_kerusakan;
        $this->edit_catatan_kerusakan = $trx->catatan_kerusakan;
        $this->edit_status = $trx->status;
        $this->edit_metode_pembayaran = $trx->metode_pembayaran;
        $this->isEditingTrx = true;
    }

    public function closeEditModal()
    {
        $this->isEditingTrx = false;
        $this->editTrxId = null;
    }

    public function updateTrx()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;

        $this->validate([
            'edit_nama' => 'required',
            'edit_waktu_mulai' => 'required',
            'edit_waktu_selesai' => 'required',
            'edit_subtotal' => 'required|numeric',
            'edit_diskon' => 'required|numeric',
            'edit_denda' => 'required|numeric',
            'edit_denda_kerusakan' => 'required|numeric',
        ]);

        $trx = Rental::findOrFail($this->editTrxId);

        // Recalculate Grand Total
        $grandTotal = $this->edit_subtotal - $this->edit_diskon + $this->edit_denda + $this->edit_denda_kerusakan + $trx->kode_unik_pembayaran;

        $trx->update([
            'nama' => strtoupper($this->edit_nama),
            'nik' => $this->edit_nik,
            'no_wa' => $this->edit_no_wa,
            'alamat' => strtoupper($this->edit_alamat),
            'waktu_mulai' => $this->edit_waktu_mulai,
            'waktu_selesai' => $this->edit_waktu_selesai,
            'subtotal_harga' => $this->edit_subtotal,
            'potongan_diskon' => $this->edit_diskon,
            'denda' => $this->edit_denda,
            'denda_kerusakan' => $this->edit_denda_kerusakan,
            'catatan_kerusakan' => $this->edit_catatan_kerusakan,
            'grand_total' => $grandTotal,
            'status' => $this->edit_status,
            'metode_pembayaran' => $this->edit_metode_pembayaran,
        ]);

        $this->logActivity('edit_transaction', $trx, "Mengedit data transaksi #{$trx->id}");

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
                $q->whereDate('created_at', '>=', $this->dateStart);
            })
            ->when($this->dateEnd, function ($q) {
                $q->whereDate('created_at', '<=', $this->dateEnd);
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
                'NIK',
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
                    "'" . $trx->nik, // Prepend quote to preserve NIK leading zeros in Excel
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

    public function render()
    {
        $query = Rental::with(['units', 'affiliator', 'commissions'])
            ->when($this->filterStatus === 'trashed', fn($q) => $q->onlyTrashed())
            ->when($this->search, function ($q) {
            $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->orWhere('booking_code', 'like', '%' . $this->search . '%')
            ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
        })
            ->when($this->filterStatus && $this->filterStatus !== 'trashed', function ($q) {
            $q->where(fn($qq) => $qq->where('status', $this->filterStatus));
        })
            ->when($this->dateStart, function ($q) {
            $q->whereDate('created_at', '>=', $this->dateStart);
        })
            ->when($this->dateEnd, function ($q) {
            $q->whereDate('created_at', '<=', $this->dateEnd);
        })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.transactions', [
            'transactions' => $query
        ])->layout('layouts.admin');
    }
}