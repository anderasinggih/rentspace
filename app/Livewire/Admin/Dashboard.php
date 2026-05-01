<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $preset = 'mth';
    public $startDate;
    public $endDate;
    public $dateRangeLabel;
    public $heatmapYear;
    public $availableYears = [];
    public $reportMonth;
    public $reportYear;

    public function mount()
    {
        if (!in_array(auth()->user()->role, ['admin', 'viewer', 'staff'])) {
            abort(403);
        }

        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->heatmapYear = (int)date('Y');
        $this->updateDateRangeLabel();
        
        $minDate = Rental::min('created_at');
        $minYear = $minDate ? (int)date('Y', strtotime($minDate)) : (int)date('Y');
        $maxYear = (int)date('Y');
        for ($y = $maxYear; $y >= $minYear; $y--) {
            $this->availableYears[] = $y;
        }
        if (!in_array($this->heatmapYear, $this->availableYears)) {
            $this->availableYears[] = $this->heatmapYear;
        }

        $this->reportMonth = (int)date('m');
        $this->reportYear = (int)date('Y');
    }

    public function selectPreset($p)
    {
        $this->preset = $p;
        if ($this->preset !== 'custom') {
            if ($this->preset === 'all') {
                $firstRental = Rental::min('created_at');
                $this->startDate = $firstRental ? Carbon::parse($firstRental)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            } elseif ($this->preset === 'ytd') {
                $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            } elseif ($this->preset === 'mth') {
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            } elseif ($this->preset === '30') {
                $this->startDate = Carbon::now()->subMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            } elseif ($this->preset === '90') {
                $this->startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            } else {
                // Handle 7
                $this->startDate = Carbon::now()->subDays((int)$this->preset - 1)->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
            }
            $this->updateDateRangeLabel();
            $this->updateCharts();
        }
    }

    public function updatedPreset()
    {
        $this->selectPreset($this->preset);
    }

    public function setHeatmapYear($year)
    {
        $this->heatmapYear = $year;
        $this->updateCharts();
    }

    public function updateDateRangeLabel()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diff = $start->diffInDays($end);

        if ($this->preset === 'all') {
            $this->dateRangeLabel = "All Time Data Experience";
        } elseif ($diff <= 7) {
            $this->dateRangeLabel = $start->format('d M Y') . ' - ' . $end->format('d M Y');
        } elseif ($diff <= 31 && $start->month === $end->month) {
            $this->dateRangeLabel = $start->format('F Y');
        } else {
            $this->dateRangeLabel = $start->format('M Y') . ' - ' . $end->format('M Y');
        }
    }

    public function updatedStartDate() { $this->preset = 'custom'; $this->updateDateRangeLabel(); $this->updateCharts(); }
    public function updatedEndDate() { $this->preset = 'custom'; $this->updateDateRangeLabel(); $this->updateCharts(); }
    public function updatedHeatmapYear() { $this->updateCharts(); }

    public function updateCharts()
    {
        $data = $this->getChartData();
        $this->dispatch('chartDataUpdated', $data);
    }

    private function getChartData()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $diffDays = $start->diffInDays($end);
        $isMonthly = $diffDays > 90;

        // Current Period
        $current = $this->fetchSeries($start, $end, $isMonthly);
        
        // Previous Period (Parallel)
        $periodDays = max(1, $diffDays + 1);
        $prevStart = $start->copy()->subDays($periodDays)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prev = $this->fetchSeries($prevStart, $prevEnd, $isMonthly, count($current['categories']));

        return [
            'categories' => $current['categories'],
            'netRevenue' => $current['netRevenue'],
            'transactions' => $current['transactions'],
            'prevNetRevenue' => $prev['netRevenue'],
            'prevTransactions' => $prev['transactions'],
            'heatmap' => $this->getHeatmapData()
        ];
    }

    private function fetchSeries($start, $end, $isMonthly, $expectedCount = null)
    {
        $chartCategories = [];
        $netRevenueSeries = [];
        $trxSeries = [];
        $cumulativeNet = 0;
        $cumulativeTrx = 0;

        if ($isMonthly) {
            $data = Rental::selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as val, SUM(grand_total) as revenue, COUNT(id) as trx_count')
                ->whereIn('status', ['paid', 'renting', 'completed'])
                ->whereBetween('paid_at', [$start, $end])
                ->groupBy('val')->orderBy('val')->get()->keyBy('val');

            $commissions = \App\Models\AffiliateCommission::whereHas('rental')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as val, SUM(amount) as total_commission')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('val')->get()->keyBy('val');

            $cursor = $start->copy()->startOfMonth();
            $iterLimit = $expectedCount ?? 1000; // Reasonable safety
            $count = 0;
            
            // Loop until we reach end of month of $end
            $limit = $end->copy()->endOfMonth();
            while ($cursor <= $limit && $count < $iterLimit) {
                $format = $cursor->format('Y-m');
                $chartCategories[] = $cursor->format('M Y');
                
                $rev = isset($data[$format]) ? (int) $data[$format]->revenue : 0;
                $comm = isset($commissions[$format]) ? (int) $commissions[$format]->total_commission : 0;
                $trx = isset($data[$format]) ? (int) $data[$format]->trx_count : 0;

                $cumulativeNet += ($rev - $comm);
                $cumulativeTrx += $trx;

                $netRevenueSeries[] = $cumulativeNet;
                $trxSeries[] = $cumulativeTrx;
                
                $cursor->addMonth();
                $count++;
            }
        } else {
            $data = Rental::selectRaw('DATE(paid_at) as val, SUM(grand_total) as revenue, COUNT(id) as trx_count')
                ->whereIn('status', ['paid', 'renting', 'completed'])
                ->whereBetween('paid_at', [$start, $end])
                ->groupBy('val')->orderBy('val')->get()->keyBy('val');

            $commissions = \App\Models\AffiliateCommission::whereHas('rental')
                ->selectRaw('DATE(created_at) as val, SUM(amount) as total_commission')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('val')->get()->keyBy('val');

            $diff = $start->diffInDays($end);
            $cursor = $start->copy();
            for ($i = 0; $i <= $diff; $i++) {
                $format = $cursor->format('Y-m-d');
                $chartCategories[] = $cursor->format('d M');
                
                $rev = isset($data[$format]) ? (int) $data[$format]->revenue : 0;
                $comm = isset($commissions[$format]) ? (int) $commissions[$format]->total_commission : 0;
                $trx = isset($data[$format]) ? (int) $data[$format]->trx_count : 0;

                $cumulativeNet += ($rev - $comm);
                $cumulativeTrx += $trx;

                $netRevenueSeries[] = $cumulativeNet;
                $trxSeries[] = $cumulativeTrx;
                
                $cursor->addDay();
            }
        }

        return [
            'categories' => $chartCategories,
            'netRevenue' => $netRevenueSeries,
            'transactions' => $trxSeries
        ];
    }

    private function getHeatmapData()
    {
        // Select activity by specifically chosen year
        $year = $this->heatmapYear ?? date('Y');
        $start = Carbon::create($year, 1, 1)->startOfWeek(Carbon::SUNDAY);
        $end = Carbon::create($year, 12, 31)->endOfWeek(Carbon::SATURDAY);
        
        $rowLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $heatmap = [];
        foreach($rowLabels as $l) {
            $heatmap[] = ['name' => $l, 'data' => []];
        }

        $rentals = Rental::whereIn('status', ['paid', 'renting', 'completed'])
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(id) as cnt')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $cursor = $start->copy();
        while ($cursor <= $end) {
            $dateStr = $cursor->format('Y-m-d');
            $val = isset($rentals[$dateStr]) ? $rentals[$dateStr]->cnt : 0;
            $rowIdx = $cursor->dayOfWeek; // 0=Sun, 1=Mon...6=Sat
            
            $xKey = $cursor->copy()->startOfWeek(Carbon::SUNDAY)->format('Y-W');

            $heatmap[$rowIdx]['data'][] = [
                'x' => $xKey,
                'y' => (int)$val,
                'd' => $cursor->format('d M Y')
            ];
            
            $cursor->addDay();
        }

        return $heatmap;
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
        $periodRevenue = Rental::whereIn('status', ['paid', 'renting', 'completed'])
                            ->whereBetween('paid_at', [$start, $end])->sum('grand_total');
        $periodDiscounts = Rental::whereBetween('created_at', [$start, $end])->sum('potongan_diskon');
        $unrealizedRevenue = Rental::whereIn('status', ['pending', 'paid'])
                                ->where('waktu_selesai', '>', $end)
                                ->where('created_at', '<=', $end)
                                ->sum('grand_total');
        
        // Affiliate Metrics
        $periodCommissions = \App\Models\AffiliateCommission::whereHas('rental')->whereBetween('created_at', [$start, $end])->sum('amount');
        $periodNetRevenue = $periodRevenue - $periodCommissions;

        $todayRevenue = Rental::whereIn('status', ['paid', 'renting', 'completed'])
                            ->whereDate('paid_at', Carbon::today())->sum('grand_total');
        $yesterdayRevenue = Rental::whereIn('status', ['paid', 'renting', 'completed'])
                            ->whereDate('paid_at', Carbon::yesterday())->sum('grand_total');
        $gainTodayRevenue = $yesterdayRevenue > 0 ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1) : null;
        
        $todayRentals = Rental::whereDate('created_at', Carbon::today())->count();

        // Previous Period Metrics (for gain delta)
        $prevRentals = Rental::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevRevenue = Rental::whereIn('status', ['paid', 'renting', 'completed'])
                            ->whereBetween('paid_at', [$prevStart, $prevEnd])->sum('grand_total');
        $prevCommissions = \App\Models\AffiliateCommission::whereBetween('created_at', [$prevStart, $prevEnd])->sum('amount');
        $prevNetRevenue = $prevRevenue - $prevCommissions;
        $prevDiscounts = Rental::whereBetween('created_at', [$prevStart, $prevEnd])->sum('potongan_diskon');
        $prevPendingRevenue = Rental::where('status', 'pending')->where('created_at', '<=', $prevEnd)->sum('grand_total');
        $prevUnrealizedRevenue = Rental::whereIn('status', ['pending', 'paid'])
                                ->where('waktu_selesai', '>', $prevEnd)
                                ->where('created_at', '<=', $prevEnd)
                                ->sum('grand_total');
        $prevPendingRentals = Rental::where('status', 'pending')->where('created_at', '<=', $prevEnd)->count();

        $gainRentals = $prevRentals > 0 ? round((($periodRentals - $prevRentals) / $prevRentals) * 100, 1) : null;
        $gainRevenue = $prevRevenue > 0 ? round((($periodRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null;
        $gainAbsRevenue = $periodRevenue - $prevRevenue;
        $gainNetRevenue = $prevNetRevenue > 0 ? round((($periodNetRevenue - $prevNetRevenue) / $prevNetRevenue) * 100, 1) : null;
        $gainDiscounts = $prevDiscounts > 0 ? round((($periodDiscounts - $prevDiscounts) / $prevDiscounts) * 100, 1) : null;
        $gainTodayRentals = Rental::whereDate('created_at', Carbon::yesterday())->count() > 0 
            ? round((($todayRentals - Rental::whereDate('created_at', Carbon::yesterday())->count()) / Rental::whereDate('created_at', Carbon::yesterday())->count()) * 100, 1) 
            : null;
        $gainPendingRevenue = $prevPendingRevenue > 0 ? round((($pendingRevenue - $prevPendingRevenue) / $prevPendingRevenue) * 100, 1) : null;
        $gainUnrealizedRevenue = $prevUnrealizedRevenue > 0 ? round((($unrealizedRevenue - $prevUnrealizedRevenue) / $prevUnrealizedRevenue) * 100, 1) : null;
        $gainPendingRentals = $prevPendingRentals > 0 ? round((($pendingRentals - $prevPendingRentals) / $prevPendingRentals) * 100, 1) : null;

        // Leaderboards scoped by date to reflect trends
        $topTenants = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_rentals, SUM(grand_total) as total_spent')
            ->whereIn('status', ['paid', 'renting', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
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
                $q->whereIn('status', ['paid', 'renting', 'completed'])
                  ->whereBetween('paid_at', [$start, $end]);
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
        $paymentSplit = Rental::whereIn('status', ['paid', 'renting', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('metode_pembayaran, COUNT(id) as cnt')
            ->groupBy('metode_pembayaran')
            ->get();
        $paymentLabels = $paymentSplit->pluck('metode_pembayaran')->map(fn($v) => strtoupper($v ?? 'QRIS'))->values()->toArray();
        $paymentCounts = $paymentSplit->pluck('cnt')->values()->toArray();

        $chartInfo = $this->getChartData();
        $chartCategories = $chartInfo['categories'];
        $chartNetRevenue = $chartInfo['netRevenue'];
        $chartTransactions = $chartInfo['transactions'];
        $prevNetRevenue = $chartInfo['prevNetRevenue'];
        $prevTransactions = $chartInfo['prevTransactions'];
        $heatmapData = $chartInfo['heatmap'];

        $activeRentals = Rental::with(['units' => function($q) { $q->withTrashed(); }])
            ->where('status', 'renting')
            ->get();

        $todayRealizedRentals = Rental::with(['units' => function($q) { $q->withTrashed(); }])
            ->whereIn('status', ['paid', 'renting', 'completed'])
            ->whereDate('paid_at', Carbon::today())
            ->orderByDesc('paid_at')
            ->get();

        // Advanced Analysis
        $avgOrderValue = $periodRentals > 0 ? $periodRevenue / $periodRentals : 0;
        $profitEfficiency = $periodRevenue > 0 ? ($periodNetRevenue / $periodRevenue) * 100 : 0;
        $unrealizedRevenue = Rental::whereIn('status', ['pending', 'paid'])
                            ->where(fn($q) => $q->where('waktu_selesai', '>', now()))
                            ->sum('grand_total');
        
        // Avg Duration for the period
        $avgDuration = Rental::whereBetween('paid_at', [$start, $end])
            ->whereIn('status', ['paid', 'renting', 'completed'])
            ->get()
            ->avg(function($r) {
                return abs(Carbon::parse($r->waktu_selesai)->diffInHours(Carbon::parse($r->waktu_mulai)));
            }) ?? 0;

        return view('livewire.admin.dashboard', compact(
            'totalUnits', 'activeUnits', 'pendingRentals', 'pendingRevenue',
            'periodRentals', 'periodRevenue', 'periodDiscounts', 'todayRevenue', 'todayRentals',
            'periodCommissions', 'periodNetRevenue',
            'gainRentals', 'gainRevenue', 'gainAbsRevenue', 'gainNetRevenue', 'gainDiscounts', 'gainTodayRevenue',
            'gainPendingRevenue', 'gainUnrealizedRevenue', 'gainTodayRentals', 'gainPendingRentals',
            'activeRentals', 'todayRealizedRentals', 'topTenants', 'topUnits', 'topAffiliates',
            'chartCategories', 'chartNetRevenue', 'chartTransactions', 'heatmapData',
            'prevNetRevenue', 'prevTransactions',
            'paymentLabels', 'paymentCounts',
            'avgOrderValue', 'profitEfficiency', 'avgDuration', 'unrealizedRevenue'
        ))->layout('layouts.admin');
    }

    public function generateYearlyReport()
    {
        return redirect()->route('admin.report.yearly', [
            'year' => $this->heatmapYear ?? date('Y')
        ]);
    }

    public function generateMonthlyReport()
    {
        return redirect()->route('admin.report.monthly', [
            'month' => $this->reportMonth ?? date('m'),
            'year' => $this->reportYear ?? date('Y')
        ]);
    }

    private function fetchAnalyticReportData($start, $end)
    {
        $rentalsQuery = Rental::whereBetween('created_at', [$start, $end]); // Keep created_at for general volume
        $paidRentalsQuery = Rental::whereBetween('paid_at', [$start, $end])->whereIn('status', ['paid', 'renting', 'completed']);

        $revenue = $paidRentalsQuery->sum('grand_total');
        $commissions = \App\Models\AffiliateCommission::whereHas('rental')
            ->whereBetween('created_at', [$start, $end])->sum('amount');
        $trxCount = $paidRentalsQuery->count();
        $discounts = $rentalsQuery->sum('potongan_diskon');

        // Payment Breakdown
        $payments = $paidRentalsQuery->selectRaw('metode_pembayaran, COUNT(id) as cnt, SUM(grand_total) as total')
            ->groupBy('metode_pembayaran')->get();

        // Top Units
        $topUnits = \App\Models\RentalItem::query()
            ->whereHas('rental', function($q) use ($start, $end) {
                $q->whereIn('status', ['paid', 'renting', 'completed'])
                  ->whereBetween('paid_at', [$start, $end]);
            })
            ->with(['unit' => function($q) { $q->withTrashed(); }])
            ->get()->groupBy('unit_id')->map(fn($g) => [
                'name' => $g->first()->unit?->seri ?? 'N/A',
                'count' => $g->count(),
                'revenue' => $g->sum('price_snapshot')
            ])->sortByDesc('revenue')->take(5)->values()->toArray();

        // Top Tenants
        $topTenants = $paidRentalsQuery->selectRaw('nama, COUNT(id) as trx, SUM(grand_total) as spent')
            ->groupBy('nama')->orderByDesc('spent')->limit(5)->get()->toArray();

        // Monthly/Daily breakdown for table depending on range
        $isYearly = $start->diffInMonths($end) > 1;
        $breakdown = [];
        if ($isYearly) {
            $monthlyData = $paidRentalsQuery->selectRaw('DATE_FORMAT(paid_at, "%m") as grp, SUM(grand_total) as rev, COUNT(id) as trx')
                ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');
            for ($m = 1; $m <= 12; $m++) {
                $k = str_pad($m, 2, '0', STR_PAD_LEFT);
                $breakdown[] = [
                    'label' => Carbon::create()->month($m)->format('M'),
                    'rev' => (int)($monthlyData[$k]->rev ?? 0),
                    'trx' => (int)($monthlyData[$k]->trx ?? 0)
                ];
            }
        } else {
            $dailyData = $paidRentalsQuery->selectRaw('DATE(paid_at) as grp, SUM(grand_total) as rev, COUNT(id) as trx')
                ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');
            $cursor = $start->copy();
            while ($cursor <= $end) {
                $k = $cursor->format('Y-m-d');
                $breakdown[] = [
                    'label' => $cursor->format('d/m'),
                    'rev' => (int)($dailyData[$k]->rev ?? 0),
                    'trx' => (int)($dailyData[$k]->trx ?? 0)
                ];
                $cursor->addDay();
            }
        }

        return [
            'total_revenue' => (int)$revenue,
            'total_net' => (int)($revenue - $commissions),
            'total_commission' => (int)$commissions,
            'total_trx' => $trxCount,
            'total_discounts' => (int)$discounts,
            'payments' => $payments->toArray(),
            'top_units' => $topUnits,
            'top_tenants' => $topTenants,
            'breakdown' => $breakdown,
            'avg_ticket' => $trxCount > 0 ? $revenue / $trxCount : 0,
            'generated_at' => now()->format('d M Y H:i'),
            'admin_name' => auth()->user()->name
        ];
    }
}
