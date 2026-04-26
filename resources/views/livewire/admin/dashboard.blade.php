<div class="relative min-h-screen pb-20">
    <!-- Header Section: Pure Shadcn Minimalist -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-foreground tracking-tight">Dashboard</h1>
        <p class="text-sm text-muted-foreground mt-0.5">Statistik dan analisis performa RentSpace.</p>
    </div>

    <!-- 1. Summary Hari Ini (Snapshot Metrics) -->
    <div class="mb-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            <!-- Stats Card 1 -->
            <div class="rounded-xl border border-border/50 bg-card p-4 shadow-sm">
                <p class="text-[11px] font-medium text-muted-foreground mb-1">Unit Aktif</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-semibold text-foreground">{{ $activeUnits }}</span>
                    <span class="text-[11px] font-medium text-muted-foreground opacity-60">/ {{ $totalUnits }} unit</span>
                </div>
                <div class="mt-3 h-1 w-full bg-muted rounded-full overflow-hidden">
                    <div class="h-full bg-primary" style="width: {{ $totalUnits > 0 ? ($activeUnits / $totalUnits) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Stats Card 2 -->
            <div class="rounded-xl border border-border/50 bg-card p-4 shadow-sm">
                <p class="text-[11px] font-medium text-muted-foreground mb-1">Pending order</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-semibold text-amber-600">{{ $pendingRentals }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-600 text-[10px] font-medium border border-amber-500/20">
                        Rp {{ number_format($pendingRevenue / 1000, 0, ',', '.') }}k
                    </span>
                </div>
            </div>

            <!-- Stats Card 3 -->
            <div class="rounded-xl border border-border/50 bg-card p-4 shadow-sm">
                <p class="text-[11px] font-medium text-muted-foreground mb-1">Pendapatan hari ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-[11px] font-medium text-muted-foreground opacity-60">Rp</span>
                    <span class="text-2xl font-semibold text-emerald-600">{{ number_format($todayRevenue / 1000, 0, ',', '.') }}k</span>
                </div>
            </div>

            <!-- Stats Card 4 -->
            <div class="rounded-xl border border-border/50 bg-card p-4 shadow-sm">
                <p class="text-[11px] font-medium text-muted-foreground mb-1">Sewa hari ini</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-semibold text-blue-600">{{ $todayRentals }}</span>
                    <span class="text-[11px] font-medium text-muted-foreground opacity-60">unit</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Filter Periode (Pindah ke bawah Summary Hari Ini) -->
    <div class="mb-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h2 class="text-xs font-bold text-muted-foreground uppercase tracking-tight opacity-70">Analisis Historis</h2>
            
            <div class="flex items-center gap-2 p-1 bg-muted/40 rounded-xl border border-border/40 shadow-sm overflow-hidden">
                <div class="relative">
                    <select wire:model.live="preset"
                        class="appearance-none h-9 w-full sm:w-[160px] rounded-lg border-none bg-background pl-4 pr-10 py-1 text-xs font-bold shadow-sm transition-all focus:ring-2 focus:ring-primary/20 outline-none cursor-pointer">
                        <option value="7">7 hari terakhir</option>
                        <option value="30">30 hari terakhir</option>
                        <option value="90">3 bulan terakhir</option>
                        <option value="all">Semua waktu</option>
                        <option value="custom">Pilih tanggal</option>
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>

                @if($preset === 'custom')
                    <div class="flex items-center gap-2 px-3 border-l border-border/30">
                        <input type="date" wire:model.live="startDate" class="h-8 bg-transparent border-none text-[11px] p-0 font-black outline-none">
                        <span class="text-muted-foreground opacity-20">→</span>
                        <input type="date" wire:model.live="endDate" class="h-8 bg-transparent border-none text-[11px] p-0 font-black outline-none">
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. Period Metrics Table -->
    <div class="mb-8 overflow-hidden rounded-xl border border-border/50 bg-card">
        <div class="p-4 border-b border-border/50 flex items-center justify-between">
            <h3 class="text-xs font-semibold text-foreground/70">Performa periode terpilih</h3>
            <span class="text-[10px] font-medium text-muted-foreground bg-muted px-2 py-0.5 rounded-full">Data olahan</span>
        </div>
        
        @php
            function getGainDisplay($gain) {
                if ($gain === null) return 'N/A';
                $isPos = $gain >= 0;
                $color = $isPos ? 'text-emerald-500' : 'text-red-500';
                $arrow = $isPos ? '↑' : '↓';
                return "<span class='flex items-center gap-0.5 $color font-bold'>$arrow" . abs($gain) . "%</span>";
            }
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-border/50 border-b border-border/50">
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[10px] font-medium text-muted-foreground">Volume sewa</span>
                <span class="text-lg font-semibold text-foreground">{{ $periodRentals }}</span>
                <div class="text-[10px] uppercase font-bold">{!! getGainDisplay($gainRentals) !!}</div>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[10px] font-medium text-muted-foreground">Omset kotor</span>
                <span class="text-lg font-semibold text-foreground">Rp {{ number_format($periodRevenue / 1000, 0, ',', '.') }}k</span>
                <div class="text-[10px] uppercase font-bold">{!! getGainDisplay($gainRevenue) !!}</div>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[10px] font-medium text-muted-foreground">Komisi affiliate</span>
                <span class="text-lg font-semibold text-red-500">Rp {{ number_format($periodCommissions / 1000, 0, ',', '.') }}k</span>
            </div>
            <div class="p-4 flex flex-col gap-1">
                <span class="text-[10px] font-medium text-muted-foreground">Diskon keluar</span>
                <span class="text-lg font-semibold text-foreground">Rp {{ number_format($periodDiscounts / 1000, 0, ',', '.') }}k</span>
            </div>
        </div>

        <div class="bg-primary/5 p-4 flex items-center justify-between">
            <span class="text-[11px] font-semibold text-primary/80 uppercase tracking-tight">Estimasi pendapatan bersih</span>
            <span class="text-base font-bold text-primary">Rp {{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- 4. Charts -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-8">
        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
            <div class="p-5 border-b border-border/50 bg-muted/10">
                <h3 class="text-sm font-semibold text-foreground">Tren pendapatan</h3>
            </div>
            <div class="p-2 md:p-4">
                <div id="revenueChart" class="w-full h-[300px]" wire:ignore></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b border-border/50 flex items-center justify-between bg-muted/10">
                    <h3 class="text-[11px] font-semibold text-foreground/70 uppercase">Frekuensi transaksi</h3>
                </div>
                <div class="p-2">
                    <div id="transactionsChart" class="w-full h-[140px]" wire:ignore></div>
                </div>
            </div>

            <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden px-4 py-3">
                <div class="mb-2">
                    <h3 class="text-[11px] font-semibold text-foreground/70 uppercase">Metode pembayaran</h3>
                </div>
                <div class="flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[180px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Top Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border/50 flex items-center justify-between bg-muted/30">
                <h3 class="text-xs font-semibold text-foreground/70">Performa unit</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70 border-b border-border/50">
                        <tr>
                            <th class="px-4 py-2">Seri</th>
                            <th class="px-2 py-2 text-center">Qty</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-border/50">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-2.5 font-medium text-foreground">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                                <td class="px-2 py-2.5 text-center opacity-70">{{ $tu->rent_count }}x</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">Rp{{ number_format($tu->revenue / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border/50 flex items-center justify-between bg-muted/30">
                <h3 class="text-xs font-semibold text-foreground/70">Penyewa setia</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70 border-b border-border/50">
                        <tr>
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-4 py-2 text-right">Spent</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-border/50">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-foreground">{{ $tenant->nama }}</div>
                                    <div class="text-[9px] text-muted-foreground opacity-50">{{ $tenant->no_wa }}</div>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-primary">Rp{{ number_format($tenant->total_spent / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Active Now -->
    <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
        <div class="p-4 border-b border-border/50 bg-muted/20 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                <h3 class="text-xs font-semibold text-foreground">Sewa aktif hari ini</h3>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70">
                    <tr>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Penyewa</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] font-medium divide-y divide-border/50">
                    @forelse($activeRentals as $rental)
                        @php
                            $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                            $diffInHours = now()->diffInHours($end, false);
                            $totalMinutes = abs(now()->diffInMinutes($end));
                            $h = floor($totalMinutes / 60);
                            $m = $totalMinutes % 60;
                            $diffText = ($h > 0 ? $h . 'j ' : '') . ($m > 0 ? $m . 'm' : ($h == 0 ? '0m' : ''));
                        @endphp
                        <tr class="hover:bg-muted/20 transition-all">
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($rental->units as $u)
                                        <span class="px-1.5 py-0.5 rounded bg-muted text-foreground/80 font-bold border border-border/50">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-bold text-foreground">{{ $rental->nama }}</div>
                                <div class="text-[9px] text-muted-foreground opacity-60">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($diffInHours < 0)
                                    <span class="text-red-500 font-bold">Telat</span>
                                @elseif($diffInHours < 3)
                                    <span class="text-amber-500 font-bold">{{ $diffText }} lagi</span>
                                @else
                                    <span class="text-emerald-500 font-bold">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center opacity-40 text-xs font-medium">Kosong.</td>
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

            function getChartColors() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    isDark,
                    textColor: isDark ? '#a1a1aa' : '#71717a',
                    borderColor: isDark ? '#27272a' : '#e4e4e7',
                };
            }

            let colors = getChartColors();
            let revChart, trxChart, donutChart;

            const baseOptions = {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                theme: { mode: colors.isDark ? 'dark' : 'light' },
                grid: { borderColor: colors.borderColor, strokeDashArray: 4, padding: { left: 0, right: 0 } },
            };

            // ── 1. REVENUE AREA CHART ──────────────────────────────────────────────
            revChart = new ApexCharts(document.querySelector("#revenueChart"), {
                ...baseOptions,
                series: [
                    { name: 'Kotor', data: @json($chartRevenue) },
                    { name: 'Bersih', data: @json($chartNetRevenue) }
                ],
                chart: { ...baseOptions.chart, type: 'area', height: 300 },
                colors: ['#6366f1', '#10b981'],
                xaxis: {
                    categories: @json($chartCategories),
                    labels: { style: { colors: colors.textColor, fontSize: '10px' } },
                    tickAmount: window.innerWidth < 640 ? 3 : 8
                },
                yaxis: {
                    labels: {
                        formatter: (val) => val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val,
                        style: { colors: colors.textColor, fontSize: '10px' }
                    }
                },
                stroke: { width: 2, curve: 'straight' },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.1, opacityTo: 0.0 } },
                tooltip: { theme: colors.isDark ? 'dark' : 'light' }
            });
            revChart.render();

            // ── 2. TRANSACTIONS BAR CHART ──────────────────────────────────────────
            trxChart = new ApexCharts(document.querySelector("#transactionsChart"), {
                ...baseOptions,
                series: [{ name: 'Order', data: @json($chartTransactions) }],
                chart: { ...baseOptions.chart, type: 'bar', height: 140 },
                colors: ['#10b981'],
                plotOptions: { bar: { borderRadius: 2, columnWidth: '20%' } },
                xaxis: { labels: { show: false } },
                yaxis: { show: false },
                grid: { show: false },
                tooltip: { theme: colors.isDark ? 'dark' : 'light' }
            });
            trxChart.render();

            // ── 3. PAYMENT METHOD DONUT CHART ─────────────────────────────────────
            donutChart = new ApexCharts(document.querySelector("#paymentDonutChart"), {
                ...baseOptions,
                series: @json($paymentCounts),
                labels: @json($paymentLabels),
                chart: { ...baseOptions.chart, type: 'donut', height: 180 },
                colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                legend: { position: 'right', fontSize: '10px', labels: { colors: colors.textColor } },
                plotOptions: { pie: { donut: { size: '70%' } } },
                stroke: { width: 0 }
            });
            donutChart.render();

            // ── Livewire updates ──────────────────────────────────────────────────
            Livewire.on('chartDataUpdated', (data) => {
                const d = Array.isArray(data) ? data[0] : data;
                revChart?.updateOptions({ xaxis: { categories: d.categories } });
                revChart?.updateSeries([{ name: 'Kotor', data: d.revenue }, { name: 'Bersih', data: d.netRevenue }]);
                trxChart?.updateSeries([{ name: 'Order', data: d.transactions }]);
            });

            // ── Theme observer ────────────────────────────────────────────────────
            const observer = new MutationObserver(() => {
                const c = getChartColors();
                [revChart, trxChart, donutChart].forEach(chart => {
                    chart?.updateOptions({ theme: { mode: c.isDark ? 'dark' : 'light' }, grid: { borderColor: c.borderColor } });
                });
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            document.addEventListener('livewire:navigating', () => {
                observer.disconnect();
                [revChart, trxChart, donutChart].forEach(c => c?.destroy());
            }, { once: true });
        };
        initCharts();
    }
</script>
@endscript