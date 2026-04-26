<div class="relative min-h-screen pb-16 overflow-x-hidden" style="touch-action: pan-y;">
    <style>
        /* Anti-horizontal scroll and tight layout */
        body { overflow-x: hidden !important; }
        .glass-card {
            background: rgba(var(--card), 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>

    <!-- Header Section -->
    <div class="mb-6 px-1">
        <h1 class="text-xl font-black text-foreground tracking-tight uppercase">Dashboard</h1>
        <p class="text-[10px] text-muted-foreground mt-0.5 opacity-60 font-bold uppercase tracking-widest">System Intelligence Matrix</p>
    </div>

    <!-- 1. Snapshot Real-time (Symmetrical 3x2 or 2x3 Grid) -->
    <div class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
            <!-- Stats Card 1: Inventory -->
            <div class="glass-card rounded-xl p-3 shadow-sm transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[9px] font-black text-muted-foreground uppercase opacity-40 mb-1 tracking-wider">Unit Aktif</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-black text-foreground leading-none">{{ $activeUnits }}</span>
                    <span class="text-[9px] font-bold text-muted-foreground opacity-30">/{{ $totalUnits }}</span>
                </div>
                <div class="mt-2.5 h-1 w-full bg-muted/30 rounded-full overflow-hidden">
                    <div class="h-full bg-primary shadow-[0_0_8px_rgba(var(--primary),0.5)]" style="width: {{ $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Stats Card 2: Queues -->
            <div class="glass-card rounded-xl p-3 shadow-sm transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[9px] font-black text-muted-foreground uppercase opacity-40 mb-1 tracking-wider">Pending Orders</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-black text-amber-600 leading-none">{{ $pendingRentals }}</span>
                    <span class="text-[8px] font-black bg-amber-500/10 text-amber-600 px-1 py-0.5 rounded uppercase tracking-tighter">Queue</span>
                </div>
            </div>

            <!-- Stats Card 3: Unrealized Income -->
            <div class="glass-card rounded-xl p-3 shadow-sm border-l-2 border-emerald-500/30 bg-emerald-500/[0.03] transition-all hover:bg-emerald-500/[0.07]">
                <p class="text-[8px] md:text-[9px] font-black text-emerald-600 uppercase opacity-50 mb-1 tracking-wider">Unrealized</p>
                <div class="flex items-baseline gap-0.5">
                    <span class="text-[9px] font-bold text-emerald-600/40">Rp</span>
                    <span class="text-xl md:text-2xl font-black text-emerald-600 leading-none">{{ number_format($unrealizedRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
                <p class="text-[7px] font-black text-emerald-600/30 uppercase mt-1 tracking-tighter">Projected Assets</p>
            </div>

            <!-- Stats Card 4: Daily Realized -->
            <div class="glass-card rounded-xl p-3 shadow-sm transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[9px] font-black text-muted-foreground uppercase opacity-40 mb-1 tracking-wider">Today Revenue</p>
                <div class="flex items-baseline gap-0.5">
                    <span class="text-[10px] font-bold text-foreground/30">Rp</span>
                    <span class="text-xl md:text-2xl font-black text-foreground leading-none">{{ number_format($todayRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
            </div>

            <!-- Stats Card 5: Daily Volume -->
            <div class="glass-card rounded-xl p-3 shadow-sm transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[9px] font-black text-muted-foreground uppercase opacity-40 mb-1 tracking-wider">Unit Tersewa</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-black text-blue-600 leading-none">{{ $todayRentals }}</span>
                    <span class="text-[8px] font-black bg-blue-500/10 text-blue-600 px-1 py-0.5 rounded uppercase tracking-tighter">Today</span>
                </div>
            </div>

            <!-- Stats Card 6: Daily AOV (THE SYMMETRY CARD) -->
            <div class="glass-card rounded-xl p-3 shadow-sm transition-all hover:bg-card/60 border-l-2 border-indigo-500/20">
                <p class="text-[8px] md:text-[9px] font-black text-indigo-500 uppercase opacity-40 mb-1 tracking-wider">Avg Order Val</p>
                <div class="flex items-baseline gap-0.5">
                    <span class="text-[9px] font-bold text-indigo-500/40">Rp</span>
                    <span class="text-xl md:text-2xl font-black text-foreground leading-none">{{ number_format($todayAov / 1000, 1, ',', '.') }}k</span>
                </div>
                <p class="text-[7px] font-black text-indigo-500/30 uppercase mt-1 tracking-tighter">Per Transaction</p>
            </div>
        </div>
    </div>

    <!-- 2. Naked Period Filter -->
    <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4 px-1">
        <h2 class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] opacity-30 leading-none">Historical Analytics</h2>
        
        <div class="flex items-center gap-2">
            <div class="relative group">
                <select wire:model.live="preset"
                    class="appearance-none h-8 w-full sm:w-[150px] bg-transparent pl-0 pr-6 py-0 text-[11px] font-black text-foreground uppercase focus:ring-0 outline-none border-none cursor-pointer transition-opacity group-hover:opacity-100 opacity-70">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 3 Months</option>
                    <option value="all">All Time</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div class="absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none opacity-40">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            @if($preset === 'custom')
                <div class="flex items-center gap-1 border-l border-border/30 pl-3">
                    <input type="date" wire:model.live="startDate" class="h-6 bg-transparent border-none text-[10px] p-0 font-black outline-none text-foreground uppercase tracking-tighter">
                    <span class="text-muted-foreground opacity-20">→</span>
                    <input type="date" wire:model.live="endDate" class="h-6 bg-transparent border-none text-[10px] p-0 font-black outline-none text-foreground uppercase tracking-tighter">
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Period Performance (Ultra Dense Glass) -->
    <div class="mb-6 overflow-hidden rounded-2xl border border-border/40 bg-card/10 backdrop-blur-2xl shadow-xl">
        <!-- Main Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-border/20 border-b border-border/20">
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[8px] font-black text-muted-foreground uppercase opacity-40 tracking-wider">Volume</span>
                <span class="text-lg font-black text-foreground leading-tight">{{ $periodRentals }} <span class="text-[10px] opacity-30">TX</span></span>
                @if($gainRentals !== null)
                    <div class="text-[9px] font-black {{ $gainRentals >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRentals >= 0 ? '↑' : '↓' }}{{ abs($gainRentals) }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[8px] font-black text-muted-foreground uppercase opacity-40 tracking-wider">Gross</span>
                <span class="text-lg font-black text-foreground leading-tight">Rp{{ number_format($periodRevenue / 1000, 0) }}k</span>
                @if($gainRevenue !== null)
                    <div class="text-[9px] font-black {{ $gainRevenue >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRevenue >= 0 ? '↑' : '↓' }}{{ abs($gainRevenue) }}%
                    </div>
                @endif
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[8px] font-black text-muted-foreground uppercase opacity-40 tracking-wider">Affiliate</span>
                <span class="text-lg font-black text-red-500/80 leading-tight">Rp{{ number_format($periodCommissions / 1000, 0) }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-0.5">
                <span class="text-[8px] font-black text-muted-foreground uppercase opacity-40 tracking-wider">Discount</span>
                <span class="text-lg font-black text-foreground/80 leading-tight">Rp{{ number_format($periodDiscounts / 1000, 0) }}k</span>
            </div>
        </div>

        <!-- Advanced Analytics Grid -->
        <div class="grid grid-cols-3 divide-x divide-border/20 bg-muted/20 border-b border-border/20">
            <div class="p-3 flex flex-col items-center">
                <span class="text-[7px] font-black text-muted-foreground uppercase opacity-40 tracking-tighter">Avg Order</span>
                <span class="text-xs font-black text-foreground tracking-tighter">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="p-3 flex flex-col items-center">
                <span class="text-[7px] font-black text-muted-foreground uppercase opacity-40 tracking-tighter">Efficiency</span>
                <span class="text-xs font-black text-emerald-600 tracking-tighter">{{ round($profitEfficiency, 1) }}%</span>
            </div>
            <div class="p-3 flex flex-col items-center">
                <span class="text-[7px] font-black text-muted-foreground uppercase opacity-40 tracking-tighter">Duration</span>
                <span class="text-xs font-black text-foreground tracking-tighter">{{ round($avgDuration, 1) }}h</span>
            </div>
        </div>

        <div class="bg-primary/10 p-4 flex items-center justify-between border-t border-primary/20">
            <span class="text-[10px] font-black text-primary/80 uppercase tracking-[0.1em]">Estimated Net Result</span>
            <span class="text-lg font-black text-primary">Rp{{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- 4. Charts Content -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden p-3 border border-white/[0.03]">
            <div class="px-3 py-2 border-b border-white/[0.03] mb-3 flex items-center justify-between">
                <h3 class="text-[10px] font-black text-foreground/40 uppercase tracking-widest">Revenue Momentum</h3>
            </div>
            <div id="revenueChart" class="w-full h-[240px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <div class="glass-card rounded-2xl shadow-sm overflow-hidden p-3 border border-white/[0.03]">
                <h3 class="text-[10px] font-black text-foreground/40 uppercase tracking-widest px-3 py-1">Frequency</h3>
                <div id="transactionsChart" class="w-full h-[120px]" wire:ignore></div>
            </div>

            <div class="glass-card rounded-2xl shadow-sm overflow-hidden p-3 flex items-center border border-white/[0.03]">
                <div class="flex-1">
                    <h3 class="text-[10px] font-black text-foreground/40 uppercase tracking-widest px-3">Gateway Usage</h3>
                    <div id="paymentDonutChart" class="w-full h-[120px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Dense Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-4 mb-6">
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden border border-white/[0.03]">
            <div class="p-4 border-b border-white/[0.03] bg-muted/10 font-black text-[10px] text-muted-foreground uppercase opacity-40 tracking-widest">Inventory Performance</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-black text-muted-foreground/30 border-b border-white/[0.03] uppercase">
                    <tr>
                        <th class="px-4 py-2">Model</th>
                        <th class="px-2 py-2 text-center text-[7px]">Usage</th>
                        <th class="px-4 py-2 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-white/[0.03]">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-4 py-3 font-black text-foreground truncate max-w-[120px]">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                            <td class="px-2 py-3 text-center font-black opacity-30">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-right font-black text-emerald-500">Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="glass-card rounded-2xl shadow-sm overflow-hidden border border-white/[0.03]">
            <div class="p-4 border-b border-white/[0.03] bg-muted/10 font-black text-[10px] text-muted-foreground uppercase opacity-40 tracking-widest">Tenant Highspent</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-black text-muted-foreground/30 border-b border-white/[0.03] uppercase">
                    <tr>
                        <th class="px-4 py-2">Profile</th>
                        <th class="px-2 py-2 text-center text-[7px]">Freq</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-white/[0.03]">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-black text-foreground truncate max-w-[130px]">{{ $tenant->nama }}</div>
                                <div class="text-[7.5px] opacity-30 font-black tracking-tighter">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-2 py-3 text-center font-black opacity-30 tracking-tighter">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-3 text-right font-black text-primary">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Monitoring Table -->
    <div class="rounded-2xl border border-primary/30 bg-card shadow-2xl overflow-hidden glass-card">
        <div class="p-5 border-b border-white/5 flex items-center justify-between bg-primary/[0.04]">
            <div class="flex items-center gap-2.5">
                <div class="h-1.5 w-1.5 rounded-full bg-primary animate-ping opacity-75"></div>
                <h3 class="text-[11px] font-black uppercase text-primary tracking-[0.3em]">Neural Live Assets</h3>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/50 text-[8px] font-black text-muted-foreground/30 uppercase tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Hardware</th>
                        <th class="px-6 py-4">Assignee</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] font-medium divide-y divide-white/[0.03]">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalM = abs(now()->diffInMinutes($end));
                            $h = floor($totalM / 60);
                            $m = $totalM % 60;
                            $diffT = ($h > 0 ? $h . 'j' : '') . ($m . 'm');
                        @endphp
                        <tr class="hover:bg-white/[0.04] transition-all group">
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($rental->units as $u)
                                        <span class="px-2 py-0.5 rounded bg-muted text-[9px] font-black text-foreground/70 border border-white/[0.05] shadow-inner group-hover:border-primary/30 transition-colors uppercase tracking-tighter">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-black text-foreground text-xs leading-none uppercase tracking-tighter">{{ $rental->nama }}</div>
                                <div class="text-[8px] text-primary/40 font-black tracking-[0.05em] mt-1">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($diffInHours < 0)
                                    <span class="text-red-500 font-black uppercase text-[10px] animate-pulse tracking-widest">Critical</span>
                                @elseif($diffInHours < 3)
                                    <span class="text-amber-500 font-black text-[10px] uppercase tracking-widest">{{ $diffT }}</span>
                                @else
                                    <span class="text-emerald-500 font-black text-[10px] uppercase tracking-widest">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-16 text-center opacity-20 text-[10px] font-black italic uppercase tracking-[0.4em]">Zero Active Assets</td></tr>
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

            function getC() {
                const isD = document.documentElement.classList.contains('dark');
                return { isD, txt: isD ? '#a1a1aa' : '#71717a', brd: isD ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.03)' };
            }

            let c = getC();
            let rv, tr, dn;

            const opt = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: c.isD ? 'dark' : 'light' },
                grid: { borderColor: c.brd, strokeDashArray: 4, padding: { left: 0, right: 0 } },
            };

            rv = new ApexCharts(document.querySelector("#revenueChart"), {
                ...opt, series: [{ name: 'Kotor', data: @json($chartRevenue) }, { name: 'Bersih', data: @json($chartNetRevenue) }],
                chart: { ...opt.chart, type: 'area', height: 240 },
                colors: ['#6366f1', '#10b981'],
                xaxis: { categories: @json($chartCategories), labels: { style: { colors: c.txt, fontSize: '9px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: (v) => v >= 1000 ? (v/1000).toFixed(0)+'k' : v, style: { colors: c.txt, fontSize: '9px' } } },
                stroke: { width: 3, curve: 'smooth' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.2, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 120 }, colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '30%' } },
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false }, grid: { show: false }
            });
            tr.render();

            dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...opt, series: @json($paymentCounts), labels: @json($paymentLabels),
                chart: { ...opt.chart, type: 'donut', height: 120 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { show: false }, plotOptions: { pie: { donut: { size: '82%' } } }, stroke: { width: 0 }
            });
            dn.render();

            Livewire.on('chartDataUpdated', (d) => {
                const x = d[0] || d;
                rv?.updateOptions({ xaxis: { categories: x.categories } });
                rv?.updateSeries([{ name: 'Kotor', data: x.revenue }, { name: 'Bersih', data: x.netRevenue }]);
                tr?.updateSeries([{ name: 'Order', data: x.transactions }]);
            });

            const obs = new MutationObserver(() => {
                const nc = getC();
                [rv, tr, dn].forEach(ch => ch?.updateOptions({ theme: { mode: nc.isD ? 'dark' : 'light' }, grid: { borderColor: nc.brd } }));
            });
            obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            document.addEventListener('livewire:navigating', () => { obs.disconnect(); [rv, tr, dn].forEach(x => x?.destroy()); }, { once: true });
        };
        initCharts();
    }
</script>
@endscript