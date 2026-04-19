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
            ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
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
                ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
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

        // --- Previous period for gain delta ---
        $periodDays = max(1, $start->diffInDays($end) + 1);
        $prevStart = $start->copy()->subDays($periodDays)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();

        // Snapshot Metrics (Global)
        $totalUnits = Unit::withTrashed()->count();
        $activeUnits = Unit::where(fn($q) => $q->where('is_active', true))->count();
        $pendingRentals = Rental::where(fn($q) => $q->where('status', 'pending'))->count();

        // Period Metrics
        $periodRentals = Rental::whereBetween('created_at', [$start, $end])->count();
        $periodRevenue = Rental::where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
                            ->whereBetween('created_at', [$start, $end])->sum('grand_total');
        $periodDiscounts = Rental::whereBetween('created_at', [$start, $end])->sum('potongan_diskon');
        $todayRevenue = Rental::where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
                            ->whereDate('created_at', Carbon::today())->sum('grand_total');
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();

        // Previous Period Metrics (for gain delta)
        $prevRentals = Rental::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevRevenue = Rental::where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
                            ->whereBetween('created_at', [$prevStart, $prevEnd])->sum('grand_total');

        $gainRentals = $prevRentals > 0 ? round((($periodRentals - $prevRentals) / $prevRentals) * 100, 1) : null;
        $gainRevenue = $prevRevenue > 0 ? round((($periodRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null;
        $gainAbsRevenue = $periodRevenue - $prevRevenue;

        // Leaderboards scoped by date to reflect trends
        $topTenants = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_rentals, SUM(grand_total) as total_spent')
            ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('nik', 'nama', 'no_wa')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Unit performance with Rented Hours calculation using RentalItem pivot
        $topUnits = \App\Models\RentalItem::query()
            ->whereHas('rental', function($q) use ($start, $end) {
                $q->where(fn($q2) => $q2->where('status', 'completed')->orWhere('status', 'paid'))
                  ->whereBetween('created_at', [$start, $end]);
            })
            ->with(['unit' => function($q) { $q->withTrashed(); }, 'rental'])
            ->get()
            ->groupBy('unit_id')
            ->map(function($group) {
                $unit = $group->first()->unit;
                $rent_count = $group->count();
                $revenue = $group->sum('price_snapshot');
                $hours = $group->sum(function($item) {
                    $r = $item->rental;
                    return abs(Carbon::parse($r->waktu_selesai)->diffInHours(Carbon::parse($r->waktu_mulai)));
                });
                return (object)[
                    'unit' => $unit,
                    'rent_count' => $rent_count,
                    'revenue' => $revenue,
                    'hours' => $hours
                ];
            })
            ->sortByDesc('revenue')
            ->take(5);

        // Payment Method Breakdown
        $paymentSplit = Rental::where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('metode_pembayaran, COUNT(id) as cnt')
            ->groupBy('metode_pembayaran')
            ->get();
        $paymentLabels = $paymentSplit->pluck('metode_pembayaran')->map(fn($v) => strtoupper($v ?? 'QRIS'))->values()->toArray();
        $paymentCounts = $paymentSplit->pluck('cnt')->values()->toArray();

        $chartInfo = $this->getChartData();
        $chartCategories = $chartInfo['categories'];
        $chartRevenue = $chartInfo['revenue'];
        $chartTransactions = $chartInfo['transactions'];

        $activeRentals = Rental::with(['units' => function($q) { $q->withTrashed(); }])
            ->whereIn('status', ['paid', 'pending'])
            ->where(fn($q) => $q->where('waktu_mulai', '<=', now()))
            ->where(fn($q) => $q->where('waktu_selesai', '>=', now()))
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'pendingRentals',
            'periodRentals', 'periodRevenue', 'periodDiscounts', 'todayRevenue', 'todayRentals',
            'gainRentals', 'gainRevenue', 'gainAbsRevenue',
            'activeRentals', 'topTenants', 'topUnits',
            'chartCategories', 'chartRevenue', 'chartTransactions',
            'paymentLabels', 'paymentCounts'
        ))->layout('layouts.admin');
    }
}
