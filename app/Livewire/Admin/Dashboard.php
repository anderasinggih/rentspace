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
        if (!in_array(auth()->user()->role, ['admin', 'viewer', 'staff'])) {
            abort(403);
        }

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

        $commissionDataObj = \App\Models\AffiliateCommission::whereHas('rental')
            ->selectRaw('DATE(created_at) as date_val, SUM(amount) as total_commission')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date_val')
            ->get()
            ->keyBy('date_val');
            
        $chartCategories = [];
        $revenueSeries = [];
        $netRevenueSeries = [];
        $trxSeries = [];

        $diffDays = $start->diffInDays($end);
        if ($diffDays > 90) {
            // Fix: Use MySQL compatible DATE_FORMAT instead of SQLite strftime
            $chartDataObj = Rental::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as val, SUM(grand_total) as revenue, COUNT(id) as trx_count')
                ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('val')
                ->orderBy('val')
                ->get()
                ->keyBy('val');

            $commissionDataObj = \App\Models\AffiliateCommission::whereHas('rental')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as val, SUM(amount) as total_commission')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('val')
                ->get()
                ->keyBy('val');

            $cursor = $start->copy()->startOfMonth();
            while ($cursor <= $end->copy()->endOfMonth()) {
                $format = $cursor->format('Y-m');
                $chartCategories[] = $cursor->format('M Y');
                $rev = isset($chartDataObj[$format]) ? (int) $chartDataObj[$format]->revenue : 0;
                $comm = isset($commissionDataObj[$format]) ? (int) $commissionDataObj[$format]->total_commission : 0;
                $revenueSeries[] = $rev;
                $netRevenueSeries[] = $rev - $comm;
                $trxSeries[] = isset($chartDataObj[$format]) ? (int) $chartDataObj[$format]->trx_count : 0;
                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy();
            for ($i = 0; $i <= $diffDays; $i++) {
                $formattedSQLDate = $cursor->format('Y-m-d');
                $chartCategories[] = $cursor->format('d M');
                $rev = isset($chartDataObj[$formattedSQLDate]) ? (int) $chartDataObj[$formattedSQLDate]->revenue : 0;
                $comm = isset($commissionDataObj[$formattedSQLDate]) ? (int) $commissionDataObj[$formattedSQLDate]->total_commission : 0;
                $revenueSeries[] = $rev;
                $netRevenueSeries[] = $rev - $comm;
                $trxSeries[] = isset($chartDataObj[$formattedSQLDate]) ? (int) $chartDataObj[$formattedSQLDate]->trx_count : 0;
                $cursor->addDay();
            }
        }
        
        return [
            'categories' => $chartCategories, 
            'revenue' => $revenueSeries, 
            'netRevenue' => $netRevenueSeries,
            'transactions' => $trxSeries
        ];
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
        $pendingRevenue = Rental::where('status', 'pending')->sum('grand_total');

        // Period Metrics
        $periodRentals = Rental::whereBetween('created_at', [$start, $end])->count();
        $periodRevenue = Rental::where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
                            ->whereBetween('created_at', [$start, $end])->sum('grand_total');
        $periodDiscounts = Rental::whereBetween('created_at', [$start, $end])->sum('potongan_diskon');
        
        // Affiliate Metrics
        $periodCommissions = \App\Models\AffiliateCommission::whereHas('rental')->whereBetween('created_at', [$start, $end])->sum('amount');
        $periodNetRevenue = $periodRevenue - $periodCommissions;

        $todayRevenue = Rental::where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
                            ->whereDate('created_at', Carbon::today())->sum('grand_total');
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();

        // Previous Period Metrics (for gain delta)
        $prevRentals = Rental::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevRevenue = Rental::where(function($q) { $q->where('status', 'completed')->orWhere('status', 'paid'); })
                            ->whereBetween('created_at', [$prevStart, $prevEnd])->sum('grand_total');
        $prevCommissions = \App\Models\AffiliateCommission::whereBetween('created_at', [$prevStart, $prevEnd])->sum('amount');
        $prevNetRevenue = $prevRevenue - $prevCommissions;

        $gainRentals = $prevRentals > 0 ? round((($periodRentals - $prevRentals) / $prevRentals) * 100, 1) : null;
        $gainRevenue = $prevRevenue > 0 ? round((($periodRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null;
        $gainAbsRevenue = $periodRevenue - $prevRevenue;
        $gainNetRevenue = $prevNetRevenue > 0 ? round((($periodNetRevenue - $prevNetRevenue) / $prevNetRevenue) * 100, 1) : null;

        // Leaderboards scoped by date to reflect trends
        $topTenants = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_rentals, SUM(grand_total) as total_spent')
            ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('nik', 'nama', 'no_wa')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Affiliate Leaderboard
        $topAffiliates = \App\Models\AffiliateCommission::whereHas('rental')
            ->selectRaw('affiliator_id, SUM(amount) as total_commission, COUNT(rental_id) as total_trx')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('affiliator_id')
            ->with('affiliator')
            ->orderByDesc('total_commission')
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
        $chartNetRevenue = $chartInfo['netRevenue'];
        $chartTransactions = $chartInfo['transactions'];

        $activeRentals = Rental::with(['units' => function($q) { $q->withTrashed(); }])
            ->whereIn('status', ['paid', 'pending'])
            ->where(fn($q) => $q->where('waktu_mulai', '<=', now()))
            ->where(fn($q) => $q->where('waktu_selesai', '>=', now()))
            ->get();

        // Advanced Analysis
        $avgOrderValue = $periodRentals > 0 ? $periodRevenue / $periodRentals : 0;
        $profitEfficiency = $periodRevenue > 0 ? ($periodNetRevenue / $periodRevenue) * 100 : 0;
        $unrealizedRevenue = Rental::whereIn('status', ['pending', 'paid'])
                            ->where(fn($q) => $q->where('waktu_selesai', '>', now()))
                            ->sum('grand_total');
        
        // Avg Duration for the period
        $avgDuration = Rental::whereBetween('created_at', [$start, $end])
            ->where(fn($q) => $q->where('status', 'completed')->orWhere('status', 'paid'))
            ->get()
            ->avg(function($r) {
                return abs(Carbon::parse($r->waktu_selesai)->diffInHours(Carbon::parse($r->waktu_mulai)));
            }) ?? 0;

        $todayAov = $todayRentals > 0 ? $todayRevenue / $todayRentals : 0;

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'pendingRentals', 'pendingRevenue',
            'periodRentals', 'periodRevenue', 'periodDiscounts', 'todayRevenue', 'todayRentals',
            'periodCommissions', 'periodNetRevenue',
            'gainRentals', 'gainRevenue', 'gainAbsRevenue', 'gainNetRevenue',
            'activeRentals', 'topTenants', 'topUnits', 'topAffiliates',
            'chartCategories', 'chartRevenue', 'chartNetRevenue', 'chartTransactions',
            'paymentLabels', 'paymentCounts',
            'avgOrderValue', 'profitEfficiency', 'avgDuration', 'unrealizedRevenue',
            'todayAov'
        ))->layout('layouts.admin');
    }
}
