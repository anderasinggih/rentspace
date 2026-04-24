<div>
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-[100] bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg"
            x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ session('message') }}
        </div>
    @endif
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold  text-foreground">Transactions & Mutations</h1>
                <p class="mt-2 text-sm text-muted-foreground">Verify payments via unique codes and manage rental
                    schedules.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button wire:click="exportCsv"
                    class="inline-flex items-center gap-2 justify-center rounded-md bg-secondary text-secondary-foreground shadow hover:bg-secondary/80 h-9 px-4 text-sm font-semibold transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" x2="12" y1="15" y2="3" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 items-end sm:items-center justify-between">
            <div class="flex flex-1 flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <div class="relative flex-1 max-w-sm">
                    <div
                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full h-9 pl-10 pr-3 text-sm rounded-md border border-input bg-background shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Cari nama, invoice, atau WA...">
                </div>

                <div class="hidden sm:flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-end">
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Dari</label>
                        <input type="date" wire:model.live="dateStart"
                            class="h-9 w-full sm:w-[140px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Sampai</label>
                        <input type="date" wire:model.live="dateEnd"
                            class="h-9 w-full sm:w-[140px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Status</label>
                        <select wire:model.live="filterStatus"
                            class="h-9 w-full sm:w-[150px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">Semua</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="paid">💳 Lunas</option>
                            <option value="completed">✅ Selesai</option>
                            <option value="cancelled">❌ Batal</option>
                            <option value="trashed">🗑️ Terhapus</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col"
                                        class="py-3 pl-3 pr-3 text-left text-xs sm:text-sm font-semibold text-foreground sm:pl-6 cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('booking_code')">
                                        <div class="flex items-center gap-1">
                                            Booking Code & Customer
                                            @if($sortField === 'booking_code')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('created_at')">
                                        <div class="flex items-center gap-1">
                                            Tgl Transaksi
                                            @if($sortField === 'created_at')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Unit Sewa</th>
                                    <th scope="col"
                                        class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('waktu_mulai')">
                                        <div class="flex items-center gap-1">
                                            Jadwal Sewa
                                            @if($sortField === 'waktu_mulai')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('subtotal_harga')">
                                        <div class="flex items-center gap-1">
                                            Subtotal
                                            @if($sortField === 'subtotal_harga')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-bold text-primary cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('grand_total')">
                                        <div class="flex items-center gap-1">
                                            Tagihan & Profit
                                            @if($sortField === 'grand_total')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3 text-left text-xs sm:text-sm font-semibold text-foreground cursor-pointer hover:bg-muted transition-colors"
                                        wire:click="sortBy('status')">
                                        <div class="flex items-center gap-1">
                                            Status
                                            @if($sortField === 'status')
                                                <span>{!! $sortDirection === 'asc' ? '↑' : '↓' !!}</span>
                                            @endif
                                        </div>
                                    </th>

                                    <th scope="col" class="relative py-3 pl-3 pr-2 sm:pr-6"><span
                                            class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border text-[11px]">
                                @forelse ($transactions as $trx)
                                                                <tr wire:click="openInspect({{ $trx->id }})"
                                                                    class="cursor-pointer hover:bg-muted/40 transition-colors group/row">
                                                                    <td class="whitespace-nowrap py-3 pl-3 pr-3 text-xs sm:pl-6">
                                                                        <div class="flex flex-col gap-1 tracking-tight">
                                                                            <div class="font-bold text-foreground text-sm tracking-tight leading-none">{{ $trx->nama }}</div>
                                                                            <div class="flex items-center gap-2">
                                                                                <span class="inline-flex items-center rounded border bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200/30 dark:border-blue-800/30 px-1.5 py-0.5 font-mono text-[9px] font-bold uppercase tracking-tight">
                                                                                    {{ $trx->booking_code }}
                                                                                </span>
                                                                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', $trx->no_wa) }}"
                                                                                    target="_blank" wire:click.stop class="text-[10px] text-muted-foreground font-semibold hover:text-primary transition-colors tracking-tight">{{ $trx->no_wa }}</a>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td
                                                                        class="hidden sm:table-cell whitespace-nowrap px-3 py-3 text-xs text-muted-foreground">
                                                                        {{ $trx->created_at->format('d M Y') }}<br />
                                                                        <span class="opacity-70">{{ $trx->created_at->format('H:i') }} WIB</span>
                                                                    </td>
                                                                    <td
                                                                        class="hidden sm:table-cell whitespace-nowrap px-3 py-3 text-muted-foreground">
                                                                        <div class="flex flex-col gap-0">
                                                                            @foreach($trx->units->take(2) as $u)
                                                                                <span
                                                                                    class="font-medium text-foreground text-xs leading-none">{{ $u->seri }}</span>
                                                                            @endforeach
                                                                            @if($trx->units->count() > 2)
                                                                                <span
                                                                                    class="text-[9px] text-muted-foreground mt-0.5">+{{ $trx->units->count() - 2 }}</span>
                                                                            @endif
                                                                            @if($trx->units->isEmpty() && $trx->unit)
                                                                                <span
                                                                                    class="font-medium text-foreground text-xs leading-none">{{ $trx->unit->seri }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                    <td
                                                                        class="hidden md:table-cell whitespace-nowrap px-3 py-3 text-muted-foreground text-[10px] leading-tight">
                                                                        {{ \Carbon\Carbon::parse($trx->waktu_mulai)->format('d/m/y H:i')
                                                                                                        }}<br />
                                                                        {{ \Carbon\Carbon::parse($trx->waktu_selesai)->format('d/m/y H:i') }}
                                                                    </td>
                                                                    <td
                                                                        class="hidden md:table-cell whitespace-nowrap px-3 py-3 text-muted-foreground leading-tight">
                                                                        Rp {{ number_format($trx->subtotal_harga, 0, ',', '.') }}<br />
                                                                        <span class="text-xs text-red-500">Diskon: -Rp {{
                                        number_format($trx->potongan_diskon, 0, ',', '.') }}</span>
                                                                    </td>
                                                                    <td
                                                                        class="hidden sm:table-cell whitespace-nowrap px-3 py-1.5 text-sm font-bold text-primary leading-none">
                                                                        Rp {{ number_format($trx->grand_total, 0, ',', '.') }}<br />
                                                                        @php
                                                                            $trxCommission = $trx->commissions->sum('amount');
                                                                            $trxNet = $trx->grand_total - $trxCommission;
                                                                        @endphp
                                                                        @if($trxCommission > 0)
                                                                            <div
                                                                                class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                                                Net: Rp {{ number_format($trxNet, 0, ',', '.') }}
                                                                            </div>
                                                                        @endif
                                                                        <div class="mt-0.5 flex flex-wrap gap-1">
                                                                            <span
                                                                                class="inline-flex rounded border bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border-purple-200/50 dark:border-purple-900/50 px-1 font-mono text-[9px] font-semibold uppercase">
                                                                                {{ $trx->kode_unik_pembayaran }}
                                                                            </span>
                                                                            <span
                                                                                class="inline-flex rounded border bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border-sky-200/50 dark:border-sky-900/50 px-1 font-mono text-[9px] font-semibold uppercase">
                                                                                {{ $trx->metode_pembayaran }}
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="whitespace-nowrap px-2 sm:px-3 py-3">
                                                                        @if($trx->status === 'pending')
                                                                            <x-ui.badge variant="amber" class="text-[9px]">Pending</x-ui.badge>
                                                                        @elseif($trx->status === 'paid')
                                                                            <x-ui.badge variant="blue" class="text-[9px]">Paid</x-ui.badge>
                                                                        @elseif($trx->status === 'completed')
                                                                            <x-ui.badge variant="green" class="text-[9px]">Selesai</x-ui.badge>
                                                                        @else
                                                                            <x-ui.badge variant="red" class="text-[9px]">Batal</x-ui.badge>
                                                                        @endif
                                                                                                              <td class="relative whitespace-nowrap py-3 pl-2 pr-2 sm:pr-6 text-right">
                                                                        <div class="flex items-center justify-end gap-2">
                                                                            @if($filterStatus === 'trashed')
                                                                                @if(auth()->user()->role === 'admin')
                                                                                    {{-- Restore Button --}}
                                                                                    <button wire:click.stop="restore({{ $trx->id }})"
                                                                                        wire:confirm="Pulihkan transaksi ini ke daftar aktif?"
                                                                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors"
                                                                                        title="Pulihkan Transaksi">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
                                                                                        </svg>
                                                                                    </button>

                                                                                    {{-- Force Delete Button --}}
                                                                                    <button wire:click.stop="forceDelete({{ $trx->id }})"
                                                                                        wire:confirm="PERINGATAN: Data ini akan dihapus PERMANEN dari database dan tidak bisa dikembalikan lagi. Lanjutkan?"
                                                                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-100 dark:hover:bg-red-950 transition-colors"
                                                                                        title="Hapus Permanen">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                                            <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m10 11 4 4"/><path d="m14 11-4 4"/>
                                                                                        </svg>
                                                                                    </button>
                                                                                @endif
                                                                            @else
                                                                                @if($trx->status === 'pending')
                                                                                    @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                        {{-- Validasi --}}
                                                                                        <x-ui.button wire:click.stop="markAsPaid({{ $trx->id }})"
                                                                                            wire:confirm="Transaksi ini sudah valid transfer?"
                                                                                            wire:loading.attr="disabled" wire:target="markAsPaid({{ $trx->id }})"
                                                                                            variant="success" size="sm"
                                                                                            class="gap-1.5 shadow-lg shadow-emerald-500/10">
                                                                                            <svg wire:loading.remove wire:target="markAsPaid({{ $trx->id }})"
                                                                                                xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                                                <polyline points="20 6 9 17 4 12" />
                                                                                            </svg>
                                                                                            <span wire:loading wire:target="markAsPaid({{ $trx->id }})"
                                                                                                class="h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                                                            Validasi
                                                                                        </x-ui.button>
                                                                                        <x-ui.button wire:click.stop="cancel({{ $trx->id }})"
                                                                                            wire:confirm="Batalkan pesanan ini?" wire:loading.attr="disabled"
                                                                                            wire:target="cancel({{ $trx->id }})" variant="destructive" size="sm"
                                                                                            class="gap-1.5">
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                                                <circle cx="12" cy="12" r="10" />
                                                                                                <line x1="15" y1="9" x2="9" y2="15" />
                                                                                                <line x1="9" y1="9" x2="15" y2="15" />
                                                                                            </svg>
                                                                                            Batal
                                                                                        </x-ui.button>
                                                                                    @endif
                                                                                @elseif($trx->status === 'paid')
                                                                                    @php
                                                                                        $tolerance = (int) \App\Models\Setting::getVal('late_tolerance_minutes', 60);
                                                                                        $isLate = (\Carbon\Carbon::parse($trx->waktu_selesai)->addMinutes($tolerance) < now());
                                                                                    @endphp
                                                                                    @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                        <x-ui.button wire:click.stop="openDendaModal({{ $trx->id }})"
                                                                                            wire:loading.attr="disabled"
                                                                                            wire:target="openDendaModal({{ $trx->id }})" :variant="$isLate ? 'destructive' : 'default'" size="sm" class="gap-1.5 shadow-lg">
                                                                                            <svg wire:loading.remove wire:target="openDendaModal({{ $trx->id }})"
                                                                                                xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                                                                                <polyline points="22 4 12 14.01 9 11.01" />
                                                                                            </svg>
                                                                                            <span wire:loading wire:target="openDendaModal({{ $trx->id }})"
                                                                                                class="h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                                                            Selesaikan Sewa
                                                                                        </x-ui.button>
                                                                                    @endif
                                                                                @endif
                                                                                
                                                                                {{-- Soft Delete Button --}}
                                                                                @if(auth()->user()->role === 'admin')
                                                                                    <button wire:click.stop="deleteRow({{ $trx->id }})"
                                                                                        wire:confirm="Pindahkan transaksi ini ke kotak sampah?"
                                                                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                                                                                        title="Buang ke Sampah">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                                            <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                                                                                        </svg>
                                                                                    </button>
                                                                                @endif
                                                                            @endif
                                                                        </div>
                                                                    </td>
                           </td>
                                                                </tr>
                                                                    {{-- Expanded Inspection Area (Dark Shadcn Minimalist) --}}
                                                                    @if($inspectTrxId === $trx->id && $inspectTrx)
                                                                        <tr
                                                                            class="bg-background animate-in fade-in slide-in-from-top-1 duration-300">
                                                                            <td colspan="8" class="p-0 border-none">
                                                                                <div class="p-6 md:p-8 bg-background border-b border-border shadow-inner">
                                                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12 items-start">

                                                                                        {{-- Col 1: Customer (Theme Aware) --}}
                                                                                        <div class="space-y-6">
                                                                                            <div>
                                                                                                <p class="text-[11px] font-bold text-muted-foreground mb-3 uppercase tracking-wider">Informasi
                                                                                                    penyewa</p>
                                                                                                <div class="space-y-1">
                                                                                                    <h4
                                                                                                        class="text-base font-bold text-foreground tracking-tight">
                                                                                                        {{ $inspectTrx->nama }}</h4>
                                                                                                    <p
                                                                                                        class="text-xs text-muted-foreground font-medium">
                                                                                                        {{ $inspectTrx->nik }}</p>
                                                                                                </div>
                                                                                                <div class="mt-4">
                                                                                                    <a href="https://wa.me/{{ $inspectTrx->no_wa }}"
                                                                                                        target="_blank"
                                                                                                        class="inline-flex items-center gap-2 text-xs font-bold text-primary hover:text-primary/80 transition-colors">
                                                                                                        <div
                                                                                                            class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                                                                                height="14" viewBox="0 0 24 24" fill="none"
                                                                                                                stroke="currentColor" stroke-width="2.5"
                                                                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                                                                <path
                                                                                                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                                                                                            </svg>
                                                                                                        </div>
                                                                                                        <span>{{ $inspectTrx->no_wa }}</span>
                                                                                                    </a>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>

                                                                                        {{-- Col 2: Units & Time (Theme Aware) --}}
                                                                                        <div
                                                                                            class="space-y-6 md:border-l border-border md:pl-12">
                                                                                            <div>
                                                                                                <p class="text-[11px] font-bold text-muted-foreground mb-3 uppercase tracking-wider">Detail sewa
                                                                                                </p>
                                                                                                <div class="space-y-2">
                                                                                                    <div class="flex items-center gap-3 text-xs">
                                                                                                        <span class="text-muted-foreground w-12 shrink-0">Mulai</span>
                                                                                                        <span
                                                                                                            class="text-foreground font-semibold">{{ $inspectTrx->waktu_mulai->format('d M Y, H:i') }}</span>
                                                                                                    </div>
                                                                                                    <div class="flex items-center gap-3 text-xs">
                                                                                                        <span class="text-muted-foreground w-12 shrink-0">Selesai</span>
                                                                                                        <span
                                                                                                            class="text-foreground font-semibold">{{ $inspectTrx->waktu_selesai->format('d M Y, H:i') }}</span>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div>
                                                                                                <p class="text-[11px] font-bold text-muted-foreground mb-3 uppercase tracking-wider">Unit
                                                                                                    terdaftar</p>
                                                                                                <div
                                                                                                    class="max-h-[90px] overflow-y-auto pr-2 flex flex-wrap gap-2 scrollbar-hide">
                                                                                                    @foreach($inspectTrx->units as $u)
                                                                                                        <x-ui.badge variant="outline"
                                                                                                            class="border-border text-muted-foreground bg-background hover:bg-muted">
                                                                                                            {{ $u->seri }}
                                                                                                        </x-ui.badge>
                                                                                                    @endforeach
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>

                                                                                        {{-- Col 3: Financials (Theme Aware) --}}
                                                                                        <div
                                                                                            class="space-y-6 md:border-l border-border md:pl-12">
                                                                                            <p class="text-[11px] font-bold text-muted-foreground mb-3 uppercase tracking-wider">Keuangan</p>
                                                                                            <div class="space-y-3">
                                                                                                {{-- Harga Dasar --}}
                                                                                                <div class="flex justify-between items-center text-xs">
                                                                                                    <span class="text-muted-foreground">Harga Dasar</span>
                                                                                                    <span class="font-semibold text-foreground">Rp {{ number_format($inspectTrx->subtotal_harga, 0, ',', '.') }}</span>
                                                                                                </div>
                                                                                                
                                                                                                {{-- Biaya Bank --}}
                                                                                                @php 
                                                                                                    $details = $inspectTrx->payment_details;
                                                                                                    $paymentFee = is_array($details) ? ($details['payment_fee'] ?? 0) : data_get($details, 'payment_fee', 0);
                                                                                                @endphp
                                                                                                @if($paymentFee > 0)
                                                                                                    <div class="flex justify-between items-center text-xs">
                                                                                                        <span class="text-muted-foreground">Biaya Bank</span>
                                                                                                        <span class="font-semibold text-foreground">Rp {{ number_format($paymentFee, 0, ',', '.') }}</span>
                                                                                                    </div>
                                                                                                @endif

                                                                                                {{-- Kode Unik --}}
                                                                                                @if($inspectTrx->kode_unik_pembayaran > 0)
                                                                                                    <div class="flex justify-between items-center text-xs">
                                                                                                        <span class="text-muted-foreground">Kode Unik</span>
                                                                                                        <span class="font-semibold text-foreground">Rp {{ number_format($inspectTrx->kode_unik_pembayaran, 0, ',', '.') }}</span>
                                                                                                    </div>
                                                                                                @endif

                                                                                                {{-- Potongan Diskon --}}
                                                                                                @if($inspectTrx->potongan_diskon > 0)
                                                                                                    <div class="flex justify-between items-center text-xs text-destructive font-bold">
                                                                                                        <span>Potongan</span>
                                                                                                        <span>- Rp {{ number_format($inspectTrx->potongan_diskon, 0, ',', '.') }}</span>
                                                                                                    </div>
                                                                                                @endif

                                                                                                @if($inspectTrx->denda > 0 || $inspectTrx->denda_kerusakan > 0)
                                                                                                    <div class="flex justify-between items-center text-xs text-amber-600 font-bold">
                                                                                                        <span>Total Denda</span>
                                                                                                        <span>+ Rp {{ number_format($inspectTrx->denda + $inspectTrx->denda_kerusakan, 0, ',', '.') }}</span>
                                                                                                    </div>
                                                                                                @endif
                                                                                                <div
                                                                                                    class="pt-4 border-t border-border flex justify-between items-end">
                                                                                                    <span class="text-[11px] font-bold text-muted-foreground">Grand
                                                                                                        total</span>
                                                                                                    <span class="text-xl font-black text-primary ">Rp {{ number_format($inspectTrx->grand_total, 0, ',', '.') }}</span>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            </div>

                                                                                                                        </div>

                                                                                                                        {{-- Footer: Actions (Theme Aware) --}}
                                                                                                                        <div class="flex flex-col md:flex-row md:items-center justify-between mt-10 pt-6 border-t border-border gap-4">
                                                                                                                            <div class="flex flex-wrap items-center gap-3">
                                                                                                                                @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                                                                    @if($inspectTrx->status === 'pending')
                                                                                                                                        <x-ui.button wire:click="markAsPaid({{ $inspectTrx->id }})" wire:confirm="Validasi?" variant="primary" size="sm" class="px-8 shadow-lg shadow-primary/20">Validasi Pembayaran</x-ui.button>
                                                                                                                                        <x-ui.button wire:click="cancel({{ $inspectTrx->id }})" wire:confirm="Batal?" variant="destructive" size="sm" class="px-6">Batalkan</x-ui.button>
                                                                                                                                    @elseif($inspectTrx->status === 'paid')
                                                                                                                                        <x-ui.button wire:click="openDendaModal({{ $inspectTrx->id }})" variant="primary" size="sm" class="px-8 shadow-lg shadow-primary/20">Selesaikan Sewa</x-ui.button>
                                                                                                                                    @endif
                                                                                                                                    <x-ui.button wire:click="editTrx({{ $inspectTrx->id }})" variant="outline" size="sm">Edit Transaksi</x-ui.button>
                                                                                                                                    
                                                                                                                                    {{-- New Invoice Button --}}
                                                                                                                                    <a href="{{ route('public.success', $inspectTrx->booking_code) }}" target="_blank"
                                                                                                                                       class="inline-flex items-center gap-2 px-6 py-2 rounded-md bg-secondary text-secondary-foreground text-xs font-bold hover:bg-secondary/80 transition-colors shadow-sm">
                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                                                                                                                                        Invoice
                                                                                                                                    </a>

                                                                                                                                    <button wire:click="deleteRow({{ $inspectTrx->id }})"
                                                                                                                                        wire:confirm="Hapus transaksi ini secara permanen?"
                                                                                                                                        class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                                                                                            <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                                                                                                        </svg>
                                                                                                                                        Hapus Data
                                                                                                                                    </button>
                                                                                                                                @else
                                                                                                                                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-widest border border-dashed border-border px-3 py-1.5 rounded-lg">Mode Viewer (Read Only)</span>
                                                                                                                                @endif
                                                                                                                            </div>
                                                                                                                            <button wire:click="closeInspect" class="text-xs font-bold text-muted-foreground hover:text-foreground transition-colors flex items-center gap-2">
                                                                                                                                Tutup Detail
                                                                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                                                                                                                            </button>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </td>
                                                                                                            </tr>
                                                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 text-center text-sm text-muted-foreground">Belum ada
                                            transaksi penyewaan yang masuk.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 flex items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-3">
                            <label for="perPage" class="text-xs font-bold text-muted-foreground uppercase tracking-widest leading-none">Rows per page</label>
                            <div class="relative">
                                <select wire:model.live="perPage" id="perPage"
                                    class="h-9 w-20 appearance-none rounded-md border border-input bg-background pl-3 pr-8 text-sm font-bold shadow-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Previous Page --}}
                            @if ($transactions->onFirstPage())
                                <button class="h-9 w-9 flex items-center justify-center rounded-md border border-input bg-background opacity-50 cursor-not-allowed text-muted-foreground shadow-sm" disabled>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                            @else
                                <button wire:click="previousPage" wire:loading.attr="disabled"
                                    class="h-9 w-9 flex items-center justify-center rounded-md border border-input bg-background text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                            @endif

                            <div class="flex items-center gap-1.5 px-3">
                                <span class="text-xs font-black text-foreground">{{ $transactions->currentPage() }}</span>
                                <span class="text-xs font-medium text-muted-foreground/50">/</span>
                                <span class="text-xs font-bold text-muted-foreground">{{ $transactions->lastPage() }}</span>
                            </div>

                            {{-- Next Page --}}
                            @if ($transactions->hasMorePages())
                                <button wire:click="nextPage" wire:loading.attr="disabled"
                                    class="h-9 w-9 flex items-center justify-center rounded-md border border-input bg-background text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            @else
                                <button class="h-9 w-9 flex items-center justify-center rounded-md border border-input bg-background opacity-50 cursor-not-allowed text-muted-foreground shadow-sm" disabled>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Denda Modal -->
        @if($completingTrxId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-background rounded-xl p-6 shadow-xl w-full max-w-md border border-border">
                    <h3 class="text-lg font-bold mb-1 text-foreground">Penyelesaian Transaksi</h3>
                    <p class="text-[11px] text-muted-foreground mb-1 leading-relaxed italic">Catat jika ada denda tambahan
                        sebelum menutup pesanan.</p>
                    <div
                        class="mb-4 inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200/50 dark:border-amber-900/50 text-[10px] font-bold uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        Durasi: {{ $lateDurationText }}
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[11px] font-bold uppercase  text-muted-foreground mb-1">Denda
                                    Keterlambatan</label>
                                <input type="number" wire:model.live="dendaAmount" min="0"
                                    class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                    placeholder="0">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-bold uppercase  text-muted-foreground mb-1">Denda
                                    Kerusakan</label>
                                <input type="number" wire:model.live="dendaKerusakanAmount" min="0"
                                    class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                    placeholder="0">
                            </div>
                        </div>

                        @if($dendaKerusakanAmount > 0)
                            <div>
                                <label
                                    class="block text-[11px] font-bold uppercase  text-muted-foreground mb-1">Keterangan
                                    Kerusakan</label>
                                <textarea wire:model="catatanKerusakan" rows="2"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-1 focus:ring-primary outline-none"
                                    placeholder="Contoh: Layar retak, Kabel hilang..."></textarea>
                            </div>
                        @endif

                        @if($dendaAmount > 0 || $dendaKerusakanAmount > 0)
                            <div class="rounded-lg bg-muted/30 p-4 border border-border">
                                <label
                                    class="block text-[11px] font-bold uppercase  text-muted-foreground mb-3">Metode
                                    Pembayaran Denda</label>
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <label
                                        class="relative flex cursor-pointer rounded-lg border bg-background p-3 shadow-sm focus:outline-none hover:border-primary/50 transition-colors {{ $dendaMethod === 'cash' ? 'border-primary ring-1 ring-primary' : 'border-border' }}">
                                        <input type="radio" wire:model.live="dendaMethod" value="cash" class="sr-only">
                                        <span class="flex flex-1 items-center justify-center">
                                            <span
                                                class="font-medium {{ $dendaMethod === 'cash' ? 'text-primary' : 'text-foreground' }}">Tunai</span>
                                        </span>
                                    </label>
                                    <label
                                        class="relative flex cursor-pointer rounded-lg border bg-background p-3 shadow-sm focus:outline-none hover:border-primary/50 transition-colors {{ $dendaMethod === 'qris' ? 'border-primary ring-1 ring-primary' : 'border-border' }}">
                                        <input type="radio" wire:model.live="dendaMethod" value="qris" class="sr-only">
                                        <span class="flex flex-1 items-center justify-center">
                                            <span
                                                class="font-medium {{ $dendaMethod === 'qris' ? 'text-primary' : 'text-foreground' }}">QRIS</span>
                                        </span>
                                    </label>
                                </div>

                                @if($dendaMethod === 'qris')
                                                                <div class="space-y-4 pt-4 border-t border-border/50">
                                                                    <div class="text-center">
                                                                        <p class="text-[10px] text-muted-foreground uppercase font-bold  mb-1">
                                                                            Total Denda Bayar</p>
                                                                        <p class="text-2xl font-black text-primary">Rp {{ number_format((int) $dendaAmount +
                                    (int) $dendaKerusakanAmount, 0, ',', '.') }}</p>
                                                                        <p class="text-[10px] text-red-500 font-medium mt-1 uppercase italic">* TANPA KODE UNIK
                                                                        </p>
                                                                    </div>
                                                                    <div class="flex justify-center">
                                                                        <div class="p-2 bg-white rounded-lg shadow-inner border border-zinc-200">
                                                                            <img src="{{ asset('uploads/' . \App\Models\Setting::getVal('qris', 'default.jpg')) }}"
                                                                                class="w-48 h-48 object-contain">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-ui.button wire:click="closeDendaModal" variant="outline" size="sm" class="rounded-full px-6">
                            Batalkan
                        </x-ui.button>
                        <x-ui.button wire:click="confirmDenda"
                            wire:loading.attr="disabled"
                            wire:target="confirmDenda"
                            variant="success" size="sm" class="w-[180px]">
                            <span wire:loading.remove wire:target="confirmDenda">Selesaikan & Tagih</span>
                            <span wire:loading wire:target="confirmDenda" class="flex items-center gap-2">
                                <span class="h-3 w-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                Memproses...
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Transaction Modal -->
        @if($isEditingTrx)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                <div
                    class="bg-background rounded-xl shadow-2xl w-full max-w-2xl border border-border flex flex-col max-h-[90vh]">
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-bold flex flex-col">
                            <span class="text-primary">{{ $this->isEditingTrx ? \App\Models\Rental::find($editTrxId)?->booking_code : '' }}</span>
                            <span class="text-[10px] font-medium text-muted-foreground uppercase leading-none mt-1">Sistem ID: {{ $editTrxId }}</span>
                        </h3>
                        <button wire:click="closeEditModal" class="text-muted-foreground hover:text-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Customer Info -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold uppercase  text-primary">Data Pelanggan</h4>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">Nama Lengkap</label>
                                    <input type="text" wire:model="edit_nama"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">NIK</label>
                                    <input type="text" wire:model="edit_nik"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">No. WhatsApp</label>
                                    <input type="text" wire:model="edit_no_wa"
                                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">Alamat</label>
                                    <textarea wire:model="edit_alamat" rows="2"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-1 focus:ring-primary outline-none"></textarea>
                                </div>
                            </div>

                            <!-- Rental Details -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold uppercase  text-primary">Detail Sewa & Biaya</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Waktu
                                            Mulai</label>
                                        <input type="datetime-local" wire:model="edit_waktu_mulai"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Waktu
                                            Selesai</label>
                                        <input type="datetime-local" wire:model="edit_waktu_selesai"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Subtotal
                                            (Rp)</label>
                                        <input type="number" wire:model="edit_subtotal"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Potongan Diskon
                                            (Rp)</label>
                                        <input type="number" wire:model="edit_diskon"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Denda Telat
                                            (Rp)</label>
                                        <input type="number" wire:model="edit_denda"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Denda Rusak
                                            (Rp)</label>
                                        <input type="number" wire:model="edit_denda_kerusakan"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">Keterangan
                                        Kerusakan</label>
                                    <textarea wire:model="edit_catatan_kerusakan" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-1 focus:ring-primary outline-none"
                                        placeholder="Catatan kerusakan..."></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Status</label>
                                        <select wire:model="edit_status"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                            <option value="pending">Pending</option>
                                            <option value="paid">Lunas</option>
                                            <option value="completed">Selesai</option>
                                            <option value="cancelled">Batal</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Metode
                                            Bayar</label>
                                        <select wire:model="edit_metode_pembayaran"
                                            class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm focus:ring-1 focus:ring-primary outline-none">
                                            <option value="CASH">CASH</option>
                                            <option value="QRIS">QRIS</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t border-border flex justify-end gap-3 bg-muted/20">
                        <x-ui.button wire:click="closeEditModal" variant="outline" size="sm" class="rounded-full px-6">
                            Batal
                        </x-ui.button>
                        <x-ui.button wire:click="updateTrx" variant="success" size="sm" class="px-8">
                            Simpan Perubahan
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>