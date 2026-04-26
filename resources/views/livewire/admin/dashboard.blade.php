<div class="relative min-h-screen pb-12 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        body { overflow-x: hidden !important; background-color: #0c0c0e; }
        .liquid-glass {
            background: rgba(22, 22, 26, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .text-stock-label { color: rgba(255, 255, 255, 0.4); }
        .text-stock-up { color: #10b981; }
        .text-stock-down { color: #ef4444; }
    </style>

    <!-- Header Section -->
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-black text-white tracking-tighter italic">DASHBOARD</h1>
        <div class="flex items-center gap-2">
            <div class="h-1.5 w-1.5 rounded-full bg-stock-up animate-ping"></div>
            <span class="text-[10px] font-black text-stock-up uppercase tracking-[0.2em]">Live Data</span>
        </div>
    </div>

    <!-- 1. Key Metrics: Stockbit Density + Liquid Glass -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 lg:gap-3 mb-6">
        <!-- Card 1 -->
        <div class="liquid-glass rounded-xl p-3 shadow-2xl transition-all hover:bg-white/[0.03]">
            <p class="text-[9px] font-black text-stock-label uppercase tracking-tighter mb-1">Asset Active</p>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-white leading-none">{{ $activeUnits }}</span>
                <span class="text-[10px] font-bold text-stock-label">/{{ $totalUnits }}</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="liquid-glass rounded-xl p-3 shadow-2xl transition-all hover:bg-white/[0.03]">
            <p class="text-[9px] font-black text-stock-label uppercase tracking-tighter mb-1">Queue TRX</p>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-white leading-none">{{ $pendingRentals }}</span>
                <span class="text-[9px] font-black text-stock-label px-1 bg-white/5 rounded border border-white/5">ORD</span>
            </div>
        </div>

        <!-- Card 3: Unrealized -->
        <div class="liquid-glass rounded-xl p-3 shadow-2xl border-emerald-500/20 bg-emerald-500/5 transition-all hover:bg-emerald-500/10">
            <p class="text-[9px] font-black text-stock-up uppercase tracking-tighter mb-1">Unrealized</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[10px] font-black text-stock-up/50">Rp</span>
                <span class="text-2xl font-black text-stock-up leading-none">{{ number_format($unrealizedRevenue/1000, 1) }}k</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="liquid-glass rounded-xl p-3 shadow-2xl transition-all hover:bg-white/[0.03]">
            <p class="text-[9px] font-black text-stock-label uppercase tracking-tighter mb-1">Realized 24H</p>
            <span class="text-2xl font-black text-white leading-none">Rp{{ number_format($todayRevenue/1000, 0) }}k</span>
        </div>

        <!-- Card 5 -->
        <div class="liquid-glass rounded-xl p-3 shadow-2xl transition-all hover:bg-white/[0.03]">
            <p class="text-[9px] font-black text-stock-label uppercase tracking-tighter mb-1">Daily Swap</p>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-white leading-none">{{ $todayRentals }}</span>
                <span class="text-[10px] font-bold text-stock-label">UNIT</span>
            </div>
        </div>
    </div>

    <!-- 2. Analysis Header -->
    <div class="mb-3 flex items-center justify-between px-1">
        <h2 class="text-[10px] font-black text-stock-label uppercase tracking-[0.3em] leading-none">Market Analysis</h2>
        
        <div class="relative">
            <select wire:model.live="preset"
                class="appearance-none h-6 bg-transparent pr-4 py-0 text-[11px] font-black text-white focus:ring-0 outline-none border-none cursor-pointer tracking-widest">
                <option value="7">7D</option>
                <option value="30">30D</option>
                <option value="90">3M</option>
                <option value="all">ALL</option>
                <option value="custom">EDIT</option>
            </select>
        </div>
    </div>

    <!-- 3. Period Performance: Table Grid + Liquid Glass -->
    <div class="mb-6 liquid-glass rounded-2xl overflow-hidden shadow-2xl">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-white/5 border-b border-white/5">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-black text-stock-label uppercase tracking-tighter">Net Profit</span>
                <span class="text-xl font-black text-white">Rp{{ number_format($periodNetRevenue/1000, 0) }}k</span>
                @if($gainNetRevenue !== null)
                    <div class="text-[11px] font-black {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '▲' : '▼' }}{{ abs($gainNetRevenue) }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-black text-stock-label uppercase tracking-tighter">Gross REV</span>
                <span class="text-xl font-black text-white">Rp{{ number_format($periodRevenue/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-black text-stock-label uppercase tracking-tighter">COGS Score</span>
                <span class="text-xl font-black text-stock-down opacity-80">Rp{{ number_format($periodCommissions/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-black text-stock-label uppercase tracking-tighter">ROI. Margin</span>
                <span class="text-xl font-black text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>

        <!-- Analysis Stats Bar -->
        <div class="grid grid-cols-2 bg-white/[0.03] p-3 divide-x divide-white/5">
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-black text-stock-label uppercase italic">AOV:</span>
                <span class="text-xs font-black text-white tracking-widest">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-black text-stock-label uppercase italic">DURATION:</span>
                <span class="text-xs font-black text-white tracking-widest">{{ round($avgDuration, 1) }} HOURS</span>
            </div>
        </div>
    </div>

    <!-- 4. Visual Charts -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between mb-4 border-b border-white/5 pb-2">
                <h3 class="text-[10px] font-black text-stock-label uppercase tracking-widest">Earnings Stream</h3>
                <span class="text-[9px] text-white/20 font-mono">256-BIT CRYPTO PROTECTED</span>
            </div>
            <div id="revenueChart" class="w-full h-[220px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="liquid-glass rounded-2xl p-4 shadow-xl">
                <h3 class="text-[10px] font-black text-stock-label uppercase tracking-widest mb-3">TX FREQ</h3>
                <div id="transactionsChart" class="w-full h-[100px]" wire:ignore></div>
            </div>
            <div class="liquid-glass rounded-2xl p-4 shadow-xl flex flex-col">
                <h3 class="text-[10px] font-black text-stock-label uppercase tracking-widest mb-3">REVENUE SPLIT</h3>
                <div class="flex-1 flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[120px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Asset & Whale Ranking -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="px-4 py-3 border-b border-white/5 bg-white/[0.02]">
                <span class="text-[10px] font-black text-white uppercase italic tracking-tighter">Inventory Alpha Ranking</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[9px] font-black text-stock-label uppercase border-b border-white/5">
                    <tr>
                        <th class="px-4 py-2">Asset Code</th>
                        <th class="px-4 py-2 text-center">Hits</th>
                        <th class="px-4 py-2 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] divide-y divide-white/5">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-white/[0.04] transition-all">
                            <td class="px-4 py-3 font-black text-white tracking-widest">{{ $tu->unit ? $tu->unit->seri : '---' }}</td>
                            <td class="px-4 py-3 text-center font-bold text-white/60 font-mono">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-right font-black text-stock-up font-mono">Rp{{ number_format($tu->revenue/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="px-4 py-3 border-b border-white/5 bg-white/[0.02]">
                <span class="text-[10px] font-black text-white uppercase italic tracking-tighter">Whale Holder Analysis</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[9px] font-black text-stock-label uppercase border-b border-white/5">
                    <tr>
                        <th class="px-4 py-2">Holder Identity</th>
                        <th class="px-4 py-2 text-center">Freq</th>
                        <th class="px-4 py-2 text-right">Portfolio</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] divide-y divide-white/5">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-white/[0.04] transition-all">
                            <td class="px-4 py-3">
                                <div class="font-black text-white uppercase tracking-tighter leading-tight">{{ $tenant->nama }}</div>
                                <div class="text-[8px] text-stock-label font-bold uppercase tracking-widest opacity-60">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-white/50 font-black">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-3 text-right font-black text-white font-mono">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Log (Pure Terminal) -->
    <div class="liquid-glass rounded-2xl overflow-hidden shadow-2xl border-indigo-500/20">
        <div class="px-5 py-4 border-b border-white/5 bg-indigo-500/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-2 w-2 rounded-full bg-stock-up animate-ping"></div>
                <span class="text-xs font-black text-white uppercase italic tracking-widest">Running Assets Monitor</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/[0.02] text-[9px] font-black text-stock-label uppercase tracking-widest">
                    <tr>
                        <th class="px-6 py-3">Device Node</th>
                        <th class="px-6 py-3">Current Holder</th>
                        <th class="px-6 py-3 text-right">Countdown</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-bold divide-y divide-white/5">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalM = abs(now()->diffInMinutes($end));
                            $h = floor($totalM / 60);
                            $m = $totalM % 60;
                            $diffT = ($h > 0 ? $h . 'H ' : '') . ($m . 'M');
                        @endphp
                        <tr class="hover:bg-white/[0.04] transition-all">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-2 py-0.5 rounded bg-white/10 text-[10px] font-black text-white border border-white/10">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-black text-white uppercase text-sm leading-tight">{{ $rental->nama }}</div>
                                <div class="text-[8px] text-indigo-400 font-black tracking-widest opacity-80">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($diffInHours < 0)
                                    <span class="bg-stock-down/20 text-stock-down text-[10px] px-2 py-1 rounded-md font-black uppercase shadow-[0_0_15px_rgba(239,68,68,0.2)]">OVERDUE</span>
                                @else
                                    <span class="text-stock-up font-black text-sm tracking-widest shadow-stock-up/20 drop-shadow-sm">{{ $diffT }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-12 text-center text-white/20 text-xs font-black uppercase tracking-[0.5em] italic">ZERO ACTIVE NODES DETECTED</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@script
<script>
    if (typeof ApexCharts !== 'undefined') {
        const initCharts = () => {
            const chartDom = document.querySelector("#revenueChart");
            if (!chartDom) return;

            const c = { txt: 'rgba(255,255,255,0.4)', brd: 'rgba(255,255,255,0.05)' };
            let rv, tr, dn;

            const opt = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: 'dark' },
                grid: { borderColor: c.brd, strokeDashArray: 0, padding: { left: 0, right: 0 } },
            };

            rv = new ApexCharts(document.querySelector("#revenueChart"), {
                ...opt, series: [{ name: 'GROSS', data: @json($chartRevenue) }, { name: 'NET', data: @json($chartNetRevenue) }],
                chart: { ...opt.chart, type: 'area', height: 220 },
                colors: ['#ffffff', '#10b981'],
                xaxis: { categories: @json($chartCategories), labels: { style: { colors: c.txt, fontSize: '9px', fontWeight: 900 } }, axisBorder: { show: false } },
                yaxis: { show: false },
                stroke: { width: 1.5, curve: 'straight' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.1, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'ORD', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 100 }, colors: ['#ffffff'],
                plotOptions: { bar: { borderRadius: 0, columnWidth: '35%' } },
                xaxis: { labels: { show: false }, axisBorder: { show: false } },
                yaxis: { show: false }, grid: { show: false }
            });
            tr.render();

            dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...opt, series: @json($paymentCounts),
                chart: { ...opt.chart, type: 'donut', height: 120 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#ffffff'],
                labels: @json($paymentLabels),
                legend: { show: false }, plotOptions: { pie: { donut: { size: '82%' } } }, stroke: { width: 0 }
            });
            dn.render();

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateOptions({ xaxis: { categories: x.categories } });
                rv?.updateSeries([{ name: 'GROSS', data: x.revenue }, { name: 'NET', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'ORD', data: x.transactions }]);
            });
        };
        initCharts();
    }
</script>
@endscript