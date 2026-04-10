<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $preset = '30';
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->subDays(29)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedPreset()
    {
        if ($this->preset !== 'custom') {
            if ($this->preset === 'all') {
                $firstRental = Rental::min('created_at');
                $this->startDate = $firstRental ? Carbon::parse($firstRental)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            } else {
                $this->startDate = Carbon::now()->subDays((int)$this->preset - 1)->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            }
            $this->updateCharts();
        }
    }

    public function updatedStartDate() { $this->preset = 'custom'; $this->updateCharts(); }
    public function updatedEndDate() { $this->preset = 'custom'; $this->updateCharts(); }

    public function updateCharts()
    {
        $data = $this->getChartData();
        $this->dispatch('chartDataUpdated', $data);
    }

    private function getChartData()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        
        $chartDataObj = Rental::selectRaw('DATE(created_at) as date_val, SUM(grand_total) as revenue, COUNT(id) as trx_count')
            ->where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date_val')
            ->orderBy('date_val')
            ->get()
            ->keyBy('date_val');
            
        $chartCategories = [];
        $revenueSeries = [];
        $trxSeries = [];

        // Determine step size if range is huge (avoiding 1000s of columns)
        $diffDays = $start->diffInDays($end);
        if ($diffDays > 90) {
            // Group by Month if > 90 days
            $chartDataObj = Rental::selectRaw('strftime("%Y-%m", created_at) as val, SUM(grand_total) as revenue, COUNT(id) as trx_count')
                ->where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('val')
                ->orderBy('val')
                ->get()
                ->keyBy('val');

            $cursor = $start->copy()->startOfMonth();
            while ($cursor <= $end->copy()->endOfMonth()) {
                $format = $cursor->format('Y-m');
                $chartCategories[] = $cursor->format('M Y');
                $revenueSeries[] = isset($chartDataObj[$format]) ? (int) $chartDataObj[$format]->revenue : 0;
                $trxSeries[] = isset($chartDataObj[$format]) ? (int) $chartDataObj[$format]->trx_count : 0;
                $cursor->addMonth();
            }
        } else {
            // Generate Daily
            $cursor = $start->copy();
            for ($i = 0; $i <= $diffDays; $i++) {
                $formattedSQLDate = $cursor->format('Y-m-d');
                $chartCategories[] = $cursor->format('d M');
                $revenueSeries[] = isset($chartDataObj[$formattedSQLDate]) ? (int) $chartDataObj[$formattedSQLDate]->revenue : 0;
                $trxSeries[] = isset($chartDataObj[$formattedSQLDate]) ? (int) $chartDataObj[$formattedSQLDate]->trx_count : 0;
                $cursor->addDay();
            }
        }
        
        return ['categories' => $chartCategories, 'revenue' => $revenueSeries, 'transactions' => $trxSeries];
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Snapshot Metrics (Global)
        $totalUnits = Unit::withTrashed()->count();
        $activeUnits = Unit::where(function($q) { $q->where('is_active', true); })->count();
        $pendingRentals = Rental::where(function($q) { $q->where('status', 'pending'); })->count();

        // Period Metrics
        $periodRentals = Rental::whereBetween('created_at', [$start, $end])->count();
        $periodRevenue = Rental::where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
                            ->whereBetween('created_at', [$start, $end])->sum('grand_total');
        $periodDiscounts = Rental::whereBetween('created_at', [$start, $end])->sum('potongan_diskon');
        $todayRevenue = Rental::where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
                            ->whereDate('created_at', Carbon::today())->sum('grand_total');
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();

        // Leaderboards scoped by date to reflect trends
        $topTenants = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_rentals, SUM(grand_total) as total_spent')
            ->where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('nik', 'nama', 'no_wa')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        $topUnits = Rental::selectRaw('unit_id, COUNT(id) as rent_count, SUM(grand_total) as revenue')
            ->with(['unit' => function($q) { $q->withTrashed(); }])
            ->where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('unit_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $chartInfo = $this->getChartData();
        $chartCategories = $chartInfo['categories'];
        $chartRevenue = $chartInfo['revenue'];
        $chartTransactions = $chartInfo['transactions'];

        $activeRentals = Rental::with(['unit' => function($q) { $q->withTrashed(); }])
            ->whereIn('status', ['paid', 'pending'])
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'pendingRentals',
            'periodRentals', 'periodRevenue', 'periodDiscounts', 'todayRevenue', 'todayRentals',
            'activeRentals', 'topTenants', 'topUnits',
            'chartCategories', 'chartRevenue', 'chartTransactions'
        ))->layout('layouts.admin');
    }
}
