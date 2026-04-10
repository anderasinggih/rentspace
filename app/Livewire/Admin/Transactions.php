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

    public function render()
    {
        return view('livewire.admin.transactions', [
            'transactions' => Rental::with('unit')->latest()->get()
        ])->layout('layouts.admin');
    }
}
