<div class="pb-10">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Dashboard Administrator</h1>
            <p class="text-muted-foreground mt-1 text-sm md:text-base">Ringkasan statistik, grafis pendapatan, dan
                analisis penyewaan.</p>
        </div>
        <a href="{{ route('admin.units') }}" wire:navigate
            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-4 py-2 w-full md:w-auto">
            + Tambah Unit Baru
        </a>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-background rounded-xl border border-border p-5 shadow-sm">
            <h3 class="text-sm font-medium text-muted-foreground mb-1">Total & Aktif Unit</h3>
            <p class="text-2xl font-bold">{{ $activeUnits }} <span class="text-sm font-normal text-muted-foreground">/
                    {{ $totalUnits }} Unit</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-5 shadow-sm">
            <h3 class="text-sm font-medium text-muted-foreground mb-1">Order Hari Ini</h3>
            <p class="text-2xl font-bold">{{ $todayRentals }} <span
                    class="text-sm font-normal text-muted-foreground">Transaksi</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-5 shadow-sm">
            <h3 class="text-sm font-medium text-red-500 mb-1">Menunggu Bayar</h3>
            <p class="text-2xl font-bold">{{ $pendingRentals }} <span
                    class="text-sm font-normal text-muted-foreground">Order</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-5 shadow-sm">
            <h3 class="text-sm font-medium text-emerald-600 mb-1">Total Pendapatan</h3>
            <p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Interactive Area Chart: Shadcn Component Clone -->
    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col mb-6">
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-border py-5 px-6 gap-4">
            <div class="grid flex-1 gap-1">
                <h2 class="text-lg font-semibold leading-none tracking-tight">Pendapatan</h2>
                <p class="text-sm text-muted-foreground">Showing total gross revenue for the selected timeframe</p>
            </div>
            <select wire:model.live="chartRange"
                class="w-full sm:w-[160px] h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                <option value="90">Last 3 months</option>
                <option value="30">Last 30 days</option>
                <option value="7">Last 7 days</option>
            </select>
        </div>
        <div class="p-4 sm:p-6 pb-2">
            <!-- Render the Chart Container -->
            <div id="revenueChart" class="w-full aspect-auto h-[250px]" wire:ignore></div>
        </div>
    </div>

    <!-- Dual Analysis Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 w-full">

        <!-- Analisis Per Unit -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-semibold leading-none tracking-tight">Kinerja Unit Terbaik</h2>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-medium">Seri iPhone</th>
                            <th class="px-5 py-3 font-medium text-center">Jumlah Sewa</th>
                            <th class="px-5 py-3 font-medium text-right">Penghasilan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topUnits as $tu)
                        <tr class="hover:bg-muted/30">
                            <td class="px-5 py-3 font-medium text-foreground flex items-center gap-2">
                                {{ $tu->unit ? $tu->unit->seri : 'Unit Terhapus' }}
                                @if($tu->unit && $tu->unit->trashed())
                                <span
                                    class="text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-sm font-medium">Deleted</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">{{ $tu->rent_count }}x</td>
                            <td class="px-5 py-3 text-right font-medium text-emerald-600">Rp {{
                                number_format($tu->revenue, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-muted-foreground">Belum ada penyewaan
                                selesai.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analisis Data Penyewa -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-semibold leading-none tracking-tight">Data Penyewa Setia</h2>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm text-left whitespace-nowrap">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nama / NIK</th>
                            <th class="px-5 py-3 font-medium text-center">Jumlah Sewa</th>
                            <th class="px-5 py-3 font-medium text-right">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($topTenants as $tenant)
                        <tr class="hover:bg-muted/30">
                            <td class="px-5 py-3">
                                <div class="font-medium text-foreground">{{ $tenant->nama }}</div>
                                <div class="text-xs text-muted-foreground">{{ $tenant->nik }}</div>
                            </td>
                            <td class="px-5 py-3 text-center">{{ $tenant->total_rentals }}x</td>
                            <td class="px-5 py-3 text-right font-medium text-emerald-600">Rp {{
                                number_format($tenant->total_spent, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-muted-foreground">Belum ada penyewaan
                                selesai.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Active Rentals Right Now -->
    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm w-full">
        <div class="p-6 border-b border-border flex items-center justify-between">
            <div class="grid flex-1 gap-1">
                <h2 class="text-lg font-semibold leading-none tracking-tight">Sedang Disewa Saat Ini</h2>
                <p class="text-sm text-muted-foreground">Daftar unit yang sedang beredar di tangan pelanggan saat ini.
                </p>
            </div>
        </div>
        <div class="w-full overflow-x-auto">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="px-6 py-4 font-medium">Unit iPhone</th>
                        <th class="px-6 py-4 font-medium">Data Penyewa</th>
                        <th class="px-6 py-4 font-medium">Waktu Selesai</th>
                        <th class="px-6 py-4 font-medium text-right">Status Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($activeRentals as $rental)
                    @php
                    $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                    $diff = now()->diffInHours($end, false);
                    @endphp
                    <tr class="hover:bg-muted/30">
                        <td class="px-6 py-4 font-medium text-foreground">{{ $rental->unit ? $rental->unit->seri : 'Unit
                            Terhapus' }}</td>
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $rental->nama }}</span><br>
                            <span class="text-xs text-muted-foreground">{{ $rental->no_wa }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $end->format('d M Y - H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            @if($diff < 0) <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-destructive/10 text-destructive">
                                Telah Overdue</span>
                                @elseif($diff < 3) <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-500/10 text-amber-600">
                                    Sisa {{ $diff }} Jam</span>
                                    @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-500/10 text-emerald-600">Aman</span>
                                    @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-muted-foreground">
                            Tidak ada jadwal penyewaan yang sedang aktif.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        if (document.querySelector("#revenueChart") && typeof ApexCharts !== 'undefined') {

            var options = {
                series: [{
                    name: 'Revenue',
                    data: @json($chartSeries)
                }],
                chart: {
                    parentHeightOffset: 0,
                    type: 'area', // Makes it interactive line area
                    height: 250,
                    fontFamily: 'inherit',
                    toolbar: { show: false }, // Hidden completely to match shadcn
                    zoom: { enabled: false },
                    background: 'transparent',
                },
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                    colors: ['hsl(var(--primary))']
                },
                xaxis: {
                    categories: @json($chartCategories),
                    tooltip: { enabled: false },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: {
                            colors: 'hsl(var(--muted-foreground))',
                            fontFamily: 'inherit',
                        }
                    },
                    tickAmount: 5
                },
                yaxis: {
                    labels: {
                        formatter: function (value) { return "Rp" + (value / 1000).toLocaleString("id-ID") + "k"; },
                        style: {
                            colors: 'hsl(var(--muted-foreground))',
                            fontFamily: 'inherit',
                        }
                    }
                },
                grid: {
                    borderColor: 'hsl(var(--border))',
                    strokeDashArray: 0,
                    yaxis: { lines: { show: true } },
                    xaxis: { lines: { show: false } },
                    padding: { top: 0, right: 0, bottom: 0, left: 10 }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.15,
                        opacityTo: 0.01,
                        stops: [0, 100],
                        colorStops: [
                            { offset: 0, color: 'hsl(var(--primary))', opacity: 0.4 },
                            { offset: 100, color: 'hsl(var(--primary))', opacity: 0 }
                        ]
                    }
                },
                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                tooltip: {
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    y: { formatter: function (value) { return "Rp " + value.toLocaleString("id-ID"); } },
                    x: { show: true },
                    style: { fontSize: '12px', fontFamily: 'inherit' },
                    marker: { show: false }
                }
            };

            var chart = new ApexCharts(document.querySelector("#revenueChart"), options);
            chart.render();

            // When Livewire dispatching the chart update
            Livewire.on('chartDataUpdated', (data) => {
                chart.updateOptions({
                    xaxis: { categories: data[0].categories }
                });
                chart.updateSeries([{
                    name: 'Revenue',
                    data: data[0].series
                }]);
            });

            // Watch for body class changes to update chart theme
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        chart.updateOptions({
                            theme: { mode: isDark ? 'dark' : 'light' },
                            tooltip: { theme: isDark ? 'dark' : 'light' }
                        });
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });
        }
    });
</script>