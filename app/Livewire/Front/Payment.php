<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;

class Payment extends Component
{
    public $rental;
    public $metode_pembayaran = 'qris';

    public function mount($booking_code)
    {
        $this->rental = Rental::with('units')
            ->where('booking_code', $booking_code)
            ->firstOrFail();
    }

    public function finish()
    {
        $this->rental->update([
            'metode_pembayaran' => $this->metode_pembayaran
        ]);

        return redirect()->route('public.success', $this->rental->booking_code);
    }

    public function render()
    {
        return view('livewire.front.payment')->layout('layouts.app');
    }
}
