<?php

namespace App\Livewire\Front;

use App\Models\Rental;
use Livewire\Component;

class Payment extends Component
{
    public $rental;

    public function mount($id)
    {
        $this->rental = Rental::with('unit')->findOrFail($id);
    }

    public function finish()
    {
        // This button just signals that the user has simulated transferring the money.
        // The admin will verify it on the Dashboard Tracking Mutation using the Unique Code.
        // It brings the user back to the timeline, and the rent schedule will immediately be seen as yellow/pending.
        return redirect()->route('public.timeline');
    }

    public function render()
    {
        return view('livewire.front.payment')->layout('layouts.app');
    }
}
