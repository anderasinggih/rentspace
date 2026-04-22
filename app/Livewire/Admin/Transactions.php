<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination;

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
        if (auth()->user()->role !== 'admin')
            return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
            $this->calculateAffiliateCommission($rental);
        }
    }

    public function cancel($id)
    {
        if (auth()->user()->role !== 'admin')
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'cancelled']);
        }
    }

    public function openDendaModal($id)
    {
        if (auth()->user()->role !== 'admin')
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
        if (auth()->user()->role !== 'admin') return;

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
            }
        }

        $this->closeDendaModal();
    }

    public function finishWithoutDenda($id)
    {
        if (auth()->user()->role !== 'admin')
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

        // Use where()->delete() instead of findOrFail()->delete() to avoid 404 on double clicks
        Rental::where('id', $id)->delete();
        
        session()->flash('message', 'Transaksi berhasil dihapus.');
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
        if (auth()->user()->role !== 'admin')
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
        if (auth()->user()->role !== 'admin')
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

        $this->closeEditModal();
        session()->flash('message', 'Transaksi berhasil diperbarui.');
    }

    public function exportCsv()
    {
        if (auth()->user()->role !== 'admin') return;

        $transactions = Rental::with('units')
            ->when($this->search, function ($q) {
                $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filterStatus, function ($q) {
                $q->where(fn($qq) => $qq->where('status', $this->filterStatus));
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
            "Content-Disposition" => "attachment; filename=mutasi_transaksi.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID Transaksi', 
                'NIK',
                'Nama Penyewa', 
                'Alamat',
                'No WhatsApp', 
                'Unit', 
                'Tgl Mulai', 
                'Tgl Selesai', 
                'Subtotal', 
                'Diskon', 
                'Promo Applied',
                'Hari Bonus',
                'Jam Bonus',
                'Kode Unik',
                'Grand Total', 
                'Ref Code',
                'Affiliator',
                'Komisi Affiliator',
                'Profit (Net)',
                'Metode Bayar Trx', 
                'Denda Telat', 
                'Denda Kerusakan', 
                'Metode Bayar Denda',
                'Catatan Kerusakan',
                'Status',
                'Tgl Selesai Aktual',
                'Tgl Dibuat'
            ]);

            foreach ($transactions as $trx) {
                $commission = $trx->commissions->sum('amount');
                $netProfit = $trx->grand_total - $commission;
                
                fputcsv($file, [
                    'INV-' . str_pad($trx->id, 5, '0', STR_PAD_LEFT),
                    $trx->nik,
                    strtoupper($trx->nama),
                    strtoupper($trx->alamat),
                    $trx->no_wa,
                    $trx->units->pluck('seri')->implode(', ') ?: ($trx->unit->seri ?? '-'),
                    $trx->waktu_mulai->format('d/m/Y H:i'),
                    $trx->waktu_selesai->format('d/m/Y H:i'),
                    $trx->subtotal_harga,
                    $trx->potongan_diskon,
                    $trx->applied_promo_name ?? '-',
                    $trx->hari_bonus,
                    $trx->jam_bonus,
                    $trx->kode_unik_pembayaran,
                    $trx->grand_total,
                    $trx->affiliate_code ?? '-',
                    $trx->affiliator->name ?? '-',
                    $commission,
                    $netProfit,
                    $trx->metode_pembayaran,
                    $trx->denda,
                    $trx->denda_kerusakan,
                    $trx->denda_payment_method ?? '-',
                    $trx->catatan_kerusakan ?? '-',
                    $trx->status,
                    $trx->completed_at ? $trx->completed_at->format('d/m/Y H:i') : '-',
                    $trx->created_at->format('d/m/Y H:i')
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'mutasi_transaksi.csv', $headers);
    }

    public function render()
    {
        $query = Rental::with(['units', 'affiliator', 'commissions'])
            ->when($this->search, function ($q) {
            $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
        })
            ->when($this->filterStatus, function ($q) {
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