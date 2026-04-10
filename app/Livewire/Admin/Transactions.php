<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination;

    public function markAsPaid($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
        }
    }

    public function cancel($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'cancelled']);
        }
    }

    public $completingTrxId = null;
    public $dendaAmount = 0;
    public $dendaMethod = 'cash';

    public function openDendaModal($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $this->completingTrxId = $id;
        $this->dendaAmount = 0;
        $this->dendaMethod = 'cash';
    }

    public function closeDendaModal()
    {
        $this->completingTrxId = null;
    }

    public function confirmDenda()
    {
        $this->validate([
            'dendaAmount' => 'required|numeric|min:0',
            'dendaMethod' => 'required|in:cash,qris',
        ]);

        if ($this->completingTrxId) {
            $rental = Rental::findOrFail($this->completingTrxId);
            if (in_array($rental->status, ['pending', 'paid'])) {
                $rental->update([
                    'status' => 'completed',
                    'denda' => (int) $this->dendaAmount,
                    'denda_payment_method' => $this->dendaAmount > 0 ? $this->dendaMethod : null
                ]);
            }
        }

        $this->closeDendaModal();
    }

    public function finishWithoutDenda($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update([
                'status' => 'completed',
                'denda' => 0,
                'denda_payment_method' => null
            ]);
        }
    }

    public function deleteRow($id)
    {
        if (auth()->user()->role !== 'admin') return;
        Rental::findOrFail($id)->delete();
    }

    public function exportCsv()
    {
        $transactions = Rental::with('unit')->orderBy('created_at', 'desc')->get();
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=mutasi_transaksi.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID Transaksi', 'Tgl Mulai', 'Tgl Selesai', 'Penyewa', 'WA', 'Unit', 'Subtotal', 'Diskon', 'Grand Total', 'Metode', 'Status']);

            foreach ($transactions as $trx) {
                fputcsv($file, [
                    'INV-'.str_pad($trx->id, 5, '0', STR_PAD_LEFT),
                    $trx->waktu_mulai->format('Y-m-d H:i'),
                    $trx->waktu_selesai->format('Y-m-d H:i'),
                    $trx->nama,
                    $trx->no_wa,
                    $trx->unit->seri ?? '-',
                    $trx->subtotal_harga,
                    $trx->potongan_diskon,
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
        return view('livewire.admin.transactions', [
            'transactions' => Rental::with('unit')->latest()->paginate(10)
        ])->layout('layouts.admin');
    }
}
