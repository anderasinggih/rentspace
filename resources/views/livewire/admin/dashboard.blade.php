<div class="relative min-h-screen pb-12 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        body { overflow-x: hidden !important; background-color: #0c0c0e; }
        .stock-card {
            background: #16161a;
            border: 1px solid #232329;
        }
        .text-stock-label { color: #81818a; }
        .text-stock-up { color: #10b981; }
        .text-stock-down { color: #ef4444; }
    </style>

    <!-- Header Section -->
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-bold text-white tracking-tight">Dashboard</h1>
        <div class="flex items-center gap-1">
            <div class="h-1 w-1 rounded-full bg-stock-up animate-pulse"></div>
            <span class="text-[9px] font-bold text-stock-up uppercase tracking-widest">Live</span>
        </div>
    </div>

    <!-- 1. Key Metrics Table-Style (Stockbit Vibe) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-[#232329] border border-[#232329] rounded-lg overflow-hidden mb-6 shadow-xl">
        <!-- Metric 1 -->
        <div class="bg-[#16161a] p-3 transition-colors hover:bg-[#1c1c21]">
            <p class="text-[9px] font-bold text-stock-label uppercase tracking-tighter mb-1">Unit Aktif</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-bold text-white">{{ $activeUnits }}</span>
                <span class="text-[10px] font-medium text-stock-label">/{{ $totalUnits }}</span>
            </div>
        </div>

        <!-- Metric 2 -->
        <div class="bg-[#16161a] p-3 transition-colors hover:bg-[#1c1c21]">
            <p class="text-[9px] font-bold text-stock-label uppercase tracking-tighter mb-1">Pending</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-bold text-white">{{ $pendingRentals }}</span>
                <span class="text-[9px] font-bold text-stock-label">TRX</span>
            </div>
        </div>

        <!-- Metric 3 -->
        <div class="bg-[#16161a] p-3 transition-colors hover:bg-[#1c1c21]">
            <p class="text-[9px] font-bold text-stock-up uppercase tracking-tighter mb-1">Unrealized</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[10px] font-bold text-stock-up/50">Rp</span>
                <span class="text-xl font-bold text-stock-up">{{ number_format($unrealizedRevenue/1000, 1) }}k</span>
            </div>
        </div>

        <!-- Metric 4 -->
        <div class="bg-[#16161a] p-3 transition-colors hover:bg-[#1c1c21]">
            <p class="text-[9px] font-bold text-stock-label uppercase tracking-tighter mb-1">Today Rev</p>
            <span class="text-xl font-bold text-white">Rp{{ number_format($todayRevenue/1000, 0) }}k</span>
        </div>

        <!-- Metric 5 -->
        <div class="bg-[#16161a] p-3 transition-colors hover:bg-[#1c1c21]">
            <p class="text-[9px] font-bold text-stock-label uppercase tracking-tighter mb-1">Sewa Today</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-bold text-white">{{ $todayRentals }}</span>
                <span class="text-[9px] font-bold text-stock-label">units</span>
            </div>
        </div>
    </div>

    <!-- 2. Historical Header & Filters (Clean) -->
    <div class="mb-3 flex items-center justify-between px-1">
        <h2 class="text-[10px] font-bold text-stock-label uppercase tracking-widest leading-none">History</h2>
        
        <div class="relative">
            <select wire:model.live="preset"
                class="appearance-none h-6 bg-transparent pr-4 py-0 text-[11px] font-bold text-white focus:ring-0 outline-none border-none cursor-pointer">
                <option value="7">7D</option>
                <option value="30">30D</option>
                <option value="90">3M</option>
                <option value="all">ALL</option>
                <option value="custom">EDIT</option>
            </select>
        </div>
    </div>

    <!-- 3. Period Performance Grid (Table Density) -->
    <div class="mb-6 stock-card rounded-lg overflow-hidden shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-[#232329] border-b border-[#232329]">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-bold text-stock-label uppercase leading-tight">Net Income</span>
                <span class="text-base font-bold text-white">Rp{{ number_format($periodNetRevenue/1000, 0) }}k</span>
                @if($gainNetRevenue !== null)
                    <div class="text-[10px] font-bold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '+' : '' }}{{ $gainNetRevenue }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-bold text-stock-label uppercase leading-tight">Gross Rev</span>
                <span class="text-base font-bold text-white">Rp{{ number_format($periodRevenue/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-bold text-stock-label uppercase leading-tight">Affiliate Cost</span>
                <span class="text-base font-bold text-stock-down opacity-80">Rp{{ number_format($periodCommissions/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-bold text-stock-label uppercase leading-tight">Eff. Margin</span>
                <span class="text-base font-bold text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>

        <!-- Micro Analytics Bar -->
        <div class="grid grid-cols-2 bg-[#1c1c21] p-2 divide-x divide-[#232329]">
            <div class="flex items-center justify-center gap-2">
                <span class="text-[8px] font-bold text-stock-label uppercase">AOV:</span>
                <span class="text-[10px] font-bold text-white">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="flex items-center justify-center gap-2">
                <span class="text-[8px] font-bold text-stock-label uppercase">Avg Duration:</span>
                <span class="text-[10px] font-bold text-white">{{ round($avgDuration, 1) }}h</span>
            </div>
        </div>
    </div>

    <!-- 4. Charts Block -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="stock-card rounded-lg p-2">
            <h3 class="text-[9px] font-bold text-stock-label uppercase px-2 py-1 mb-2">Revenue Growth</h3>
            <div id="revenueChart" class="w-full h-[200px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="stock-card rounded-lg p-2">
                <h3 class="text-[9px] font-bold text-stock-label uppercase px-2 py-1">Orders</h3>
                <div id="transactionsChart" class="w-full h-[100px]" wire:ignore></div>
            </div>
            <div class="stock-card rounded-lg p-2">
                <h3 class="text-[9px] font-bold text-stock-label uppercase px-2 py-1">Payments</h3>
                <div id="paymentDonutChart" class="w-full h-[100px]" wire:ignore></div>
            </div>
        </div>
    </div>

    <!-- 5. Rank Tables (Pure Stockbit Style) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="stock-card rounded-lg overflow-hidden">
            <div class="px-3 py-2 border-b border-[#232329] flex items-center justify-between bg-[#1c1c21]">
                <span class="text-[9px] font-black text-white uppercase italic tracking-tighter">Inventory Ranking</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[8px] font-bold text-stock-label uppercase border-b border-[#232329]">
                    <tr>
                        <th class="px-3 py-1.5">Asset</th>
                        <th class="px-3 py-1.5 text-center">Hits</th>
                        <th class="px-3 py-1.5 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-[#232329]">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2 font-bold text-white">{{ $tu->unit ? $tu->unit->seri : '---' }}</td>
                            <td class="px-3 py-2 text-center text-stock-label">{{ $tu->rent_count }}x</td>
                            <td class="px-3 py-2 text-right font-black text-stock-up">Rp{{ number_format($tu->revenue/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="stock-card rounded-lg overflow-hidden">
            <div class="px-3 py-2 border-b border-[#232329] flex items-center justify-between bg-[#1c1c21]">
                <span class="text-[9px] font-black text-white uppercase italic tracking-tighter">Whale Watch</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[8px] font-bold text-stock-label uppercase border-b border-[#232329]">
                    <tr>
                        <th class="px-3 py-1.5">Entity</th>
                        <th class="px-3 py-1.5 text-center">Frek</th>
                        <th class="px-3 py-1.5 text-right">Total spent</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-[#232329]">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2">
                                <div class="font-bold text-white">{{ $tenant->nama }}</div>
                                <div class="text-[7px] text-stock-label font-medium uppercase tracking-tighter opacity-50">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-3 py-2 text-center text-stock-label">{{ $tenant->total_rentals }}x</td>
                            <td class="px-3 py-2 text-right font-black text-white">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Monitoring (Strict Terminal Style) -->
    <div class="stock-card rounded-lg overflow-hidden shadow-2xl">
        <div class="px-4 py-2.5 border-b border-[#232329] bg-[#1c1c21] flex items-center justify-between">
            <span class="text-[10px] font-black text-stock-up uppercase italic tracking-widest animate-pulse">Running Transactions</span>
        </div>
        <table class="w-full text-left">
            <thead class="text-[8px] font-extrabold text-stock-label uppercase border-b border-[#232329] bg-white/[0.02]">
                <tr>
                    <th class="px-4 py-2">Asset ID</th>
                    <th class="px-4 py-2">Holder</th>
                    <th class="px-4 py-2 text-right">Timer</th>
                </tr>
            </thead>
            <tbody class="text-[11px] font-medium divide-y divide-[#232329]">
                @forelse($activeRentals as $rental)
                    @php
                        $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                        $diffInHours = now()->diffInHours($end, false);
                        $totalM = abs(now()->diffInMinutes($end));
                        $h = floor($totalM / 60);
                        $m = $totalM % 60;
                        $diffT = ($h > 0 ? $h . 'j' : '') . ($m . 'm');
                    @endphp
                    <tr class="hover:bg-white/[0.03]">
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-0.5">
                                @foreach($rental->units as $u)
                                    <span class="px-1.5 py-0.5 rounded-sm bg-[#232329] text-[9px] font-black text-white border border-white/5">{{ $u->seri }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-black text-white uppercase">{{ $rental->nama }}</div>
                            <div class="text-[7px] text-stock-label font-bold tracking-tighter opacity-50">{{ $rental->booking_code }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($diffInHours < 0)
                                <span class="bg-stock-down/10 text-stock-down text-[9px] px-1.5 py-0.5 rounded font-black uppercase">Expired</span>
                            @else
                                <span class="text-stock-up font-black text-[10px]">{{ $diffT }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-center text-stock-label text-[10px] font-bold uppercase tracking-widest opacity-20 italic underline decoration-dotted">No ongoing exchange.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@script
<script>
    if (typeof ApexCharts !== 'undefined') {
        const initCharts = () => {
            const chartDom = document.querySelector("#revenueChart");
            if (!chartDom) return;

            const c = { txt: '#81818a', brd: '#232329' };
            let rv, tr, dn;

            const opt = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: 'dark' },
                grid: { borderColor: c.brd, strokeDashArray: 0, padding: { left: 0, right: 0 } },
            };

            rv = new ApexCharts(document.querySelector("#revenueChart"), {
                ...opt, series: [{ name: 'Gross', data: @json($chartRevenue) }, { name: 'Net', data: @json($chartNetRevenue) }],
                chart: { ...opt.chart, type: 'area', height: 200 },
                colors: ['#ffffff', '#10b981'],
                xaxis: { categories: @json($chartCategories), labels: { style: { colors: c.txt, fontSize: '9px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false },
                stroke: { width: 1.5, curve: 'straight' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.1, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 100 }, colors: ['#ffffff'],
                plotOptions: { bar: { borderRadius: 0, columnWidth: '40%' } },
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false }, grid: { show: false }
            });
            tr.render();

            dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...opt, series: @json($paymentCounts),
                chart: { ...opt.chart, type: 'donut', height: 100 },
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ffffff'],
                labels: @json($paymentLabels),
                legend: { show: false }, plotOptions: { pie: { donut: { size: '80%' } } }, stroke: { width: 0 }
            });
            dn.render();

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateOptions({ xaxis: { categories: x.categories } });
                rv?.updateSeries([{ name: 'Gross', data: x.revenue }, { name: 'Net', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'Order', data: x.transactions }]);
            });
        };
        initCharts();
    }
</script>
@endscript