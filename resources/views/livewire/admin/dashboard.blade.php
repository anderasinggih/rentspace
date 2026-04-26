<div class="relative min-h-screen pb-16">
    <!-- Header Section -->
    <div class="mb-6">
        <h1 class="text-xl font-bold text-foreground">Dashboard</h1>
        <p class="text-[11px] text-muted-foreground mt-0.5 opacity-60 font-medium">Monitoring performa RentSpace.</p>
    </div>

    <!-- 1. Snapshot Real-time (Ultra Compact) -->
    <div class="mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4">
            <!-- Card 1 -->
            <div class="rounded-xl border border-border/40 bg-card p-3 shadow-sm md:p-4">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Unit Aktif</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-foreground">{{ $activeUnits }}</span>
                    <span class="text-[9px] font-medium text-muted-foreground opacity-40">/{{ $totalUnits }}</span>
                </div>
                <div class="mt-2 h-1 w-full bg-muted rounded-full overflow-hidden">
                    <div class="h-full bg-primary" style="width: {{ $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="rounded-xl border border-border/40 bg-card p-3 shadow-sm md:p-4">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Pending</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-amber-600">{{ $pendingRentals }}</span>
                    <span class="text-[8px] font-bold bg-amber-500/10 text-amber-600 px-1 rounded">Rp{{ number_format($pendingRevenue/1000, 0) }}k</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="rounded-xl border border-border/40 bg-card p-3 shadow-sm md:p-4">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Hari Ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-emerald-600">Rp{{ number_format($todayRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="rounded-xl border border-border/40 bg-card p-3 shadow-sm md:p-4">
                <p class="text-[8px] md:text-[10px] font-bold text-muted-foreground uppercase opacity-50 mb-1">Sewa Hari Ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl md:text-2xl font-bold text-blue-600">{{ $todayRentals }}</span>
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

    <!-- 3. Period Performance & In-depth Analysis (High Density) -->
    <div class="mb-6 overflow-hidden rounded-xl border border-border/40 bg-card shadow-sm">
        <!-- Main Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y divide-border/40 border-b border-border/40">
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Volume Sewa</span>
                <span class="text-base md:text-lg font-bold text-foreground leading-tight">{{ $periodRentals }} <span class="text-[9px] font-medium opacity-40">trx</span></span>
                @if($gainRentals !== null)
                    <div class="text-[9px] font-bold {{ $gainRentals >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRentals >= 0 ? '+' : '' }}{{ $gainRentals }}%
                    </div>
                @endif
            </div>
            <div class="p-3 md:p-4 flex flex-col gap-0.5">
                <span class="text-[8px] md:text-[9px] font-bold text-muted-foreground uppercase opacity-40">Omset Kotor</span>
                <span class="text-base md:text-lg font-bold text-foreground leading-tight">Rp{{ number_format($periodRevenue / 1000, 0) }}k</span>
                @if($gainRevenue !== null)
                    <div class="text-[9px] font-bold {{ $gainRevenue >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                        {{ $gainRevenue >= 0 ? '+' : '' }}{{ $gainRevenue }}%
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

        <!-- Advanced Analytics Grid (New Data) -->
        <div class="grid grid-cols-3 divide-x divide-border/40 bg-muted/20 border-b border-border/40">
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-50">AOV (Rata-rata)</span>
                <span class="text-[11px] md:text-xs font-bold text-foreground">Rp{{ number_format($avgOrderValue/1000, 1) }}k</span>
            </div>
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-50">Profit Efficiency</span>
                <span class="text-[11px] md:text-xs font-bold text-emerald-600">{{ round($profitEfficiency, 1) }}%</span>
            </div>
            <div class="p-2.5 flex flex-col items-center">
                <span class="text-[7px] md:text-[8px] font-bold text-muted-foreground uppercase opacity-50">Avg Duration</span>
                <span class="text-[11px] md:text-xs font-bold text-foreground">{{ round($avgDuration, 1) }}h</span>
            </div>
        </div>

        <!-- Net Result -->
        <div class="bg-primary/5 p-3 flex items-center justify-between">
            <span class="text-[9px] md:text-[10px] font-bold text-primary/70 uppercase tracking-tight">Net Revenue Estimate</span>
            <span class="text-sm md:text-base font-bold text-primary">Rp{{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- 4. Charts Content (Side by Side Desktop, Stack Mobile) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
        <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden p-2">
            <div class="px-3 py-2 border-b border-border/20 mb-2 flex items-center justify-between">
                <h3 class="text-[10px] font-bold text-foreground/60 uppercase">Revenue Flow</h3>
                <div class="flex items-center gap-2">
                    <span class="flex items-center gap-1 text-[8px] font-bold opacity-50 text-indigo-500">■ Kotor</span>
                    <span class="flex items-center gap-1 text-[8px] font-bold opacity-50 text-emerald-500">■ Bersih</span>
                </div>
            </div>
            <div id="revenueChart" class="w-full h-[240px]" wire:ignore></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden p-2">
                <h3 class="text-[10px] font-bold text-foreground/60 uppercase px-3 py-1">Order Frequency</h3>
                <div id="transactionsChart" class="w-full h-[120px]" wire:ignore></div>
            </div>

            <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden p-2 flex items-center">
                <div class="flex-1">
                    <h3 class="text-[10px] font-bold text-foreground/60 uppercase px-3">Payments</h3>
                    <div id="paymentDonutChart" class="w-full h-[120px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Dense Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Top Units -->
        <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden">
            <div class="p-3 border-b border-border/40 bg-muted/10 font-bold text-[10px] text-muted-foreground uppercase opacity-60">Unit Performance</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-bold text-muted-foreground/60">
                    <tr>
                        <th class="px-3 py-1.5">Seri</th>
                        <th class="px-2 py-1.5 text-center">Qty</th>
                        <th class="px-3 py-1.5 text-right">Rev</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-border/20">
                    @foreach($topUnits as $tu)
                        <tr>
                            <td class="px-3 py-2 font-bold text-foreground truncate max-w-[100px]">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                            <td class="px-2 py-2 text-center opacity-60">{{ $tu->rent_count }}x</td>
                            <td class="px-3 py-2 text-right font-bold text-emerald-600">Rp{{ number_format($tu->revenue / 1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top Tenants -->
        <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden">
            <div class="p-3 border-b border-border/40 bg-muted/10 font-bold text-[10px] text-muted-foreground uppercase opacity-60">Loyal Tenants</div>
            <table class="w-full text-left">
                <thead class="bg-muted/30 text-[8px] font-bold text-muted-foreground/60">
                    <tr>
                        <th class="px-3 py-1.5">Nama</th>
                        <th class="px-2 py-1.5 text-center">Sewa</th>
                        <th class="px-3 py-1.5 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] divide-y divide-border/20">
                    @foreach($topTenants as $tenant)
                        <tr>
                            <td class="px-3 py-2">
                                <div class="font-bold text-foreground truncate max-w-[110px]">{{ $tenant->nama }}</div>
                                <div class="text-[7px] opacity-40">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-2 py-2 text-center font-bold opacity-50">{{ $tenant->total_rentals }}x</td>
                            <td class="px-3 py-2 text-right font-bold text-primary">Rp{{ number_format($tenant->total_spent/1000, 0) }}k</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Real-time Monitoring Table (The Most Compact) -->
    <div class="rounded-xl border border-border/40 bg-card shadow-sm overflow-hidden">
        <div class="p-3 border-b border-border/40 bg-emerald-500/5 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                <h3 class="text-[10px] font-bold text-foreground">Live Monitoring</h3>
            </div>
            <span class="text-[8px] font-bold text-muted-foreground uppercase opacity-40">Unit In Hands</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/40 text-[8px] font-bold text-muted-foreground/50">
                    <tr>
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2">Penyewa</th>
                        <th class="px-4 py-2 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[10px] font-medium divide-y divide-border/10">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalM = abs(now()->diffInMinutes($end));
                            $h = floor($totalM / 60);
                            $m = $totalM % 60;
                            $diffT = ($h > 0 ? $h . 'j' : '') . ($m . 'm');
                        @endphp
                        <tr class="hover:bg-muted/10 transition-all">
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-1 py-0 rounded bg-muted text-[9px] font-bold border border-border/30">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="font-bold text-foreground leading-tight">{{ $rental->nama }}</div>
                                <div class="text-[7px] opacity-30">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-4 py-2 text-right truncate">
                                @if($diffInHours < 0)
                                    <span class="text-red-500 font-extrabold uppercase text-[9px]">Telat</span>
                                @elseif($diffInHours < 3)
                                    <span class="text-amber-500 font-bold">{{ $diffT }}</span>
                                @else
                                    <span class="text-emerald-500 font-bold">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center opacity-30 text-[9px] font-bold italic">No active units.</td></tr>
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
                return { isD, txt: isD ? '#a1a1aa' : '#71717a', brd: isD ? '#27272a' : '#e4e4e7' };
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
                stroke: { width: 2, curve: 'smooth' }, fill: { type: 'gradient', gradient: { opacityFrom: 0.1, opacityTo: 0 } }
            });
            rv.render();

            tr = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...opt, series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { ...opt.chart, type: 'bar', height: 120 }, colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 2, columnWidth: '20%' } },
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false }, grid: { show: false }
            });
            tr.render();

            dn = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...opt, series: @json($paymentCounts), labels: @json($paymentLabels),
                chart: { ...opt.chart, type: 'donut', height: 120 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { show: false }, plotOptions: { pie: { donut: { size: '75%' } } }, stroke: { width: 0 }
            });
            dn.render();

            Livewire.on('chartDataUpdated', (d) => {
                const x = Array.isArray(d) ? d[0] : d;
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