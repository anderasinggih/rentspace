<?php

namespace App\Livewire\Front;

use App\Models\Unit;
use Livewire\Component;

class BookingTimeline extends Component
{
    public $timeframe = 14; // Default to 14 days

    public function render()
    {
        if ($this->timeframe === 'month') {
            $startDate = \Carbon\Carbon::now()->startOfMonth();
            $endDate = \Carbon\Carbon::now()->endOfMonth();
            $totalDays = $startDate->diffInDays($endDate) + 1;
        } else {
            $startDate = \Carbon\Carbon::today();
            $totalDays = (int) $this->timeframe; 
            $endDate = $startDate->copy()->addDays($totalDays - 1)->endOfDay();
        }
        
        $dates = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $dates[] = $startDate->copy()->addDays($i);
        }

        $units = \App\Models\Unit::query()->with('category')->where('is_active', true)->with(['rentals' => function ($q) use ($startDate, $endDate) {
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
