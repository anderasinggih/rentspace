<div class="relative min-h-screen pb-12 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        body { overflow-x: hidden !important; background-color: #0c0c0e; color: #fdfdfd; }
        .liquid-glass {
            background: rgba(22, 22, 26, 0.45);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }
        .glass-highlight {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-stock-label { color: rgba(255, 255, 255, 0.35); }
        .text-stock-up { color: #10b981; }
        .text-stock-down { color: #ef4444; }
    </style>

    <!-- Header Section -->
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-white tracking-tight">Dashboard</h1>
        <div class="flex items-center gap-2">
            <div class="h-1 w-1 rounded-full bg-stock-up animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
            <span class="text-[9px] font-semibold text-stock-up tracking-wider italic">Real-time audit</span>
        </div>
    </div>

    <!-- 1. Snapshot Grid (Extreme Density - 6 Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 md:gap-3 mb-6">
        <!-- Card 1: Assets -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 tranition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1">Unit aktif</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $activeUnits }}</span>
                <span class="text-[9px] font-medium text-stock-label">/{{ $totalUnits }}</span>
            </div>
        </div>

        <!-- Card 2: Pending Count -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1">Antrean order</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $pendingRentals }}</span>
                <span class="text-[8px] font-semibold text-stock-label bg-white/5 px-1 rounded">trx</span>
            </div>
        </div>

        <!-- Card 3: Pending Nominal (NEW) -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 border-amber-500/20 bg-amber-500/5 transition-all hover:bg-amber-500/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-amber-600 mb-1">Pending balance</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-amber-600/50">Rp</span>
                <span class="text-xl font-semibold text-amber-600 leading-none">{{ number_format($pendingRevenue/1000, 0) }}k</span>
            </div>
        </div>

        <!-- Card 4: Unrealized -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 border-emerald-500/20 bg-emerald-500/5 transition-all hover:bg-emerald-500/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-emerald-600 mb-1">Unrealized income</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-emerald-600/50">Rp</span>
                <span class="text-xl font-semibold text-emerald-600 leading-none">{{ number_format($unrealizedRevenue/1000, 1) }}k</span>
            </div>
        </div>

        <!-- Card 5: Realized Today -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1">Realized today</p>
            <span class="text-xl font-semibold text-white leading-none">Rp{{ number_format($todayRevenue/1000, 0) }}k</span>
        </div>

        <!-- Card 6: Swap Count -->
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1">Penyewaan hari ini</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $todayRentals }}</span>
                <span class="text-[9px] font-medium text-stock-label">unit</span>
            </div>
        </div>
    </div>

    <!-- 2. Analysis Header -->
    <div class="mb-3 flex items-center justify-between px-1">
        <h2 class="text-[10px] font-semibold text-stock-label uppercase tracking-widest leading-none">Analisis pasar</h2>
        <div class="relative">
            <select wire:model.live="preset"
                class="appearance-none h-6 bg-transparent pr-4 py-0 text-[11px] font-semibold text-white focus:ring-0 outline-none border-none cursor-pointer">
                <option value="7">7 Hari</option>
                <option value="30">30 Hari</option>
                <option value="90">3 Bulan</option>
                <option value="all">Semua</option>
                <option value="custom">Edit</option>
            </select>
        </div>
    </div>

    <!-- 3. Period Performance -->
    <div class="mb-6 liquid-glass rounded-2xl overflow-hidden glass-highlight shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-white/5 border-b border-white/5">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label">Net income</span>
                <span class="text-lg font-semibold text-white">Rp{{ number_format($periodNetRevenue/1000, 0) }}k</span>
                @if($gainNetRevenue !== null)
                    <div class="text-[10px] font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '+' : '' }}{{ $gainNetRevenue }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label">Gross revenue</span>
                <span class="text-lg font-semibold text-white">Rp{{ number_format($periodRevenue/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label">Biaya afiliasi</span>
                <span class="text-lg font-semibold text-stock-down/70">Rp{{ number_format($periodCommissions/1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label">Margin ROI</span>
                <span class="text-lg font-semibold text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>

        <div class="grid grid-cols-2 bg-white/[0.02] p-3 divide-x divide-white/5">
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-semibold text-stock-label">AOV rata-rata</span>
                <span class="text-xs font-semibold text-white">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-semibold text-stock-label">Durasi sewa</span>
                <span class="text-xs font-semibold text-white">{{ round($avgDuration, 1) }} Jam</span>
            </div>
        </div>
    </div>

    <!-- 4. Charts Block -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl p-4 glass-highlight">
            <h3 class="text-[10px] font-semibold text-stock-label tracking-widest mb-4">Tren pendapatan</h3>
            <div id="revenueChart" class="w-full h-[220px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="liquid-glass rounded-2xl p-4 glass-highlight">
                <h3 class="text-[10px] font-semibold text-stock-label tracking-widest mb-3">Order</h3>
                <div id="transactionsChart" class="w-full h-[100px]" wire:ignore></div>
            </div>
            <div class="liquid-glass rounded-2xl p-4 glass-highlight flex flex-col">
                <h3 class="text-[10px] font-semibold text-stock-label tracking-widest mb-3">Gateway</h3>
                <div class="flex-1 flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[120px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Rank Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight">
            <div class="px-4 py-3 border-b border-white/5 bg-white/[0.02]">
                <span class="text-[10px] font-semibold text-white tracking-wider opacity-60">Performa unit</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5">
                    <tr>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2 text-center">Qty</th>
                        <th class="px-4 py-2 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] divide-y divide-white/5">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3 font-semibold text-white truncate max-w-[120px]">{{ $tu->unit ? $tu->unit->seri : '---' }}</td>
                            <td class="px-4 py-3 text-center text-white/50">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-right font-semibold text-stock-up">Rp{{ number_format($tu->revenue/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight">
            <div class="px-4 py-3 border-b border-white/5 bg-white/[0.02]">
                <span class="text-[10px] font-semibold text-white tracking-wider opacity-60">Penyewa setia</span>
            </div>
            <table class="w-full text-left">
                <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5">
                    <tr>
                        <th class="px-4 py-2">Nama</th>
                        <th class="px-4 py-2 text-center">Frek</th>
                        <th class="px-4 py-2 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] divide-y divide-white/5">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-white truncate max-w-[140px] leading-tight">{{ $tenant->nama }}</div>
                                <div class="text-[8px] text-stock-label mt-0.5">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-white/40">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-3 text-right font-semibold text-white">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Monitoring -->
    <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight shadow-xl">
        <div class="px-5 py-3.5 border-b border-white/5 bg-primary/5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full bg-primary animate-pulse"></div>
                <span class="text-[11px] font-semibold text-primary tracking-widest text-xs">Monitoring sewa aktif</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/[0.01] text-[9px] font-semibold text-stock-label tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Unit</th>
                        <th class="px-6 py-3">Penyewa</th>
                        <th class="px-6 py-3 text-right">Selesai</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] divide-y divide-white/5">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalM = abs(now()->diffInMinutes($end));
                            $h = floor($totalM / 60);
                            $m = $totalM % 60;
                            $dt = ($h > 0 ? $h . 'h ' : '') . ($m . 'm');
                        @endphp
                        <tr class="hover:bg-white/[0.04] transition-all">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-[10px] font-semibold text-white border border-white/10">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white text-xs">{{ $rental->nama }}</div>
                                <div class="text-[8px] text-stock-label mt-0.5">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($diffInHours < 0)
                                    <span class="text-red-500 font-semibold text-[10px]">Telat</span>
                                @else
                                    <span class="text-stock-up font-semibold text-xs">{{ $dt }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-12 text-center text-white/10 text-[10px] font-semibold italic">Kosong.</td></tr>
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

            const c = { txt: 'rgba(255,255,255,0.3)', brd: 'rgba(255,255,255,0.05)' };
            let rv, tr, dn;

            const opt = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: 'dark' },
                grid: { borderColor: c.brd, strokeDashArray: 0, padding: { left: 0, right: 0 } },
            };

            rv = new ApexCharts(document.querySelector("#revenueChart"), {
                ...opt, series: [{ name: 'KOTOR', data: @json($chartRevenue) }, { name: 'BERSIH', data: @json($chartNetRevenue) }],
                chart: { ...opt.chart, type: 'area', height: 220 },
                colors: ['#ffffff', '#10b981'],
                xaxis: { categories: @json($chartCategories), labels: { style: { colors: c.txt, fontSize: '9px', fontWeight: 600 } }, axisBorder: { show: false } },
                yaxis: { show: false },
                stroke: { width: 1.5, curve: 'straight' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.1, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'ORD', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 100 }, colors: ['#ffffff'],
                plotOptions: { bar: { borderRadius: 1, columnWidth: '35%' } },
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
                rv?.updateSeries([{ name: 'KOTOR', data: x.revenue }, { name: 'BERSIH', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'ORD', data: x.transactions }]);
            });
        };
        initCharts();
    }
</script>
@endscript