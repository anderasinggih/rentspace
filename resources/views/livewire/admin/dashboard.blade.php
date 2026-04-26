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

    <!-- 2. Historical Section (Stockbit Style - Full Width) -->
    <div class="mb-4 px-1">
        <div class="flex items-center justify-between gap-1 w-full overflow-x-auto hide-scrollbar no-scrollbar" style="-ms-overflow-style: none; scrollbar-width: none;">
            @php
                $presets = [
                    ['val' => '7', 'label' => '7D'],
                    ['val' => '30', 'label' => '1M'],
                    ['val' => '90', 'label' => '3M'],
                    ['val' => 'ytd', 'label' => 'YTD'],
                    ['val' => 'all', 'label' => 'ALL'],
                ];
            @endphp
            @foreach($presets as $p)
                <button 
                    wire:click="$set('preset', '{{ $p['val'] }}')"
                    class="flex-1 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === $p['val'] ? 'bg-emerald-500 text-black shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-white/5 text-stock-label hover:bg-white/10' }}">
                    {{ $p['label'] }}
                </button>
            @endforeach
            <button 
                wire:click="$set('preset', 'custom')"
                class="px-4 h-7 rounded text-[10px] font-bold transition-all shrink-0 {{ $preset === 'custom' ? 'bg-amber-500 text-black' : 'bg-white/5 text-stock-label hover:bg-white/10' }}">
                C
            </button>
        </div>
    </div>

    <!-- 2.1 Custom Date Picker (Show only if preset is custom) -->
    @if($preset === 'custom')
        <div class="mb-6 grid grid-cols-2 gap-3 liquid-glass p-3 rounded-xl glass-highlight animate-in fade-in slide-in-from-top-1">
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">Start Date</label>
                <input type="date" wire:model.live="startDate" class="bg-white/5 border-white/10 rounded h-8 text-[11px] text-white focus:ring-emerald-500 px-2 outline-none">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[8px] font-bold text-stock-label uppercase px-1">End Date</label>
                <input type="date" wire:model.live="endDate" class="bg-white/5 border-white/10 rounded h-8 text-[11px] text-white focus:ring-emerald-500 px-2 outline-none">
            </div>
        </div>
    @endif

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
        <div class="grid grid-cols-3 bg-white/[0.02] p-3 divide-x divide-white/5 font-sans">
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Total Order</span>
                <span class="text-xs font-bold text-white tracking-tight leading-none">{{ $periodRentals }} <span class="text-[8px] text-stock-label">TRX</span></span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">AOV Rerata</span>
                <span class="text-xs font-bold text-white tracking-tight leading-none">Rp{{ number_format($avgOrderValue / 1000, 1) }}k</span>
            </div>
            <div class="flex flex-col items-center justify-center gap-1 text-center">
                <span class="text-[8px] font-bold text-stock-label uppercase leading-none">Durasi Sewa</span>
                <span class="text-xs font-bold text-white tracking-tight leading-none">{{ round($avgDuration, 1) }} Jam</span>
            </div>
        </div>
    </div>

    <!-- 4. Interactive Terminals (The Twins) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Revenue Terminal -->
        <div class="liquid-glass rounded-2xl p-4 glass-highlight relative overflow-hidden h-[340px]">
            <div class="absolute top-4 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-2">Net Income Analysis</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs font-semibold {{ $gainNetRevenue >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Rp</span>
                        <span id="chart-revenue-nominal" class="text-3xl font-semibold text-white leading-none">0k</span>
                    </div>
                    <div class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $gainNetRevenue >= 0 ? 'bg-stock-up/10 text-stock-up' : 'bg-stock-down/10 text-stock-down' }}">
                        {{ $gainNetRevenue >= 0 ? '▲' : '▼' }} {{ abs($gainNetRevenue) }}%
                    </div>
                </div>
                <p id="chart-revenue-date" class="text-[9px] font-semibold text-stock-label mt-3 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[220px]">
                <div id="revenueChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>

        <!-- Transactions Terminal -->
        <div class="liquid-glass rounded-2xl p-4 glass-highlight relative overflow-hidden h-[340px]">
            <div class="absolute top-4 left-1/2 -translate-x-1/2 text-center z-10 w-full pointer-events-none">
                <h3 class="text-[10px] font-semibold text-stock-label uppercase mb-2">Order Traffic Pattern</h3>
                <div class="flex items-baseline justify-center gap-2">
                    <div class="flex items-baseline gap-1">
                        <span id="chart-trx-nominal" class="text-3xl font-semibold text-white leading-none">0</span>
                        <span class="text-xs font-semibold {{ $gainRentals >= 0 ? 'text-stock-up' : 'text-stock-down' }} opacity-50">Trx</span>
                    </div>
                    <div class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $gainRentals >= 0 ? 'bg-stock-up/10 text-stock-up' : 'bg-stock-down/10 text-stock-down' }}">
                        {{ $gainRentals >= 0 ? '▲' : '▼' }} {{ abs($gainRentals) }}%
                    </div>
                </div>
                <p id="chart-trx-date" class="text-[9px] font-semibold text-stock-label mt-3 opacity-0 transition-opacity">---</p>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-[220px]">
                <div id="transactionsChart" class="w-full h-full" wire:ignore></div>
            </div>
        </div>
    </div>

    <!-- 5. Secondary Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-1 liquid-glass rounded-2xl p-5 glass-highlight flex flex-col h-[300px]">
             <h3 class="text-[10px] font-semibold text-stock-label mb-3 uppercase leading-none">Payment Methods</h3>
             <div class="flex-1 flex items-center justify-center">
                 <div id="paymentDonutChart" class="w-full h-full" wire:ignore></div>
             </div>
        </div>
        
        <div class="lg:col-span-2 liquid-glass rounded-2xl overflow-hidden glass-highlight h-[300px]">
            <div class="p-3 border-b border-white/5 bg-white/[0.02] text-[10px] font-semibold text-white opacity-60 uppercase">
                Top Performing Units</div>
            <div class="overflow-y-auto h-[255px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5 uppercase sticky top-0 bg-[#16161a] z-10">
                        <tr>
                            <th class="px-4 py-2">Unit</th>
                            <th class="px-4 py-2 text-center">Rented</th>
                            <th class="px-4 py-2 text-right">Net Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-white/[0.03] transition-colors">
                                <td class="px-4 py-3 font-semibold text-white uppercase">{{ $tu->unit ? $tu->unit->seri : '---' }}</td>
                                <td class="px-4 py-3 text-center text-white/50">{{ $tu->rent_count }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-stock-up">Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Monitor & Tenants Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight shadow-xl">
            <div class="px-5 py-3.5 border-b border-white/5 bg-primary/5 flex items-center justify-between uppercase">
                <span class="text-[11px] font-semibold text-primary">Sewa Aktif Monitor</span>
            </div>
            <div class="overflow-x-auto max-h-[300px]">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead class="bg-white/[0.01] text-[9px] font-semibold text-stock-label uppercase sticky top-0 bg-[#16161a] z-10">
                        <tr><th class="px-6 py-3">Unit</th><th class="px-6 py-3">Penyewa</th><th class="px-6 py-3 text-right">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($activeRentals as $rental)
                            <tr class="hover:bg-white/[0.04] transition-all">
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($rental->units as $u)
                                            <span class="px-2 py-0.5 rounded bg-white/5 text-[10px] font-semibold text-white border border-white/10 uppercase">{{ $u->seri }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white uppercase">{{ $rental->nama }}</td>
                                <td class="px-6 py-4 text-right"><span class="text-stock-up font-semibold text-xs uppercase">Active</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-12 text-center text-white/10 text-[10px] font-semibold uppercase">Empty</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="liquid-glass rounded-2xl overflow-hidden glass-highlight h-full">
            <div class="p-3 border-b border-white/5 bg-white/[0.02] text-[10px] font-semibold text-white opacity-60 uppercase">
                Top Active Tenants</div>
            <div class="overflow-y-auto max-h-[300px]">
                <table class="w-full text-left font-sans text-[11px]">
                    <thead class="text-[9px] font-semibold text-stock-label border-b border-white/5 uppercase sticky top-0 bg-[#16161a] z-10">
                        <tr><th class="px-4 py-2">Tenant</th><th class="px-4 py-2 text-center">Frek</th><th class="px-4 py-2 text-right">Spent</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-white/[0.03] transition-colors">
                                <td class="px-4 py-3"><div class="font-semibold text-white uppercase">{{ $tenant->nama }}</div><div class="text-[8px] text-stock-label mt-0.5">{{ $tenant->no_wa }}</div></td>
                                <td class="px-4 py-3 text-center text-white/40">{{ $tenant->total_rentals }}x</td>
                                <td class="px-4 py-3 text-right font-semibold text-white">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
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
            
            elRevVal.innerText = (latRev / 1000).toLocaleString() + 'k';
            elTrxVal.innerText = latTrx.toLocaleString();

            const gainRev = @json($gainNetRevenue);
            const gainTrx = @json($gainRentals);
            const revColor = (gainRev >= 0) ? '#10b981' : '#ef4444';
            const trxColor = (gainTrx >= 0) ? '#10b981' : '#ef4444';

            const currentYear = new Date().getFullYear();
            const fmtCategories = categories.map(cat => cat.includes(currentYear) ? cat : cat + ' ' + currentYear);

            // -- Chart Helper Config --
            const baseConfig = (seriesData, color, nominalEl, dateEl, isTrx = false) => ({
                series: [{ name: isTrx ? 'Order' : 'Bersih', data: seriesData }],
                chart: {
                    type: 'area', height: '100%', fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, sparkline: { enabled: true },
                    events: {
                        mouseMove: function (ev, ctx, config) {
                            if (config.dataPointIndex !== -1 && ctx.w.globals) {
                                try {
                                    const v = ctx.w.globals.series[0][config.dataPointIndex];
                                    const l = fmtCategories[config.dataPointIndex];
                                    if (v !== undefined && nominalEl) {
                                        nominalEl.innerText = isTrx ? v.toLocaleString() : (v/1000).toLocaleString() + 'k';
                                        if (dateEl) { dateEl.innerText = l || '---'; dateEl.style.opacity = '1'; }
                                    }
                                } catch (e) { }
                            }
                        },
                        mouseLeave: function () {
                            if (nominalEl) nominalEl.innerText = isTrx ? latTrx.toLocaleString() : (latRev/1000).toLocaleString() + 'k';
                            if (dateEl) dateEl.style.opacity = '0';
                        }
                    }
                },
                grid: { 
                    show: true, 
                    borderColor: 'rgba(255,255,255,0.03)', 
                    strokeDashArray: 2, 
                    position: 'back', 
                    xaxis: { lines: { show: false } }, // SEMBUNYIKAN: Garis vertikal biar lega
                    yaxis: { lines: { show: true } } 
                },
                colors: [color],
                stroke: { width: 3, curve: 'smooth' },
                fill: { type: 'gradient', gradient: { shade: 'dark', type: "vertical", shadeIntensity: 0.5, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                markers: { size: 0, strokeColors: color, strokeWidth: 1, hover: { size: 2.5 } },
                tooltip: { enabled: true, shared: false, intersect: false, marker: { show: false }, x: { show: false }, y: { show: false } },
                xaxis: { categories: fmtCategories, crosshairs: { show: true, width: 1, position: 'back', stroke: { color: 'rgba(255,255,255,0.1)', width: 1, dashArray: 4 } }, tooltip: { enabled: false } },
                yaxis: { 
                    tickAmount: 4, // BATASI: Cuma 4 garis horizontal
                    tooltip: { enabled: false } 
                }
            });

            const rv = new ApexCharts(document.querySelector("#revenueChart"), baseConfig(netData, revColor, elRevVal, elRevDate));
            const tr = new ApexCharts(document.querySelector("#transactionsChart"), baseConfig(trxData, trxColor, elTrxVal, elTrxDate, true));
            
            rv.render();
            tr.render();

            let dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                series: @json($paymentCounts),
                chart: { type: 'donut', height: '100%', toolbar: { show: false } },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#ffffff'],
                labels: @json($paymentLabels),
                dataLabels: { enabled: true, formatter: (val, opts) => opts.w.globals.labels[opts.seriesIndex], style: { fontSize: '9px', fontWeight: 600, colors: ['#fff'] } },
                legend: { show: false },
                plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: true, fontSize: '10px', color: '#666', offsetY: -5 }, value: { show: true, fontSize: '14px', color: '#fff', offsetY: 5, fontWeight: 700 } } } } },
                stroke: { width: 1, colors: ['#000'] },
                tooltip: { enabled: false }
            });
            dn.render();

            let latestRev = latRev;
            let latestTrx = latTrx;

            // -- ELASTIC RESET: Snap back to latest values on release/leave --
            const snapBack = () => {
                if (elRevVal) elRevVal.innerText = (latestRev / 1000).toLocaleString() + 'k';
                if (elRevDate) elRevDate.style.opacity = '0';
                if (elTrxVal) elTrxVal.innerText = latestTrx.toLocaleString();
                if (elTrxDate) elTrxDate.style.opacity = '0';
            };

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateSeries([{ name: 'Bersih', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'Order', data: x.transactions }]);
                
                latestRev = x.netRevenue.length > 0 ? x.netRevenue[x.netRevenue.length - 1] : 0;
                latestTrx = x.transactions.length > 0 ? x.transactions[x.transactions.length - 1] : 0;
                
                snapBack();
            });

            // Re-apply events for both twins to ensure hold-and-release feel
            [rv, tr].forEach(chart => {
                chart.updateOptions({
                    chart: {
                        events: {
                            mouseLeave: snapBack,
                            touchEnd: snapBack // Proteksi buat Mobile: Lepas jari = Reset
                        }
                    }
                });
            });
        };
        initCharts();
    }
</script>
@endscript