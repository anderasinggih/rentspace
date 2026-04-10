<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $chartRange = '90'; // default matching "3 months" like the react template

    public function updatedChartRange()
    {
        $data = $this->getChartData();
        $this->dispatch('chartDataUpdated', $data);
    }

    private function getChartData()
    {
        $daysValue = (int)$this->chartRange;
        if (!in_array($daysValue, [7, 30, 90])) $daysValue = 90;
        
        $daysAgo = Carbon::now()->subDays($daysValue - 1);
        $chartDataObj = Rental::selectRaw('DATE(created_at) as date_val, SUM(grand_total) as revenue')
            ->where(function($q) { $q->where('status', 'completed'); })
            ->where('created_at', '>=', $daysAgo->startOfDay())
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get()
            ->keyBy('date_val');
            
        $chartCategories = [];
        $chartSeries = [];
        $cursor = $daysAgo->copy();
        for ($i = 0; $i < $daysValue; $i++) {
            $formattedSQLDate = $cursor->format('Y-m-d');
            $chartCategories[] = $cursor->format('d M');
            $chartSeries[] = isset($chartDataObj[$formattedSQLDate]) ? (int) $chartDataObj[$formattedSQLDate]->revenue : 0;
            $cursor->addDay();
        }
        
        return ['categories' => $chartCategories, 'series' => $chartSeries];
    }

    public function render()
    {
        $totalUnits = Unit::withTrashed()->count();
        $activeUnits = Unit::where(function($q) { $q->where('is_active', true); })->count();
        
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();
        $pendingRentals = Rental::where(function($q) { $q->where('status', 'pending'); })->count();
        
        $totalRevenue = Rental::where(function($q) { $q->where('status', 'completed'); })->sum('grand_total');

        $topTenants = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_rentals, SUM(grand_total) as total_spent')
            ->where(function($q) { $q->where('status', 'completed'); })
            ->groupBy('nik', 'nama', 'no_wa')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        $topUnits = Rental::selectRaw('unit_id, COUNT(id) as rent_count, SUM(grand_total) as revenue')
            ->with(['unit' => function($q) { $q->withTrashed(); }])
            ->where(function($q) { $q->where('status', 'completed'); })
            ->groupBy('unit_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $chartInfo = $this->getChartData();
        $chartCategories = $chartInfo['categories'];
        $chartSeries = $chartInfo['series'];

        $activeRentals = Rental::with(['unit' => function($q) { $q->withTrashed(); }])
            ->whereIn('status', ['paid', 'pending'])
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'todayRentals', 'pendingRentals', 
            'totalRevenue', 'activeRentals', 'topTenants', 'topUnits',
            'chartCategories', 'chartSeries'
        ))->layout('layouts.admin');
    }
}
