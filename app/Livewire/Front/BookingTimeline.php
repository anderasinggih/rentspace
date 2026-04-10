<?php

namespace App\Livewire\Front;

use App\Models\Unit;
use Livewire\Component;

class BookingTimeline extends Component
{
    public function render()
    {
        $startDate = \Carbon\Carbon::today();
        $totalDays = 30; // Extended to 30 Days as requested
        $endDate = $startDate->copy()->addDays($totalDays - 1)->endOfDay();
        
        $dates = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $dates[] = $startDate->copy()->addDays($i);
        }

        $units = \App\Models\Unit::where('is_active', true)->with(['rentals' => function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['paid', 'pending', 'completed'])
              ->where('waktu_mulai', '<=', $endDate)
              ->where('waktu_selesai', '>=', $startDate);
        }])->get();

        return view('livewire.front.booking-timeline', [
            'units' => $units,
            'dates' => $dates,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDays' => $totalDays
        ])->layout('layouts.app');
    }
}
