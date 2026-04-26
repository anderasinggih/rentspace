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
    <div class="mb-6">
        <h1 class="text-xl font-bold text-foreground tracking-tight">Dashboard</h1>
        <p class="text-[11px] text-muted-foreground mt-0.5 opacity-60 font-medium">Monitoring performa RentSpace.</p>
    </div>

    <!-- 1. Snapshot Real-time (Liquid Glass Style) -->
    <div class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4">
            <!-- Card 1 -->
            <div class="glass-card rounded-xl p-3 shadow-sm md:p-4 transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Unit Aktif</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-foreground leading-none">{{ $activeUnits }}</span>
                    <span class="text-[9px] font-medium text-muted-foreground opacity-40">/{{ $totalUnits }}</span>
                </div>
                <div class="mt-2.5 h-1 w-full bg-muted/30 rounded-full overflow-hidden">
                    <div class="h-full bg-primary" style="width: {{ $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="glass-card rounded-xl p-3 shadow-sm md:p-4 transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Pending</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-amber-600 leading-none">{{ $pendingRentals }}</span>
                    <span class="text-[8px] font-bold bg-amber-500/10 text-amber-600 px-1 rounded">Rp{{ number_format($pendingRevenue/1000, 0) }}k</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="glass-card rounded-xl p-3 shadow-sm md:p-4 transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Hari Ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-emerald-600 leading-none">Rp{{ number_format($todayRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="glass-card rounded-xl p-3 shadow-sm md:p-4 transition-all hover:bg-card/60">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Sewa Hari Ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-blue-600 leading-none">{{ $todayRentals }}</span>
                    <span class="text-[9px] font-medium text-muted-foreground opacity-40">unit</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Naked Period Filter -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-3 px-1">
        <h2 class="text-[10px] font-bold text-muted-foreground uppercase tracking-tight opacity-40 leading-none">Analisis Historis</h2>
        
        <div class="flex items-center gap-2">
            <div class="relative">
                <select wire:model.live="preset"
                    class="appearance-none h-8 w-full sm:w-[150px] bg-transparent pl-0 pr-6 py-0 text-xs font-bold text-foreground focus:ring-0 outline-none border-none cursor-pointer">
                    <option value="7">7 hari terakhir</option>
                    <option value="30">30 hari terakhir</option>
                    <option value="90">3 bulan terakhir</option>
                    <option value="all">Semua waktu</option>
                    <option value="custom">Pilih tanggal</option>
                </select>
                <div class="absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none opacity-40">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>

            @if($preset === 'custom')
                <div class="flex items-center gap-1 border-l border-border/30 pl-2">
                    <input type="date" wire:model.live="startDate" class="h-6 bg-transparent border-none text-[10px] p-0 font-bold outline-none text-foreground">
                    <span class="text-muted-foreground opacity-20">→</span>
                    <input type="date" wire:model.live="endDate" class="h-6 bg-transparent border-none text-[10px] p-0 font-bold outline-none text-foreground">
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Period Performance (Liquid Glass Table) -->
    <div class="mb-6 overflow-hidden rounded-xl border border-border/40 bg-card/10 backdrop-blur-md shadow-sm">
        <!-- Main Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-border/20 border-b border-border/20">
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Volume Sewa</span>
                <span class="text-base md:text-lg font-bold text-foreground leading-tight">{{ $periodRentals }} <span class="text-[9px] font-medium opacity-40">trx</span></span>
                @if($gainRentals !== null)
                    <div class="text-[9px] font-bold {{ $gainRentals >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRentals >= 0 ? '↑' : '↓' }}{{ abs($gainRentals) }}%
                    </div>
                @endif
            </div>
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Omset Kotor</span>
                <span class="text-base md:text-lg font-bold text-foreground leading-tight">Rp{{ number_format($periodRevenue / 1000, 0) }}k</span>
                @if($gainRevenue !== null)
                    <div class="text-[9px] font-bold {{ $gainRevenue >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRevenue >= 0 ? '↑' : '↓' }}{{ abs($gainRevenue) }}%
                    </div>
                @endif
            </div>
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Afiliasi</span>
                <span class="text-base md:text-lg font-bold text-red-500/80 leading-tight">Rp{{ number_format($periodCommissions / 1000, 0) }}k</span>
            </div>
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Diskon</span>
                <span class="text-base md:text-lg font-bold text-foreground/80 leading-tight">Rp{{ number_format($periodDiscounts / 1000, 0) }}k</span>
            </div>
        </div>

        <!-- Advanced Analytics Grid -->
        <div class="grid grid-cols-3 divide-x divide-border/20 bg-muted/20 border-b border-border/20">
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-40">AOV</span>
                <span class="text-[11px] md:text-xs font-bold text-foreground">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-40">Eff. Margin</span>
                <span class="text-[11px] md:text-xs font-bold text-emerald-600">{{ round($profitEfficiency, 1) }}%</span>
            </div>
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-40">Avg Duration</span>
                <span class="text-[11px] md:text-xs font-bold text-foreground">{{ round($avgDuration, 1) }}h</span>
            </div>
        </div>

        <!-- Net Result -->
        <div class="bg-primary/5 p-3 flex items-center justify-between">
            <span class="text-[9px] md:text-[10px] font-bold text-primary/70 uppercase tracking-tight">Net Revenue Estimate</span>
            <span class="text-sm md:text-base font-bold text-primary">Rp{{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- 4. Charts Content -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="glass-card rounded-xl shadow-sm overflow-hidden p-2">
            <div class="px-3 py-2 border-b border-white/5 mb-2 flex items-center justify-between">
                <h3 class="text-[10px] font-bold text-foreground/50 uppercase">Earnings Trend</h3>
            </div>
            <div id="revenueChart" class="w-full h-[240px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <div class="glass-card rounded-xl shadow-sm overflow-hidden p-2">
                <h3 class="text-[10px] font-bold text-foreground/50 uppercase px-3 py-1">Order Frequency</h3>
                <div id="transactionsChart" class="w-full h-[120px]" wire:ignore></div>
            </div>

            <div class="glass-card rounded-xl shadow-sm overflow-hidden p-2 flex items-center">
                <div class="flex-1">
                    <h3 class="text-[10px] font-bold text-foreground/50 uppercase px-3">Payments</h3>
                    <div id="paymentDonutChart" class="w-full h-[120px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Dense Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Top Units -->
        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="p-3 border-b border-border/20 bg-muted/10 font-bold text-[10px] text-muted-foreground uppercase opacity-50 font-black">Inventory Rank</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-bold text-muted-foreground/40 border-b border-border/10">
                    <tr>
                        <th class="px-3 py-1.5">Seri</th>
                        <th class="px-2 py-1.5 text-center">Qty</th>
                        <th class="px-3 py-1.5 text-right">Rev</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-border/10">
                    @foreach($topUnits as $tu)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-3 py-2.5 font-bold text-foreground truncate max-w-[100px]">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                            <td class="px-2 py-2.5 text-center opacity-60">{{ $tu->rent_count }}x</td>
                            <td class="px-3 py-2.5 text-right font-bold text-emerald-600">Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top Tenants -->
        <div class="glass-card rounded-xl shadow-sm overflow-hidden">
            <div class="p-3 border-b border-border/20 bg-muted/10 font-bold text-[10px] text-muted-foreground uppercase opacity-50 font-black">Top Tenants</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-bold text-muted-foreground/40 border-b border-border/10">
                    <tr>
                        <th class="px-3 py-1.5">Nama</th>
                        <th class="px-2 py-1.5 text-center">Sewa</th>
                        <th class="px-3 py-1.5 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-border/10">
                    @foreach($topTenants as $tenant)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-3 py-2.5">
                                <div class="font-bold text-foreground truncate max-w-[110px]">{{ $tenant->nama }}</div>
                                <div class="text-[7px] opacity-40 font-medium">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-2 py-2.5 text-center font-bold opacity-50">{{ $tenant->total_rentals }}x</td>
                            <td class="px-3 py-2.5 text-right font-bold text-primary">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Monitoring Table -->
    <div class="rounded-xl border border-primary/20 bg-card shadow-lg overflow-hidden glass-card">
        <div class="p-4 border-b border-white/5 flex items-center justify-between bg-primary/5">
            <div class="flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full bg-primary animate-ping"></div>
                <h3 class="text-[10px] font-black uppercase text-primary tracking-widest">Live Monitoring</h3>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/40 text-[8px] font-bold text-muted-foreground/30 uppercase tracking-tighter">
                    <tr>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Penyewa</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] font-medium divide-y divide-white/5">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalM = abs(now()->diffInMinutes($end));
                            $h = floor($totalM / 60);
                            $m = $totalM % 60;
                            $diffT = ($h > 0 ? $h . 'j' : '') . ($m . 'm');
                        @endphp
                        <tr class="hover:bg-white/[0.03] transition-all">
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-1.5 py-0.5 rounded-md bg-muted text-[9px] font-black text-foreground/80 border border-border/20 shadow-sm">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-extrabold text-foreground text-xs leading-tight">{{ $rental->nama }}</div>
                                <div class="text-[7px] text-primary/40 font-black tracking-tighter">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($diffInHours < 0)
                                    <span class="text-red-500 font-black uppercase text-[9px] animate-pulse">Telat</span>
                                @elseif($diffInHours < 3)
                                    <span class="text-amber-500 font-black text-[9px]">{{ $diffT }} lagi</span>
                                @else
                                    <span class="text-emerald-500 font-extrabold text-[9px]">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-12 text-center opacity-20 text-[9px] font-black italic uppercase tracking-widest">No active units currently monitored.</td></tr>
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
                return { isD, txt: isD ? '#a1a1aa' : '#71717a', brd: isD ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' };
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
                stroke: { width: 2.5, curve: 'smooth' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.15, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 120 }, colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '25%' } },
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false }, grid: { show: false }
            });
            tr.render();

            dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...opt, series: @json($paymentCounts), labels: @json($paymentLabels),
                chart: { ...opt.chart, type: 'donut', height: 120 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { show: false }, plotOptions: { pie: { donut: { size: '78%' } } }, stroke: { width: 0 }
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