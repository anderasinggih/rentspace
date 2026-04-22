<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;
use Carbon\Carbon;

#[Title('Monitoring Timeline - Admin')]
class Monitoring extends Component
{
    public $timeframe = '14'; 
    public $filterCategoryId = '';
    public $customStartDate;
    public $customEndDate;
    public $selectedRentalId = null;

    public function mount()
    {
        $this->customStartDate = Carbon::today()->format('Y-m-d');
        $this->customEndDate = Carbon::today()->addDays(14)->format('Y-m-d');
    }

    public function selectRental($id)
    {
        $this->selectedRentalId = $id;
        $this->dispatch('open-rental-modal');
    }

    public function closeDetail()
    {
        $this->selectedRentalId = null;
    }

    public function getSelectedRentalProperty()
    {
        if (!$this->selectedRentalId) return null;
        return Rental::with('units')->find($this->selectedRentalId);
    }

    public function render()
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(13);

        if ($this->timeframe === '7') {
            $endDate = $startDate->copy()->addDays(6);
        } elseif ($this->timeframe === '14') {
            $endDate = $startDate->copy()->addDays(13);
        } elseif ($this->timeframe === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($this->timeframe === 'year') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now()->endOfYear();
        } elseif ($this->timeframe === 'all') {
            $firstRental = Rental::min('waktu_mulai');
            $startDate = $firstRental ? Carbon::parse($firstRental)->startOfDay() : Carbon::now()->subMonths(3);
            $endDate = Carbon::now()->addMonths(1)->endOfDay();
        } elseif ($this->timeframe === 'custom' && $this->customStartDate && $this->customEndDate) {
            $startDate = Carbon::parse($this->customStartDate)->startOfDay();
            $endDate = Carbon::parse($this->customEndDate)->endOfDay();
        }

        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
        
        // Limit total days to prevent browser crash (e.g. 2 years max)
        if ($totalDays > 730) {
            $totalDays = 730;
            $endDate = $startDate->copy()->addDays($totalDays - 1);
        }

        $dates = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $dates[] = $startDate->copy()->addDays($i);
        }

        $unitsQuery = Unit::query()->with(['category', 'rentals' => function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['paid', 'pending', 'completed'])
              ->where('waktu_mulai', '<=', $endDate)
              ->where('waktu_selesai', '>=', $startDate);
        }]);

        if ($this->filterCategoryId) {
            $unitsQuery->where('category_id', $this->filterCategoryId);
        }

        $units = $unitsQuery->orderBy('category_id')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        return view('livewire.admin.monitoring', [
            'units' => $units,
            'categories' => $categories,
            'dates' => $dates,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDays' => $totalDays
        ])->layout('layouts.admin');
    }
}
