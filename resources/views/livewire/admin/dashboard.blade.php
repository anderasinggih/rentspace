<div class="pb-10">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-foreground">Dashboard Admin</h1>
        <p class="text-muted-foreground mt-1 text-xs md:text-sm">Ringkasan statistik, grafis pendapatan, dan
            analisis penyewaan.</p>
    </div>

    <!-- Snapshot Metrics -->
    <div>
        <h2 class="text-sm font-semibold text-foreground mb-3 border-b border-border pb-2">Status & Hari Ini (Real-time)
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
            <div class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between">
                <h3 class="text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">Total & Aktif Unit
                </h3>
                <p class="text-xl md:text-2xl font-bold text-foreground">{{ $activeUnits }} <span
                        class="text-xs font-normal text-muted-foreground">/ {{ $totalUnits }} Unit</span></p>
            </div>
            <div class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between">
                <h3 class="text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">Antrean Pending
                </h3>
                <p class="text-xl md:text-2xl font-bold text-amber-500">{{ $pendingRentals }} <span
                        class="text-xs font-normal text-muted-foreground">Order</span></p>
            </div>
            <div
                class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between border-l-4 border-l-emerald-500">
                <h3 class="text-xs font-semibold text-emerald-600 mb-1 uppercase tracking-wider">Pendapatan Hari Ini
                </h3>
                <p class="text-xl md:text-2xl font-bold text-emerald-600">Rp {{ number_format($todayRevenue/1000, 0,
                    ',', '.') }}k</p>
            </div>
            <div
                class="bg-background rounded-xl border border-border p-4 shadow-sm flex flex-col justify-between border-l-4 border-l-primary">
                <h3 class="text-xs font-semibold text-primary mb-1 uppercase tracking-wider">Sewa Hari Ini</h3>
                <p class="text-xl md:text-2xl font-bold text-primary">{{ $todayRentals }} <span
                        class="text-xs font-normal opacity-70">Transaksi</span></p>
            </div>
        </div>
    </div>

    <!-- Scoped Period Metrics -->
    <div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-3 border-b border-border pb-2">
            @if($preset === 'all')
            <h2 class="text-sm font-semibold text-foreground">Keseluruhan (Sepanjang Waktu)</h2>
            @else
            <h2 class="text-sm font-semibold text-foreground">Performa Periode Terpilih</h2>
            @endif
            
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                <select wire:model.live="preset"
                    class="h-8 w-full sm:w-[140px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="7">7 Hari Terakhir</option>
                    <option value="30">30 Hari Terakhir</option>
                    <option value="90">3 Bulan Terakhir</option>
                    <option value="all">Semua Waktu</option>
                    <option value="custom">Pilih Tanggal</option>
                </select>

                @if($preset === 'custom')
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" wire:model.live="startDate"
                        class="h-8 w-full sm:w-[120px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <span class="text-muted-foreground text-xs">-</span>
                    <input type="date" wire:model.live="endDate"
                        class="h-8 w-full sm:w-[120px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Sewa (Periode)</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">{{ $periodRentals }} <span
                        class="text-xs font-normal text-muted-foreground">Order</span></p>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Omset (Periode)</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">Rp {{ number_format($periodRevenue, 0, ',', '.')
                    }}</p>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Diskon Keluar</h3>
                <p class="text-lg md:text-xl font-bold text-red-500">-Rp {{ number_format($periodDiscounts, 0, ',', '.')
                    }}</p>
            </div>
            <div class="bg-muted/40 rounded-xl border border-border p-4 flex flex-col justify-between">
                <h3 class="text-[10px] sm:text-xs font-semibold text-muted-foreground mb-1 uppercase tracking-wider">
                    Rata-rata Transaksi</h3>
                <p class="text-lg md:text-xl font-bold text-foreground">Rp {{ $periodRentals > 0 ?
                    number_format($periodRevenue/$periodRentals, 0, ',', '.') : 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Dual Interactive Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Revenue Area Chart -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Tren Pendapatan</h2>
                <p class="text-xs text-muted-foreground mt-1">Pendapatan kotor selama periode yang dipilih</p>
            </div>
            <div class="p-3">
                <div id="revenueChart" class="w-full h-[220px]" wire:ignore></div>
            </div>
        </div>

        <!-- Transactions Bar Chart -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Tren Transaksi</h2>
                <p class="text-xs text-muted-foreground mt-1">Jumlah penyewaan selesai / lunas</p>
            </div>
            <div class="p-3">
                <div id="transactionsChart" class="w-full h-[220px]" wire:ignore></div>
            </div>
        </div>
    </div>

    <!-- Dual Analysis Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 w-full">
        <!-- Analisis Per Unit -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Unit Paling Laris</h2>
                <p class="text-[11px] text-muted-foreground mt-1">Berdasarkan total pendapatan di periode terpilih</p>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Seri iPhone</th>
                            <th class="px-4 py-2 font-medium text-center">Sewa</th>
                            <th class="px-4 py-2 font-medium text-center">Total Durasi</th>
                            <th class="px-4 py-2 font-medium text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topUnits as $tu)
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium text-foreground flex items-center gap-2">
                                {{ $tu->unit ? $tu->unit->seri : 'Unit Terhapus' }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ $tu->rent_count }}x</td>
                            <td class="px-4 py-3 text-center">{{ $tu->hours }} Jam</td>
                            <td class="px-4 py-3 text-right font-medium text-emerald-600">Rp {{
                                number_format($tu->revenue/1000, 0, ',', '.') }}k</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">Belum ada data unit
                                tersewa.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analisis Data Penyewa -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border">
                <h2 class="text-sm font-semibold leading-none tracking-tight">Penyewa Setia</h2>
                <p class="text-[11px] text-muted-foreground mt-1">Berdasarkan total belanja di periode terpilih</p>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Pelanggan</th>
                            <th class="px-4 py-2 font-medium text-center">Sewa</th>
                            <th class="px-4 py-2 font-medium text-right">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topTenants as $tenant)
                        <tr class="hover:bg-muted/30">
                            <td class="px-4 py-2 text-foreground">
                                <div class="font-medium">{{ $tenant->nama }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ $tenant->no_wa }}</div>
                            </td>
                            <td class="px-4 py-2 text-center">{{ $tenant->total_rentals }}x</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-600">Rp {{
                                number_format($tenant->total_spent/1000, 0, ',', '.') }}k</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-muted-foreground">Belum ada data penyewa
                                setia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Rentals Right Now -->
    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm w-full">
        <div class="p-4 border-b border-border">
            <h2 class="text-sm font-semibold leading-none tracking-tight">Status Sewa Berjalan Berjalan</h2>
            <p class="text-[11px] text-muted-foreground mt-1">Daftar unit yang sedang beredar di tangan pelanggan saat
                ini</p>
        </div>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-xs text-left whitespace-nowrap">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Unit</th>
                        <th class="px-4 py-2 font-medium">Penyewa</th>
                        <th class="px-4 py-2 font-medium">Waktu Selesai</th>
                        <th class="px-4 py-2 font-medium text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($activeRentals as $rental)
                    @php
                    $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                    $diff = now()->diffInHours($end, false);
                    @endphp
                    <tr class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ $rental->unit ? $rental->unit->seri :
                            'Terhapus' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-foreground">{{ $rental->nama }}</span><br>
                            <span class="text-[10px] text-muted-foreground">{{ $rental->no_wa }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $end->format('d M, H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($diff < 0) <span
                                class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-destructive/10 text-destructive">
                                Telat Masuk
                                </span>
                                @elseif($diff < 3) <span
                                    class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-amber-500/10 text-amber-600">
                                    Sisa {{ $diff }} Jam
                                    </span>
                                    @else
                                    <span
                                        class="inline-flex px-1.5 py-0.5 rounded-sm text-[10px] font-bold bg-emerald-500/10 text-emerald-600">
                                        Aman
                                    </span>
                                    @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">Tidak ada jadwal penyewaan
                            yang sedang aktif dibawa pelanggan saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        if (typeof ApexCharts !== 'undefined') {

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = 'hsl(var(--muted-foreground))';
            const borderColor = 'hsl(var(--border))';

            // 1. REVENUE AREA CHART
            var revOptions = {
                series: [{ name: 'Pendapatan', data: @json($chartRevenue) }],
                chart: {
                    type: 'area', height: 220, fontFamily: 'inherit',
                    toolbar: { show: false }, zoom: { enabled: false },
                    background: 'transparent', offsetX: -10, offsetY: 10
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2, colors: ['hsl(var(--primary))'] },
                xaxis: {
                    categories: @json($chartCategories),
                    tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { style: { colors: textColor, fontFamily: 'inherit', fontSize: '10px' } },
                    tickAmount: 5
                },
                yaxis: {
                    labels: {
                        formatter: function (val) { return "Rp" + (val / 1000).toLocaleString("id-ID") + "k"; },
                        style: { colors: textColor, fontFamily: 'inherit', fontSize: '10px' }
                    }
                },
                grid: {
                    borderColor: borderColor, strokeDashArray: 0,
                    yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } },
                    padding: { top: 0, right: 0, bottom: 0, left: 10 }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.01,
                        stops: [0, 100],
                        colorStops: [
                            { offset: 0, color: 'hsl(var(--primary))', opacity: 0.3 },
                            { offset: 100, color: 'hsl(var(--primary))', opacity: 0 }
                        ]
                    }
                },
                theme: { mode: isDark ? 'dark' : 'light' },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: { formatter: function (val) { return "Rp " + val.toLocaleString("id-ID"); } },
                    style: { fontSize: '11px', fontFamily: 'inherit' }, marker: { show: false }
                }
            };

            var revChart = new ApexCharts(document.querySelector("#revenueChart"), revOptions);
            revChart.render();

            // 2. TRANSACTIONS BAR CHART
            var trxOptions = {
                series: [{ name: 'Jml Sewa', data: @json($chartTransactions) }],
                chart: {
                    type: 'bar', height: 220, fontFamily: 'inherit',
                    toolbar: { show: false }, background: 'transparent', offsetX: -10, offsetY: 10
                },
                plotOptions: {
                    bar: { borderRadius: 4, columnWidth: '40%' }
                },
                dataLabels: { enabled: false },
                colors: ['#10b981'], // Emerald-500
                xaxis: {
                    categories: @json($chartCategories),
                    tooltip: { enabled: false }, axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { style: { colors: textColor, fontFamily: 'inherit', fontSize: '10px' } },
                    tickAmount: 5
                },
                yaxis: {
                    labels: {
                        formatter: function (val) { return Math.round(val); },
                        style: { colors: textColor, fontFamily: 'inherit', fontSize: '10px' }
                    }
                },
                grid: {
                    borderColor: borderColor, strokeDashArray: 0,
                    yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } },
                    padding: { top: 0, right: 0, bottom: 0, left: 10 }
                },
                theme: { mode: isDark ? 'dark' : 'light' },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: { formatter: function (val) { return val + " Orders"; } },
                    style: { fontSize: '11px', fontFamily: 'inherit' }, marker: { show: false }
                }
            };

            var trxChart = new ApexCharts(document.querySelector("#transactionsChart"), trxOptions);
            trxChart.render();

            // Handle Livewire Updates
            Livewire.on('chartDataUpdated', (data) => {
                const updateData = Array.isArray(data) ? data[0] : data;

                revChart.updateOptions({ xaxis: { categories: updateData.categories } });
                revChart.updateSeries([{ name: 'Revenue', data: updateData.revenue }]);

                trxChart.updateOptions({ xaxis: { categories: updateData.categories } });
                trxChart.updateSeries([{ name: 'Rentals', data: updateData.transactions }]);
            });

            // Watch for Theme Changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const newIsDark = document.documentElement.classList.contains('dark');
                        const themeConfig = { mode: newIsDark ? 'dark' : 'light' };
                        const tooltipConfig = { theme: newIsDark ? 'dark' : 'light' };

                        revChart.updateOptions({ theme: themeConfig, tooltip: tooltipConfig });
                        trxChart.updateOptions({ theme: themeConfig, tooltip: tooltipConfig });
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });
        }
    });
</script>