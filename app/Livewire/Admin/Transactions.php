<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use Livewire\Component;

class Transactions extends Component
{
    public $search = '';
    public $filterStatus = '';

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

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function markAsPaid($id)
    {
        if (auth()->user()->role !== 'admin')
            return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
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
        }
    }

    public function deleteRow($id)
    {
        if (auth()->user()->role !== 'admin')
            return;
        Rental::findOrFail($id)->delete();
    }

    public function openInspect($id)
    {
        $this->inspectTrxId = $id;
        $this->inspectTrx = Rental::with('unit')->find($id);
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
            'nama' => $this->edit_nama,
            'nik' => $this->edit_nik,
            'no_wa' => $this->edit_no_wa,
            'alamat' => $this->edit_alamat,
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
        $transactions = Rental::with('unit')->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=mutasi_transaksi.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID Transaksi', 'Tgl Mulai', 'Tgl Selesai', 'Penyewa', 'WA', 'Unit', 'Subtotal', 'Diskon', 'Denda Telat', 'Denda Kerusakan', 'Grand Total', 'Metode', 'Status']);

            foreach ($transactions as $trx) {
                fputcsv($file, [
                    'INV-' . str_pad($trx->id, 5, '0', STR_PAD_LEFT),
                    $trx->waktu_mulai->format('Y-m-d H:i'),
                    $trx->waktu_selesai->format('Y-m-d H:i'),
                    $trx->nama,
                    $trx->no_wa,
                    $trx->unit->seri ?? '-',
                    $trx->subtotal_harga,
                    $trx->potongan_diskon,
                    $trx->denda,
                    $trx->denda_kerusakan,
                    $trx->grand_total,
                    $trx->metode_pembayaran,
                    $trx->status
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'mutasi_transaksi.csv', $headers);
    }

    public function render()
    {
        $query = Rental::with('unit')
            ->when($this->search, function ($q) {
            $q->where(fn($qq) => $qq->where('nama', 'like', '%' . $this->search . '%')
            ->orWhere('id', 'like', '%' . $this->search . '%')
            ->orWhere('no_wa', 'like', '%' . $this->search . '%'));
        })
            ->when($this->filterStatus, function ($q) {
            $q->where(fn($qq) => $qq->where('status', $this->filterStatus));
        })
            ->latest()
            ->get();

        return view('livewire.admin.transactions', [
            'transactions' => $query
        ])->layout('layouts.admin');
    }
}