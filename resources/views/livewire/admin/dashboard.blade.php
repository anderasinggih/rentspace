<div class="relative min-h-screen pb-20">
    <!-- Clean Background Layout (No blobs) -->
    
    <!-- Header Section: Minimalist Shadcn -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Dashboard</h1>
                <p class="text-sm text-muted-foreground mt-0.5">Statistik dan analisis performa RentSpace hari ini.</p>
            </div>
            
            <div class="flex items-center gap-2 p-1 bg-muted/40 rounded-lg border border-border/50">
                <select wire:model.live="preset"
                    class="h-8 w-full sm:w-[150px] rounded-md border-none bg-background px-3 py-1 text-xs font-medium shadow-sm outline-none">
                    <option value="7">7 hari terakhir</option>
                    <option value="30">30 hari terakhir</option>
                    <option value="90">3 bulan terakhir</option>
                    <option value="all">Semua waktu</option>
                    <option value="custom">Pilih tanggal</option>
                </select>

                @if($preset === 'custom')
                    <div class="flex items-center gap-2 px-2 border-l border-border/50">
                        <input type="date" wire:model.live="startDate" class="h-8 bg-transparent border-none text-[11px] p-0 font-medium outline-none">
                        <span class="text-muted-foreground opacity-30">→</span>
                        <input type="date" wire:model.live="endDate" class="h-8 bg-transparent border-none text-[11px] p-0 font-medium outline-none">
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Snapshot Metrics: Pure Shadcn Grid -->
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

    <!-- Period Metrics Table: Shadcn Density -->
    <div class="mb-8 overflow-hidden rounded-xl border border-border/50 bg-card">
        <div class="p-4 border-b border-border/50 flex items-center justify-between">
            <h3 class="text-xs font-semibold text-foreground/70">Performa periode ini</h3>
            <span class="text-[10px] font-medium text-muted-foreground bg-muted px-2 py-0.5 rounded-full">Analitik detail</span>
        </div>
        
        @php
            function getGainDisplay($gain) {
                if ($gain === null) return 'N/A';
                $isPos = $gain >= 0;
                $color = $isPos ? 'text-emerald-500' : 'text-red-500';
                $arrow = $isPos ? '↑' : '↓';
                return "<span class='flex items-center gap-0.5 $color font-semibold'>$arrow" . abs($gain) . "%</span>";
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
            <span class="text-[11px] font-semibold text-primary/80">Estimasi pendapatan bersih</span>
            <span class="text-base font-bold text-primary">Rp {{ number_format($periodNetRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Charts: Clean Shadcn Containers -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-8">
        <!-- Revenue Chart -->
        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
            <div class="p-5 border-b border-border/50">
                <h3 class="text-sm font-semibold text-foreground">Tren pendapatan</h3>
                <p class="text-xs text-muted-foreground">Kotor vs Bersih periode terpilih</p>
            </div>
            <div class="p-2 md:p-4">
                <div id="revenueChart" class="w-full h-[300px]" wire:ignore></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-1 gap-4">
            <!-- Transactions Trend -->
            <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
                <div class="p-4 border-b border-border/50 flex items-center justify-between">
                    <h3 class="text-[11px] font-semibold text-foreground/70">Frekuensi transaksi</h3>
                </div>
                <div class="p-2">
                    <div id="transactionsChart" class="w-full h-[140px]" wire:ignore></div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden px-4 py-3">
                <div class="mb-2">
                    <h3 class="text-[11px] font-semibold text-foreground/70">Metode pembayaran</h3>
                </div>
                <div class="flex items-center justify-center">
                    <div id="paymentDonutChart" class="w-full h-[180px]" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Analysis: Dense & Simple Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <!-- Top Units -->
        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border/50 flex items-center justify-between">
                <h3 class="text-xs font-semibold text-foreground/70">Performa unit</h3>
                <span class="text-[10px] text-muted-foreground">iPhone / iPad</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70 border-b border-border/50">
                        <tr>
                            <th class="px-4 py-2">Seri</th>
                            <th class="px-2 py-2 text-center">Qty</th>
                            <th class="px-2 py-2 text-center">Durasi</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-border/50">
                        @foreach($topUnits as $tu)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-2.5 font-medium text-foreground">{{ $tu->unit ? $tu->unit->seri : 'Unknown' }}</td>
                                <td class="px-2 py-2.5 text-center opacity-70">{{ $tu->rent_count }}x</td>
                                <td class="px-2 py-2.5 text-center opacity-70">{{ $tu->hours }}j</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">Rp{{ number_format($tu->revenue / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Tenants -->
        <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border/50 flex items-center justify-between">
                <h3 class="text-xs font-semibold text-foreground/70">Penyewa setia</h3>
                <span class="text-[10px] text-muted-foreground">Berdasarkan spent</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70 border-b border-border/50">
                        <tr>
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-2 py-2 text-center">Sewa</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] divide-y divide-border/50">
                        @foreach($topTenants as $tenant)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-foreground">{{ $tenant->nama }}</div>
                                    <div class="text-[9px] text-muted-foreground opacity-50">{{ $tenant->no_wa }}</div>
                                </td>
                                <td class="px-2 py-2 text-center opacity-70">{{ $tenant->total_rentals }}x</td>
                                <td class="px-4 py-2 text-right font-semibold text-primary">Rp{{ number_format($tenant->total_spent / 1000, 0, ',', '.') }}k</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Now: High Density Table -->
    <div class="rounded-xl border border-border/50 bg-card shadow-sm overflow-hidden">
        <div class="p-4 border-b border-border/50 bg-muted/20 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                <h3 class="text-xs font-semibold text-foreground">Sewa aktif hari ini</h3>
            </div>
            <span class="text-[10px] font-medium text-muted-foreground">Monitoring unit</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-muted/40 text-[10px] font-semibold text-muted-foreground/70">
                    <tr>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3">Penyewa</th>
                        <th class="px-5 py-3 text-center">Estimasi Selesai</th>
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
                                        <span class="px-1.5 py-0.5 rounded bg-muted text-foreground/80 font-semibold border border-border/50">{{ $u->seri }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-bold text-foreground">{{ $rental->nama }}</div>
                                <div class="text-[9px] text-muted-foreground opacity-60 mt-0.5">{{ $rental->booking_code }}</div>
                            </td>
                            <td class="px-5 py-3 text-center opacity-70">
                                <div>{{ $end->format('H:i') }}</div>
                                <div class="text-[9px]">{{ $end->format('d M') }}</div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($diffInHours < 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 dark:bg-red-950 text-red-600 dark:text-red-400 text-[10px] font-bold border border-red-200 dark:border-red-900">Telat</span>
                                @elseif($diffInHours < 3)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 text-[10px] font-bold border border-amber-200 dark:border-amber-900">{{ $diffText }} lagi</span>
                                @else
                                    <span class="text-emerald-500 text-[10px] font-bold">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center opacity-40 text-xs font-medium">Belum ada unit yang disewa hari ini.</td>
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