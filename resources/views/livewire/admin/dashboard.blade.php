<div class="relative min-h-screen pb-12 overflow-x-hidden select-none" style="touch-action: pan-y;">
    <style>
        body {
            overflow-x: hidden !important;
            @apply bg-slate-50 dark:bg-background text-foreground;
            user-select: none;
        }

        .liquid-glass {
            @apply backdrop-blur-xl bg-background shadow-[0_8px_30px_rgb(0, 0, 0, 0.04)];
            border: 1px solid #cbd5e1;
        }

        .dark .liquid-glass {
            background: rgba(22, 22, 26, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .glass-highlight {
            @apply border-t border-border/40;
        }

        .dark .glass-highlight {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-stock-label {
            @apply text-muted-foreground/60 uppercase;
        }

        .dark .text-stock-label {
            color: rgba(255, 255, 255, 0.35);
        }

        .text-stock-up {
            color: #10b981;
        }

        .text-stock-down {
            color: #ef4444;
        }

        .apexcharts-tooltip {
            display: none !important;
            visibility: hidden !important;
        }

        .apexcharts-xaxistooltip {
            display: none !important;
            visibility: hidden !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <!-- 1. Snapshot Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 md:gap-3 mb-6">
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Active Units</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-foreground leading-none">{{ $activeUnits }}</span>
                <span class="text-[9px] font-medium text-stock-label">/{{ $totalUnits }}</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Order Queue</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-foreground leading-none">{{ $pendingRentals }}</span>
                <span class="text-[8px] font-semibold text-stock-label bg-white/5 px-1 rounded">Trx</span>
            </div>
        </div>
        <div
            class="liquid-glass glass-highlight rounded-xl p-3 border-amber-500/40 bg-amber-500/5 transition-all hover:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-[8px] md:text-[9px] font-semibold text-amber-600 mb-1 uppercase">Pending Balance</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-amber-600/50">Rp</span>
                <span
                    class="text-xl font-semibold text-amber-600 leading-none">{{ number_format($pendingRevenue / 1000, 0) }}k</span>
            </div>
        </div>
        <div
            class="liquid-glass glass-highlight rounded-xl p-3 border-emerald-500/40 bg-emerald-500/5 transition-all hover:bg-emerald-500/10 dark:border-emerald-500/20">
            <p class="text-[8px] md:text-[9px] font-semibold text-emerald-600 mb-1 uppercase">Unrealized Income</p>
            <div class="flex items-baseline gap-0.5">
                <span class="text-[8px] font-medium text-emerald-600/50">Rp</span>
                <span
                    class="text-xl font-semibold text-emerald-600 leading-none">{{ number_format($unrealizedRevenue / 1000, 1) }}k</span>
            </div>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Realized Today</p>
            <span
                class="text-xl font-semibold text-foreground leading-none">Rp{{ number_format($todayRevenue / 1000, 0) }}k</span>
        </div>
        <div class="liquid-glass glass-highlight rounded-xl p-3 transition-all hover:bg-muted/10">
            <p class="text-[8px] md:text-[9px] font-semibold text-stock-label mb-1 uppercase">Today's Rentals</p>
            <div class="flex items-baseline gap-1">
                <span class="text-xl font-semibold text-foreground leading-none">{{ $todayRentals }}</span>
                <span class="text-[10px] font-medium text-stock-label uppercase">Units</span>
            </div>
        </div>
    </div>

    <!-- 2. Historical Section (Stockbit Style - Full Width) -->
    <div class="mb-4 px-1">
        <div class="flex items-center justify-between gap-1 w-full overflow-x-auto no-scrollbar">
            @php
                $presets = [
                    ['val' => '7',   'label' => '7D'],
                    ['val' => '30',  'label' => '1M'],
                    ['val' => '90',  'label' => '3M'],
                    ['val' => '180', 'label' => '6M'],
                    ['val' => 'ytd', 'label' => 'YTD'],
                    ['val' => 'all', 'label' => 'ALL'],
                ];
            @endphp
            @foreach($presets as $p)
                <button wire:click="$set('preset', '{{ $p['val'] }}')"
                    class="flex-1 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === $p['val'] ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-muted/50 text-muted-foreground hover:bg-muted' }}">
                    {{ $p['label'] }}
                </button>
            @endforeach
            <button wire:click="$set('preset', 'custom')"
                class="px-4 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === 'custom' ? 'bg-amber-500 text-black' : 'bg-muted/50 text-muted-foreground hover:bg-muted' }}">
                C
            </button>
        </div>
    </div>

    <!-- 2.1 Custom Date Picker -->
    @if($preset === 'custom')
        <div class="mb-6 grid grid-cols-2 gap-3 liquid-glass p-3 rounded-xl animate-in fade-in slide-in-from-top-1">
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">Start Date</label>
                <input type="date" wire:model.live="startDate"
                    class="bg-background border border-border rounded h-8 text-[11px] text-foreground focus:ring-primary px-2 outline-none">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">End Date</label>
                <input type="date" wire:model.live="endDate"
                    class="bg-background border border-border rounded h-8 text-[11px] text-foreground focus:ring-primary px-2 outline-none">
            </div>
        </div>
    @endif

    <!-- 3. Performance Summary -->
    <div class="mb-6 liquid-glass rounded-2xl overflow-hidden shadow-sm">
        <div class="grid grid-cols-3 divide-x divide-border border-b border-border">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Net Income</span>
                <span
                    class="text-lg font-semibold text-foreground">Rp{{ number_format($periodNetRevenue / 1000, 0) }}k</span>
                @if($gainNetRevenue !== null)
                    <div class="text-[10px] font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '+' : '' }}{{ $gainNetRevenue }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Affiliate Fee</span>
                <span
                    class="text-lg font-semibold text-stock-down/70">Rp{{ number_format($periodCommissions / 1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5 text-right">
                <span class="text-[9px] font-semibold text-stock-label uppercase">Margin ROI</span>
                <span class="text-lg font-semibold text-stock-up">{{ round($profitEfficiency, 1) }}%</span>
            </div>
        </div>
        <div class="grid grid-cols-3 bg-muted/20 p-3 divide-x divide-border font-sans">
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Total Orders</span>
                <span class="text-xs font-bold text-foreground tracking-tight leading-none">{{ $periodRentals }} <span
                        class="text-[8px] text-stock-label">TRX</span></span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Avg. AOV</span>
                <span
                    class="text-xs font-bold text-foreground tracking-tight leading-none">Rp{{ number_format($avgOrderValue / 1000, 1) }}k</span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Avg. Duration</span>
                <span class="text-xs font-bold text-foreground tracking-tight leading-none">{{ round($avgDuration, 1) }}
                    Hours</span>
            </div>
        </div>
    </div>

    <!-- 4. Interactive Terminals -->
    <div class="flex flex-col gap-4 mb-6">
        <div class="liquid-glass rounded-2xl p-4 relative overflow-hidden h-[320px] md:h-[400px]">
            <div class="absolute top-8 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-2">Net Income Analysis</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-xs font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Rp</span>
                        <span id="chart-revenue-nominal"
                            class="text-3xl font-semibold text-foreground leading-none">0k</span>
                    </div>
                    <div
                        class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $gainNetRevenue >= 0 ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '▲' : '▼' }} {{ abs($gainNetRevenue) }}%
                    </div>
                </div>
                <p id="chart-revenue-date"
                    class="text-[9px] font-semibold text-stock-label mt-4 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[200px] md:h-[280px]">
                <div id="revenueChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl p-4 relative overflow-hidden h-[320px] md:h-[400px]">
            <div class="absolute top-8 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-2">Order Traffic Pattern</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span id="chart-trx-nominal"
                            class="text-3xl font-semibold text-foreground leading-none">0</span>
                        <span
                            class="text-xs font-semibold {{ $gainRentals >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Trx</span>
                    </div>
                    <div
                        class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $gainRentals >= 0 ? 'bg-emerald-500/10 text-stock-up' : 'bg-red-500/10 text-stock-down' }}">
                        {{ $gainRentals >= 0 ? '▲' : '▼' }} {{ abs($gainRentals) }}%
                    </div>
                </div>
                <p id="chart-trx-date"
                    class="text-[9px] font-semibold text-stock-label mt-4 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[200px] md:h-[280px]">
                <div id="transactionsChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl p-5 relative overflow-hidden h-auto">
            {{-- Dynamic Labels (Clear Row to prevent overlap) --}}
            <div class="flex flex-col items-center justify-center h-10 w-full pointer-events-none">
                <p id="hm-dynamic-val" class="text-md font-black text-foreground leading-none mb-2">0 Orders</p>
                <p id="hm-dynamic-date"
                    class="text-[10px] font-bold text-stock-label  opacity-0 transition-opacity whitespace-nowrap ">
                    ---</p>
            </div>

            <div class="flex gap-1 md:gap-4 flex-col md:flex-row mb-4">
                <div class="flex-1 overflow-x-auto no-scrollbar -mx-2 px-2"
                    style="-ms-overflow-style: none; scrollbar-width: none;">
                    <div id="heatmapChart" class="min-w-[900px] h-[160px]" wire:ignore></div>
                </div>

                <div
                    class="flex flex-row md:flex-col gap-1.5 md:gap-2 shrink-0 md:border-l border-border md:pl-4 py-2 border-t md:border-t-0 mt-3 md:mt-0 pt-3 md:pt-0 overflow-x-auto no-scrollbar">
                    @foreach($availableYears as $year)
                        <button wire:click="$set('heatmapYear', {{ $year }})"
                            class="text-[9px] font-black transition-all px-2 md:px-0 py-1 rounded {{ $heatmapYear == $year ? 'text-primary' : 'text-stock-label hover:text-foreground' }}">
                            {{ $year }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-border">
                <h3 class="text-[9px] font-bold text-stock-label tracking-tight uppercase">Yearly Activity Monitor</h3>
                <div class="flex items-center gap-1.5 text-[8px] font-bold text-stock-label uppercase">
                    <span>Less</span>
                    <div class="flex gap-1">
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#ebedf0] dark:bg-zinc-800"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#d1fae5] dark:bg-[#064e3b]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#6ee7b7] dark:bg-[#065f46]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#10b981] dark:bg-[#059669]"></div>
                        <div class="w-2.5 h-2.5 rounded-sm bg-[#047857] dark:bg-[#34d399]"></div>
                    </div>
                    <span>More</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Secondary Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-1 liquid-glass rounded-2xl p-5 flex flex-col h-[260px]">
            <h3 class="text-[10px] font-semibold text-stock-label mb-3 uppercase leading-none">Payment Methods</h3>
            <div class="flex-1 flex items-center justify-center">
                <div id="paymentDonutChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <div class="lg:col-span-2 liquid-glass rounded-2xl overflow-hidden h-[260px]">
            <div
                class="p-3 border-b border-border bg-muted/20 text-[10px] font-semibold text-foreground opacity-60 uppercase">
                Top Performing Units</div>
            <div class="overflow-y-auto h-[215px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead
                        class="text-[9px] font-semibold text-stock-label border-b border-border uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-4 py-2">Unit</th>
                            <th class="px-4 py-2 text-center">Rented</th>
                            <th class="px-4 py-2 text-right">Net Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3 font-semibold text-foreground uppercase">
                                    {{ $tu->unit ? $tu->unit->seri : '---' }}
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ $tu->rent_count }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-stock-up">
                                    Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Monitor & Tenants Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-3.5 border-b border-border bg-primary/5 flex items-center justify-between uppercase">
                <span class="text-[11px] font-semibold text-primary">Live Activity Monitor</span>
            </div>
            <div class="overflow-x-auto max-h-[300px]">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead
                        class="bg-muted/10 text-[9px] font-semibold text-stock-label uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-6 py-3">Unit</th>
                            <th class="px-6 py-3">Tenant</th>
                            <th class="px-6 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($activeRentals as $rental)
                            <tr class="hover:bg-muted/30 transition-all">
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($rental->units as $u)
                                            <span
                                                class="px-1.5 py-0.5 rounded bg-muted text-[8px] md:text-[10px] font-bold text-foreground border border-border uppercase">{{ $u->seri }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-foreground uppercase">
                                    {{ explode(' ', trim($rental->nama))[0] }}
                                </td>
                                <td class="px-6 py-4 text-right"><span
                                        class="text-stock-up font-semibold text-xs uppercase">Active</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"
                                    class="px-6 py-12 text-center text-muted-foreground text-[10px] font-semibold uppercase">
                                    No Active Rentals</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden h-full">
            <div
                class="p-3 border-b border-border bg-muted/20 text-[10px] font-semibold text-foreground opacity-60 uppercase">
                Top Active Tenants</div>
            <div class="overflow-y-auto max-h-[300px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead
                        class="text-[9px] font-semibold text-stock-label border-b border-border uppercase sticky top-0 bg-background z-10">
                        <tr>
                            <th class="px-4 py-2">Tenant</th>
                            <th class="px-4 py-2 text-center">Freq</th>
                            <th class="px-4 py-2 text-right">Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-foreground uppercase">{{ $tenant->nama }}</div>
                                    <div class="text-[8px] text-stock-label mt-0.5">{{ $tenant->no_wa }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ $tenant->total_rentals }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-foreground">
                                    Rp{{ number_format($tenant->total_spent / 1000, 0) }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@script
<script>
    if (typeof ApexCharts !== 'undefined') {
        const initCharts = () => {
            // -- Elements --
            const elRevVal = document.getElementById('chart-revenue-nominal');
            const elRevDate = document.getElementById('chart-revenue-date');
            const elTrxVal = document.getElementById('chart-trx-nominal');
            const elTrxDate = document.getElementById('chart-trx-date');

            // -- Data --
            const netData = @json($chartNetRevenue);
            const trxData = @json($chartTransactions);
            const categories = @json($chartCategories);

            const latRev = netData.length > 0 ? netData[netData.length - 1] : 0;
            const latTrx = trxData.length > 0 ? trxData[trxData.length - 1] : 0;

            if (elRevVal) elRevVal.innerText = (latRev / 1000).toLocaleString() + 'k';
            if (elTrxVal) elTrxVal.innerText = latTrx.toLocaleString();

            const gainRev = @json($gainNetRevenue);
            const gainTrx = @json($gainRentals);
            const revColor = (gainRev >= 0) ? '#10b981' : '#ef4444';
            const trxColor = (gainTrx >= 0) ? '#10b981' : '#ef4444';

            const currentYear = new Date().getFullYear();
            const fmtCategories = categories.map(cat => cat.includes(currentYear) ? cat : cat + ' ' + currentYear);

            // -- Theme Helper --
            const getChartStyles = () => {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    grid: isDark ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.03)',
                    crosshair: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    tooltip: isDark ? 'dark' : 'light',
                    label: isDark ? '#666' : '#999',
                    bg: isDark ? '#0c0c0e' : '#ffffff'
                };
            };

            let styles = getChartStyles();

            // -- Chart Helper Config --
            const baseConfig = (seriesData, color, nominalEl, dateEl, isTrx = false) => ({
                series: [{ name: isTrx ? 'Orders' : 'Net', data: seriesData }],
                chart: {
                    type: 'area', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, sparkline: { enabled: true },
                    events: {
                        mouseMove: function (ev, ctx, config) {
                            if (config.dataPointIndex !== -1 && ctx.w.globals) {
                                try {
                                    const v = ctx.w.globals.series[0][config.dataPointIndex];
                                    const l = fmtCategories[config.dataPointIndex];
                                    if (v !== undefined && nominalEl) {
                                        nominalEl.innerText = isTrx ? v.toLocaleString() : (v / 1000).toLocaleString() + 'k';
                                        if (dateEl) { dateEl.innerText = l || '---'; dateEl.style.opacity = '1'; }
                                    }
                                } catch (e) { }
                            }
                        }
                    }
                },
                grid: {
                    show: true,
                    borderColor: styles.grid,
                    strokeDashArray: 2,
                    position: 'back',
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                colors: [color],
                stroke: { width: 2, curve: 'smooth' },
                fill: { type: 'gradient', gradient: { shade: styles.tooltip, type: "vertical", shadeIntensity: 0.5, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                markers: { size: 0, strokeColors: color, strokeWidth: 1, hover: { size: 2.5 } },
                tooltip: { enabled: true, theme: styles.tooltip, shared: false, intersect: false, marker: { show: false }, x: { show: false }, y: { show: false } },
                xaxis: { categories: fmtCategories, crosshairs: { show: true, width: 1, position: 'back', stroke: { color: styles.crosshair, width: 1, dashArray: 4 } }, tooltip: { enabled: false } },
                yaxis: { tickAmount: 4, tooltip: { enabled: false } }
            });

            const rv = new ApexCharts(document.querySelector("#revenueChart"), baseConfig(netData, revColor, elRevVal, elRevDate));
            const tr = new ApexCharts(document.querySelector("#transactionsChart"), baseConfig(trxData, trxColor, elTrxVal, elTrxDate, true));

            rv.render();
            tr.render();

            let dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                series: @json($paymentCounts),
                chart: { type: 'donut', height: '100%', toolbar: { show: false } },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#18181b'],
                labels: @json($paymentLabels),
                dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.labels[opts.seriesIndex], style: { fontSize: '9px', fontWeight: 600 } },
                legend: { show: false },
                plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: true, fontSize: '10px', color: styles.label, offsetY: -5 }, value: { show: true, fontSize: '14px', color: styles.label === '#666' ? '#fff' : '#000', offsetY: 5, fontWeight: 700 } } } } },
                stroke: { width: 1, colors: [styles.grid] }
            });
            dn.render();

            // -- Heatmap Range Helper --
            const getHeatmapRanges = (isDark) => {
                return isDark ? [
                    { from: 0, to: 0, color: 'rgba(255,255,255,0.06)' },
                    { from: 1, to: 1, color: '#064e3b' },
                    { from: 2, to: 5, color: '#065f46' },
                    { from: 6, to: 9, color: '#059669' },
                    { from: 10, to: 1000, color: '#34d399' }
                ] : [
                    { from: 0, to: 0, color: '#ebedf0' },
                    { from: 1, to: 1, color: '#d1fae5' },
                    { from: 2, to: 5, color: '#6ee7b7' },
                    { from: 6, to: 9, color: '#10b981' },
                    { from: 10, to: 1000, color: '#047857' }
                ];
            };

            let hm = new ApexCharts(document.querySelector("#heatmapChart"), {
                series: @json($heatmapData),
                chart: {
                    type: 'heatmap', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    events: {
                        mouseMove: function (ev, ctx, config) {
                            if (config.seriesIndex !== -1 && config.dataPointIndex !== -1) {
                                const v = ctx.w.globals.initialSeries[config.seriesIndex].data[config.dataPointIndex].y;
                                const d = ctx.w.globals.initialSeries[config.seriesIndex].data[config.dataPointIndex].d;
                                const evV = document.getElementById('hm-dynamic-val');
                                const evD = document.getElementById('hm-dynamic-date');
                                if (evV) evV.innerText = v + ' Orders';
                                if (evD) { evD.innerText = d; evD.style.opacity = '1'; }
                            }
                        },
                        mouseLeave: function () {
                            const evV = document.getElementById('hm-dynamic-val');
                            const evD = document.getElementById('hm-dynamic-date');
                            if (evV) evV.innerText = '0 Orders';
                            if (evD) evD.style.opacity = '0';
                        }
                    }
                },
                dataLabels: { enabled: false },
                colors: ["#10b981"],
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false }, crosshairs: { show: false } },
                yaxis: { labels: { style: { fontSize: '9px', colors: styles.label, fontWeight: 500 }, offsetX: -12 } },
                grid: { show: false, padding: { top: -20, bottom: 0, left: 20, right: 10 } },
                legend: { show: false },
                states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
                plotOptions: {
                    heatmap: {
                        radius: 4,
                        enableShades: false,
                        useFillColorAsStroke: false,
                        colorScale: {
                            ranges: getHeatmapRanges(document.documentElement.classList.contains('dark'))
                        }
                    }
                },
                stroke: { width: 4, colors: [styles.bg] },
                tooltip: {
                    enabled: true, theme: styles.tooltip,
                    custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                        const val = series[seriesIndex][dataPointIndex];
                        const date = w.globals.initialSeries[seriesIndex].data[dataPointIndex].d;
                        return `<div class="px-3 py-2 bg-background border border-border rounded-lg shadow-xl">
                                    <div class="text-[10px] font-bold text-foreground mb-1">${val} Orders</div>
                                    <div class="text-[8px] font-medium text-muted-foreground uppercase tracking-widest">${date}</div>
                                </div>`;
                    }
                }
            });
            hm.render();

            let latestRev = latRev;
            let latestTrx = latTrx;

            const snapBack = () => {
                if (elRevVal) elRevVal.innerText = (latestRev / 1000).toLocaleString() + 'k';
                if (elRevDate) elRevDate.style.opacity = '0';
                if (elTrxVal) elTrxVal.innerText = latestTrx.toLocaleString();
                if (elTrxDate) elTrxDate.style.opacity = '0';
            };

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateSeries([{ name: 'Net', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'Orders', data: x.transactions }]);
                latestRev = x.netRevenue.length > 0 ? x.netRevenue[x.netRevenue.length - 1] : 0;
                latestTrx = x.transactions.length > 0 ? x.transactions[x.transactions.length - 1] : 0;
                hm?.updateSeries(x.heatmap);
                snapBack();
            });

            window.addEventListener('theme-changed', (e) => {
                const s = getChartStyles();
                const isDark = document.documentElement.classList.contains('dark');

                const commonOpt = {
                    grid: { borderColor: s.grid },
                    tooltip: { theme: s.tooltip },
                    xaxis: { crosshairs: { stroke: { color: s.crosshair } } }
                };

                rv.updateOptions(commonOpt);
                tr.updateOptions(commonOpt);

                hm.updateOptions({
                    grid: { borderColor: s.grid },
                    stroke: { width: 4, colors: [s.bg] },
                    tooltip: { theme: s.tooltip },
                    yaxis: { labels: { style: { colors: s.label } } },
                    plotOptions: {
                        heatmap: {
                            colorScale: {
                                ranges: getHeatmapRanges(isDark)
                            }
                        }
                    }
                });
            });

            ['#revenueChart', '#transactionsChart', '#heatmapChart'].forEach(id => {
                const el = document.querySelector(id);
                if (el) {
                    ['mouseup', 'touchend', 'mouseleave'].forEach(evt => {
                        el.addEventListener(evt, snapBack);
                    });
                }
            });
        };
        initCharts();
    }
</script>
@endscript