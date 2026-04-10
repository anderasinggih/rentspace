<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Dashboard Administrator</h1>
            <p class="text-muted-foreground mt-1">Ringkasan statistik dan aktivitas penyewaan iPhone hari ini.</p>
        </div>
        <a href="{{ route('admin.units') }}" wire:navigate class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-4 py-2">
            + Tambah Unit Baru
        </a>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-background rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-sm font-medium text-muted-foreground mb-1">Total Unit iPhone</h3>
            <p class="text-2xl font-bold">{{ $activeUnits }} <span class="text-xs font-normal text-muted-foreground">/ {{ $totalUnits }} Aktif</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-sm font-medium text-muted-foreground mb-1">Penyewaan Hari Ini</h3>
            <p class="text-2xl font-bold">{{ $todayRentals }} <span class="text-xs font-normal text-muted-foreground">Transaksi</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-sm font-medium text-destructive mb-1">Tagihan Belum Lunas (Pending)</h3>
            <p class="text-2xl font-bold">{{ $pendingRentals }} <span class="text-xs font-normal text-muted-foreground">Order</span></p>
        </div>
        <div class="bg-background rounded-xl border border-border p-6 shadow-sm">
            <h3 class="text-sm font-medium text-green-600 mb-1">Total Pendapatan (Completed)</h3>
            <p class="text-2xl font-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Active Rentals Right Now -->
    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
        <div class="p-6 border-b border-border">
            <h2 class="text-lg font-semibold">Sedang Disewa Saat Ini</h2>
            <p class="text-sm text-muted-foreground">Daftar unit yang sedang dipakai oleh pelanggan saat ini juga.</p>
        </div>
        <div class="p-0">
            @if(count($activeRentals) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="px-6 py-3 font-medium">Unit iPhone</th>
                                <th class="px-6 py-3 font-medium">Penyewa</th>
                                <th class="px-6 py-3 font-medium">Waktu Selesai</th>
                                <th class="px-6 py-3 font-medium">Status / Waktu Tersisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($activeRentals as $rental)
                                @php
                                    $end = \Carbon\Carbon::parse($rental->waktu_selesai);
                                    $diff = now()->diffInHours($end, false);
                                @endphp
                                <tr class="hover:bg-muted/30">
                                    <td class="px-6 py-4 font-medium text-foreground">{{ $rental->unit->seri }}</td>
                                    <td class="px-6 py-4">
                                        {{ $rental->nama }}<br>
                                        <span class="text-xs text-muted-foreground">{{ $rental->no_wa }}</span>
                                    </td>
                                    <td class="px-6 py-4">{{ $end->format('d M Y - H:i') }}</td>
                                    <td class="px-6 py-4">
                                        @if($diff < 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Telah Lewat/Overdue</span>
                                        @elseif($diff < 3)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Sisa {{ $diff }} Jam</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aman</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-muted-foreground">
                    Tidak ada unit yang sedang disewa pada jam ini.
                </div>
            @endif
        </div>
    </div>
</div>
