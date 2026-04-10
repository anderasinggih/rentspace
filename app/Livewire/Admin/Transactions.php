<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use Livewire\Component;

class Transactions extends Component
{
    public function markAsPaid($id)
    {
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
        }
    }

    public function cancel($id)
    {
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'cancelled']);
        }
    }

    public function complete($id)
    {
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'completed']);
        }
    }

    public function deleteRow($id)
    {
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
            'transactions' => Rental::with('unit')->latest()->get()
        ])->layout('layouts.admin');
    }
}
