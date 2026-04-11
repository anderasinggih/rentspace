<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;

class Payment extends Component
{
    public $rental;
    public $metode_pembayaran = 'qris';

    public function mount($id)
    {
        $this->rental = Rental::with('unit')->findOrFail($id);
    }

    public function finish()
    {
        $this->rental->update([
            'metode_pembayaran' => $this->metode_pembayaran
        ]);

        return redirect()->route('public.success', $this->rental->id);
    }

    public function render()
    {
        return view('livewire.front.payment')->layout('layouts.app');
    }
}
