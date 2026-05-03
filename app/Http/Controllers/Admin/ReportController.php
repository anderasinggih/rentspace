<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));
        $start = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $data = $this->fetchAnalyticReportData($start, $end);
        $data['title'] = "MONTHLY PERFORMANCE REPORT";
        $data['sub_title'] = $start->format('F Y');

        // Growth
        $prevStart = $start->copy()->subMonth()->startOfMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();
        $prevRev = Rental::whereIn('status', ['paid', 'renting', 'completed'])
            ->whereBetween('created_at', [$prevStart, $prevEnd])->sum('grand_total');
        
        $data['mom_growth'] = $prevRev > 0 ? (($data['total_revenue'] - $prevRev) / $prevRev) * 100 : 0;
        $data['revenue_gain'] = $data['total_revenue'] - $prevRev;
        $data['prev_revenue'] = $prevRev;

        return view('admin.report-printable', $data);
    }

    public function yearly(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $data = $this->fetchAnalyticReportData($start, $end);
        $data['title'] = "ANNUAL PERFORMANCE REPORT " . $year;
        $data['sub_title'] = "Fiscal Year Summary";

        return view('admin.report-printable', $data);
    }

    private function fetchAnalyticReportData($start, $end)
    {
        $rentalsQuery = Rental::whereBetween('created_at', [$start, $end]);
        $paidRentalsQuery = (clone $rentalsQuery)->whereIn('status', ['paid', 'renting', 'completed']);

        $revenue = $paidRentalsQuery->sum('grand_total');
        $commissions = \App\Models\AffiliateCommission::whereHas('rental')
            ->whereBetween('created_at', [$start, $end])->sum('amount');
        $trxCount = $paidRentalsQuery->count();
        $discounts = $rentalsQuery->sum('potongan_diskon');

        $payments = $paidRentalsQuery->selectRaw('metode_pembayaran, COUNT(id) as cnt, SUM(grand_total) as total')
            ->groupBy('metode_pembayaran')->get();

        $topUnits = \App\Models\RentalItem::query()
            ->whereHas('rental', function($q) use ($start, $end) {
                $q->whereIn('status', ['paid', 'renting', 'completed'])
                  ->whereBetween('created_at', [$start, $end]);
            })
            ->with(['unit' => function($q) { $q->withTrashed(); }])
            ->get()->groupBy('unit_id')->map(fn($g) => [
                'name' => $g->first()->unit?->seri ?? 'N/A',
                'count' => $g->count(),
                'revenue' => $g->sum('price_snapshot')
            ])->sortByDesc('revenue')->take(5)->values()->toArray();

        $topTenants = $paidRentalsQuery->selectRaw('nama, COUNT(id) as trx, SUM(grand_total) as spent')
            ->groupBy('nama')->orderByDesc('spent')->limit(5)->get()->map(fn($t) => [
                'nama' => $t->nama,
                'total_rentals' => $t->trx,
                'total_spent' => $t->spent
            ])->toArray();

        $isYearly = $start->diffInMonths($end) > 1;
        $breakdown = [];
        if ($isYearly) {
            $monthlyData = $paidRentalsQuery->selectRaw('DATE_FORMAT(created_at, "%m") as grp, SUM(grand_total) as rev, COUNT(id) as trx')
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
            $dailyData = $paidRentalsQuery->selectRaw('DATE(created_at) as grp, SUM(grand_total) as rev, COUNT(id) as trx')
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
