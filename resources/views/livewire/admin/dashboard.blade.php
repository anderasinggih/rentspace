<div class="relative min-h-screen pb-12 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        body {
            overflow-x: hidden !important;
            background-color: #0c0c0e;
            color: #fdfdfd;
        }

        .liquid-glass {
            background: rgba(22, 22, 26, 0.45);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .glass-highlight {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-stock-label {
            color: rgba(255, 255, 255, 0.35);
        }

        .text-stock-up {
            color: #10b981;
        }

        .text-stock-down {
            color: #ef4444;
        }

        /* Ninja Style: Hide default tooltip while keeping interaction alive */
        .apexcharts-tooltip {
            display: none !important;
            visibility: hidden !important;
        }

        .apexcharts-xaxistooltip {
            display: none !important;
            visibility: hidden !important;
        }
    </style>

    <!-- 1. Snapshot Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 md:gap-3 mb-6">
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Unit Aktif</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $activeUnits }}</span>
                <span class="text-[9px] font-medium text-stock-label">/{{ $totalUnits }}</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Antrean Order</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $pendingRentals }}</span>
                <span class="text-[8px] font-semibold text-stock-label bg-white/5 px-1 rounded">Trx</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 border-amber-500/20 bg-amber-500/5 transition-all hover:bg-amber-500/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-amber-600 mb-1 uppercase">Pending Balance</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-amber-600/50">Rp</span>
                <span class="text-xl font-semibold text-amber-600 leading-none">{{ number_format($pendingRevenue / 1000, 0) }}k</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 border-emerald-500/20 bg-emerald-500/5 transition-all hover:bg-emerald-500/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-emerald-600 mb-1 uppercase">Unrealized Income</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-emerald-600/50">Rp</span>
                <span class="text-xl font-semibold text-emerald-600 leading-none">{{ number_format($unrealizedRevenue / 1000, 1) }}k</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Realized Today</p>
            <span class="text-xl font-semibold text-white leading-none">Rp{{ number_format($todayRevenue / 1000, 0) }}k</span>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-white/[0.02]">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Sewa Hari Ini</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-white leading-none">{{ $todayRentals }}</span>
                <span class="text-[10px] font-medium text-stock-label uppercase">Unit</span>
            </div>
        </div>
    </div>

    <!-- 2. Historical Section -->
    <div class="mb-3 flex items-center justify-between px-1">
        <h2 class="text-[10px] font-semibold text-stock-label uppercase leading-none">Analisis</h2>
        <div class="relative">
            <select wire:model.live="preset"
                class="appearance-none h-6 bg-transparent pr-4 py-0 text-[11px] font-semibold text-white focus:ring-0 outline-none border-none cursor-pointer">
                <option value="7">7 Hari</option>
                <option value="30">30 Hari</option>
                <option value="90">3 Bulan</option>
                <option value="all">Semua</option>
            </select>
        </div>
    </div>

    <!-- 3. Performance Summary -->
    <div class="mb-6 liquid-glass rounded-2xl overflow-hidden glass-highlight shadow-sm">
        <div class="grid grid-cols-3 divide-x divide-white/5 border-b border-white/5">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Net Income</span>
                <span class="text-lg font-semibold text-white">Rp{{ number_format($periodNetRevenue / 1000, 0) }}k</span>
                @if($gainNetRevenue !== null)
                    <div class="text-[10px] font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '+' : '' }}{{ $gainNetRevenue }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Biaya Afiliasi</span>
                <span class="text-lg font-semibold text-stock-down/70">Rp{{ number_format($periodCommissions / 1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5 text-right">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Margin ROI</span>
                <span class="text-lg font-semibold text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>
        <div class="grid grid-cols-2 bg-white/[0.02] p-3 divide-x divide-white/5 font-sans">
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-semibold text-stock-label uppercase">AOV Rerata</span>
                <span class="text-xs font-semibold text-white">Rp{{ number_format($avgOrderValue / 1000, 1) }}k</span>
            </div>
            <div class="flex items-center justify-center gap-3">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Durasi Sewa</span>
                <span class="text-xs font-semibold text-white">{{ round($avgDuration, 1) }} Jam</span>
            </div>
        </div>
    </div>

    <!-- 4. Revenue Curve Block -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl p-4 glass-highlight relative overflow-hidden h-[340px]">
            <!-- Dynamic Nominal Display -->
            <div class="absolute top-4 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 id="chart-nominal-label" class="text-[10px] font-semibold text-stock-label uppercase mb-1">
                    Pendapatan Bersih</h3>
                <div class="flex items-baseline justify-center gap-1">
                    <span class="text-xs font-semibold text-stock-up/50">Rp</span>
                    <span id="chart-nominal-value" class="text-3xl font-semibold text-white leading-none">0k</span>
                </div>
                <p id="chart-nominal-date"
                    class="text-[8px] font-semibold text-stock-label mt-2 opacity-0 transition-opacity">---</p>
            </div>

            <!-- The Chart -->
            <div class="absolute bottom-0 left-0 right-0 h-[220px]">
                <div id="revenueChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="liquid-glass rounded-2xl p-4 glass-highlight flex flex-col">
                <h3 class="text-[10px] font-semibold text-stock-label mb-3 uppercase leading-none">Statistik Order</h3>
                <div class="flex-1 flex items-center justify-center">
                    <div id="transactionsChart" class="w-full h-[160px]" wire:ignore></div>
                </div>
            </div>
            <div class="liquid-glass rounded-2xl p-4 glass-highlight flex flex-col relative overflow-hidden">
                <h3 class="text-[10px] font-semibold text-stock-label mb-3 uppercase leading-none">Metode Bayar</h3>
                <div class="flex-1 flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[180px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Rank Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight">
            <div class="p-3 border-b border-white/5 bg-white/[0.02] text-[10px] font-semibold text-white opacity-60 uppercase">
                Performa Unit</div>
            <table class="w-full text-left font-sans text-[11px]">
                <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5 uppercase">
                    <tr>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2 text-center">Frek</th>
                        <th class="px-4 py-2 text-right">Net Rev</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3 font-semibold text-white uppercase">{{ $tu->unit ? $tu->unit->seri : '---' }}</td>
                            <td class="px-4 py-3 text-center text-white/50">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-right font-semibold text-stock-up">
                                Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight">
            <div class="p-3 border-b border-white/5 bg-white/[0.02] text-[10px] font-semibold text-white opacity-60 uppercase">
                Penyewa Paling Aktif</div>
            <table class="w-full text-left font-sans text-[11px]">
                <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5 uppercase">
                    <tr>
                        <th class="px-4 py-2">Penyewa</th>
                        <th class="px-4 py-2 text-center">Frek</th>
                        <th class="px-4 py-2 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-white/[0.03] transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-white leading-tight uppercase">{{ $tenant->nama }}</div>
                                <div class="text-[8px] text-stock-label mt-0.5">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-white/40">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-3 text-right font-semibold text-white">
                                Rp{{ number_format($tenant->total_spent / 1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Monitor Log -->
    <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight shadow-xl">
        <div class="px-5 py-3.5 border-b border-white/5 bg-primary/5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full bg-primary animate-pulse"></div>
                <span class="text-[11px] font-semibold text-primary uppercase">Sewa Aktif Monitor</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-[11px]">
                <thead class="bg-white/[0.01] text-[9px] font-semibold text-stock-label uppercase">
                    <tr>
                        <th class="px-6 py-3">Unit</th>
                        <th class="px-6 py-3">Penyewa</th>
                        <th class="px-6 py-3 text-right">Countdown</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($activeRentals as $rental)
                        <tr class="hover:bg-white/[0.04] transition-all">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span
                                            class="px-2 py-0.5 rounded bg-white/5 text-[10px] font-semibold text-white border border-white/10 uppercase">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white text-xs uppercase">{{ $rental->nama }}</div>
                                <div class="text-[8px] text-stock-label mt-0.5 uppercase">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-stock-up font-semibold text-xs uppercase">Active</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-white/10 text-[10px] font-semibold uppercase">
                                Kosong.</td>
                        </tr>
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

            const nValue = document.getElementById('chart-nominal-value');
            const nDate = document.getElementById('chart-nominal-date');

            const netData = @json($chartNetRevenue);
            const latestValue = netData.length > 0 ? netData[netData.length - 1] : 0;
            nValue.innerText = (latestValue / 1000).toLocaleString() + 'k';

            const c = { txt: 'rgba(255,255,255,0.2)', brd: 'rgba(255,255,255,0.05)' };

            let rv = new ApexCharts(document.querySelector("#revenueChart"), {
                series: [{ name: 'Bersih', data: netData }],
                chart: {
                    type: 'area', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, sparkline: { enabled: true },
                    events: {
                        mouseMove: function (event, chartContext, config) {
                            if (config.dataPointIndex !== -1 && chartContext && chartContext.w && chartContext.w.globals) {
                                try {
                                    const val = chartContext.w.globals.series[0][config.dataPointIndex];
                                    const label = chartContext.w.globals.categoryLabels[config.dataPointIndex];
                                    if (val !== undefined && nValue) {
                                        nValue.innerText = (val / 1000).toLocaleString() + 'k';
                                        if (nDate) { nDate.innerText = label || '---'; nDate.style.opacity = '1'; }
                                    }
                                } catch (e) { }
                            }
                        },
                        mouseLeave: function () {
                            if (nValue) nValue.innerText = (latestValue / 1000).toLocaleString() + 'k';
                            if (nDate) nDate.style.opacity = '0';
                        }
                    }
                },
                colors: ['#10b981'],
                stroke: { width: 3, curve: 'smooth' },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.15, opacityTo: 0 } },
                markers: { 
                    size: 0,
                    strokeColors: '#10b981',
                    strokeWidth: 2,
                    hover: { size: 4 }
                },
                tooltip: { 
                    enabled: true,
                    shared: false,
                    intersect: false,
                    marker: { show: false },
                    x: { show: false },
                    y: { show: false }
                },
                xaxis: {
                    categories: @json($chartCategories),
                    crosshairs: { 
                        show: true,
                        width: 1,
                        position: 'back',
                        stroke: { color: 'rgba(255,255,255,0.1)', width: 1, dashArray: 4 }
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    tooltip: { enabled: false }
                }
            });
            rv.render();

            let tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { type: 'bar', height: 160, toolbar: { show: false } },
                colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '50%', dataLabels: { position: 'top' } } },
                dataLabels: {
                    enabled: true, offsetY: -18,
                    style: { fontSize: '10px', colors: ["#10b981"], fontWeight: 600 }
                },
                xaxis: { categories: @json($chartCategories), labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false },
                grid: { show: false },
                tooltip: { enabled: false } // Matikan tooltip di chart kedua juga
            });
            tr.render();

            let dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                series: @json($paymentCounts),
                chart: { type: 'donut', height: 200 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#ffffff'],
                labels: @json($paymentLabels),
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        return opts.w.globals.labels[opts.seriesIndex];
                    },
                    style: { fontSize: '9px', fontWeight: 600, colors: ['#fff'] }
                },
                legend: { show: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '10px', color: '#666', offsetY: -5 },
                                value: { show: true, fontSize: '14px', color: '#fff', offsetY: 5, fontWeight: 700 }
                            }
                        }
                    }
                },
                stroke: { width: 1, colors: ['#000'] },
                tooltip: { enabled: false } // Matikan tooltip di donut juga biar konsisten
            });
            dn.render();

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateSeries([{ name: 'Bersih', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'Order', data: x.transactions }]);
            });
        };
        initCharts();
    }
</script>
@endscript