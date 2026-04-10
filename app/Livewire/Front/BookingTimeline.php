<?php

namespace App\Livewire\Front;

use App\Models\Unit;
use Livewire\Component;

class BookingTimeline extends Component
{
    public function render()
    {
        // Simple 7-day timeline logic: We pass units and their active rentals
        $units = Unit::where('is_active', true)
                     ->with(['rentals' => function($query) {
                         $query->whereIn('status', ['pending', 'paid', 'completed'])
                               ->where('waktu_selesai', '>=', now())
                               ->where('waktu_mulai', '<=', now()->addDays(7));
                     }])
                     ->get();

        return view('livewire.front.booking-timeline', [
            'units' => $units
        ])->layout('layouts.app');
    }
}
