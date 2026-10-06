<div>
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-[100] bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg"
            x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ session('message') }}
        </div>
    @endif
    <div>
        <div class="flex items-center justify-end mb-4">
            <button wire:click="exportCsv"
                class="inline-flex items-center gap-1.5 justify-center rounded-xl bg-secondary/80 hover:bg-secondary text-secondary-foreground shadow-xs h-8 px-3.5 text-xs font-semibold transition-all active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" x2="12" y1="15" y2="3" />
                </svg>
                <span>Export CSV</span>
            </button>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 items-end sm:items-center justify-between">
            <div class="flex flex-1 flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <div class="relative flex-1 max-w-sm group">
                    <div
                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full h-9 pl-10 pr-10 text-sm rounded-md border border-input bg-background shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Cari nama, invoice, atau WA...">
                    
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    @endif
                </div>

                <div class="hidden sm:flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-end">
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Mulai</label>
                        <input type="date" wire:model.live="dateStart"
                            class="h-9 w-full sm:w-[140px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Hingga</label>
                        <input type="date" wire:model.live="dateEnd"
                            class="h-9 w-full sm:w-[140px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Status</label>
                        <select wire:model.live="filterStatus"
                            class="h-9 w-full sm:w-[150px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">Semua</option>
                            <option value="pending">Pending (Auto)</option>
                            <option value="pending_confirmation">Verifikasi (Manual)</option>
                            <option value="paid">Paid</option>
                            <option value="renting">Rent</option>
                            <option value="completed">Done</option>
                            <option value="cancelled">Cancel</option>
                            <option value="trashed">Trashed</option>
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
                                                                                                                                                                                                                                            class="cursor-pointer hover:bg-muted/40 transition-colors group/row {{ $trx->status === 'cancelled' ? 'opacity-40' : '' }}">
                                                                                                                                                                                                                                            <td class="whitespace-nowrap py-3 pl-3 pr-3 text-xs sm:pl-6">
                                                                                                                                                                                                                                                <div class="flex flex-col gap-1 tracking-tight">
                                                                                                                                                                                                                                                    <div class="font-bold text-foreground text-sm tracking-tight leading-none truncate max-w-[120px] sm:max-w-[180px]" title="{{ $trx->nama }}">{{ \Illuminate\Support\Str::limit($trx->nama, 25) }}</div>
                                                                                                                                                                                                                                                    <div class="flex items-center gap-2">
                                <span 
                                    x-data="{ copied: false }"
                                    @click.stop="navigator.clipboard.writeText('{{ $trx->booking_code }}'); copied = true; setTimeout(() => copied = false, 200)"
                                    class="relative inline-flex items-center rounded border px-1.5 py-0.5 font-mono text-[9px] font-bold uppercase tracking-tight cursor-pointer transition-all duration-200"
                                    :class="copied ? 'bg-primary text-primary-foreground border-primary' : 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200/50 dark:border-sky-900/50'">
                                    {{ $trx->booking_code }}
                                </span>
                                                                                                                                                                                                                                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $trx->no_wa) }}"
                                                                                                                                                                                                                                                            target="_blank" wire:click.stop class="text-[10px] text-muted-foreground font-semibold hover:text-primary transition-colors tracking-tight truncate max-w-[90px] inline-block" title="{{ $trx->no_wa }}">{{ \Illuminate\Support\Str::limit($trx->no_wa, 15) }}</a>
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
                                                                                                                                                                                                                                                            class="font-medium text-foreground text-xs leading-none truncate max-w-[80px] sm:max-w-[120px]" title="{{ $u->seri }}">{{ \Illuminate\Support\Str::limit($u->seri, 20) }}</span>
                                                                                                                                                                                                                                                    @endforeach
                                                                                                                                                                                                                                                    @if($trx->units->count() > 2)
                                                                                                                                                                                                                                                        <span
                                                                                                                                                                                                                                                            class="text-[9px] text-muted-foreground mt-0.5">+{{ $trx->units->count() - 2 }}</span>
                                                                                                                                                                                                                                                    @endif
                                                                                                                                                                                                                                                    @if($trx->units->isEmpty() && $trx->unit)
                                                                                                                                                                                                                                                        <span
                                                                                                                                                                                                                                                            class="font-medium text-foreground text-xs leading-none truncate max-w-[80px] sm:max-w-[120px]" title="{{ $trx->unit->seri }}">{{ \Illuminate\Support\Str::limit($trx->unit->seri, 20) }}</span>
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
                                                                                                                                                                                                                                                class="hidden sm:table-cell whitespace-nowrap px-3 py-1.5 text-sm font-bold text-foreground leading-none">
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
                                                                                                                                                                                                                                                @if($trx->status === 'pending' || $trx->status === 'pending_confirmation')
                                                                                                                                                                                                                                                    <x-ui.badge variant="amber" class="text-[9px]">{{ $trx->status === "pending_confirmation" ? "Verifikasi" : "Pending" }}</x-ui.badge>
                                                                                                                                                                                                                                                @elseif($trx->status === 'paid')
                                                                                                                                                                                                                                                    <x-ui.badge variant="blue" class="text-[9px]">Paid</x-ui.badge>
                                                                                                                                                                                                                                                @elseif($trx->status === 'renting')
                                                                                                                                                                                                                                                    <x-ui.badge variant="emerald" class="text-[9px]">Rent</x-ui.badge>
                                                                                                                                                                                                                                                @elseif($trx->status === 'completed')
                                                                                                                                                                                                                                                    <x-ui.badge variant="green" class="text-[9px]">Done</x-ui.badge>
                                                                                                                                                                                                                                                @else
                                                                                                                                                                                                                                                    <x-ui.badge variant="red" class="text-[9px]">Cancel</x-ui.badge>
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
                                                                                                                                                                                                                                                        @if($trx->status === 'pending' || $trx->status === 'pending_confirmation')
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
                                                                                                                                                                                                                                                            @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                                                                                                                                                                                                <x-ui.button wire:click.stop="handover({{ $trx->id }})"
                                                                                                                                                                                                                                                                    wire:confirm="Validasi ambil unit sekarang?"
                                                                                                                                                                                                                                                                    wire:loading.attr="disabled"
                                                                                                                                                                                                                                                                    wire:target="handover({{ $trx->id }})" variant="default" size="sm" class="gap-1.5 shadow-lg shadow-sky-500/10">
                                                                                                                                                                                                                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                                                                                                                                                                                                                                                                    Validasi Ambil
                                                                                                                                                                                                                                                                </x-ui.button>
                                                                                                                                                                                                                                                            @endif
                                                                                                                                                                                                                                                        @elseif($trx->status === 'renting')
                                                                                                                                                                                                                                                            @php
                                                                                                                                                                                                                                                                $tolerance = (int) \App\Models\Setting::getVal('late_tolerance_minutes', 60);
                                                                                                                                                                                                                                                                $isLate = (\Carbon\Carbon::parse($trx->waktu_selesai)->addMinutes($tolerance) < now());
                                                                                                                                                                                                                                                            @endphp
                                                                                                                                                                                                                                                            @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                                                                                                                                                                                                {{-- Tombol Cepat Perpanjang --}}
                                                                                                                                                                                                                                                                <button wire:click.stop="openExtendModal({{ $trx->id }})"
                                                                                                                                                                                                                                                                    wire:loading.attr="disabled"
                                                                                                                                                                                                                                                                    wire:target="openExtendModal({{ $trx->id }})"
                                                                                                                                                                                                                                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 transition-all active:scale-95 shadow-sm shadow-amber-500/5 mr-1.5">
                                                                                                                                                                                                                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                                                                                                                                                                                                                                                    <span>Perpanjang</span>
                                                                                                                                                                                                                                                                </button>
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
                                                                                                                                                                                                                                                                    Validasi Pengembalian
                                                                                                                                                                                                                                                                </x-ui.button>
                                                                                                                                                                                                                                                            @endif
                                                                                                                                                                                                                                                        @endif

                                                                                                                                                                                                                                @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                                                                                                                                                                    <button wire:click.stop="editTrx({{ $trx->id }})"
                                                                                                                                                                                                                                        wire:loading.attr="disabled"
                                                                                                                                                                                                                                        wire:target="editTrx({{ $trx->id }})"
                                                                                                                                                                                                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-muted/70 hover:bg-muted text-foreground border border-border transition-all active:scale-95 shadow-sm mr-1">
                                                                                                                                                                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                                                                                                                                                                                                                        <span>Edit</span>
                                                                                                                                                                                                                                    </button>
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
                                                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                                                            {{-- Expanded Inspection Area (Dark Shadcn Minimalist) --}}
                                                                                                                                                                                                                                            @if($inspectTrxId === $trx->id && $inspectTrx)
                                                                                                                                                                                                                                                                            <tr
                                                                                                                                                                                                                                                                                class="bg-background animate-in fade-in slide-in-from-top-1 duration-300">
                                                                                                                                                                                                                                                                                <td colspan="8" class="p-0 border-none">
                                                                                                                                                                                                                                                                                    <div class="p-6 md:p-8 bg-background border-b border-border shadow-inner">
                                                                                                                                                                                                                                                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12 items-start">

                                                                                                                                                                                                                                                                                            {{-- Col 1: Customer --}}
                                                                                                                                                                                                                                                                                            <div class="space-y-4">
                                                                                                                                                                                                                                                                                                <div>
                                                                                                                                                                                                                                                                                                    <h4 class="text-lg font-bold text-foreground tracking-tight">{{ $inspectTrx->nama }}</h4>
                                                                                                                                                                                                                                                                                                    <p class="text-xs text-muted-foreground mt-0.5 cursor-pointer hover:text-primary transition-colors" 
                                                                                                                                                                                                                                                                          x-data="{ copied: false }" 
                                                                                                                                                                                                                                                                          @click="navigator.clipboard.writeText('{{ $inspectTrx->no_wa }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                                                                                                                                                                                                                                          :title="copied ? 'Copied!' : 'Click to copy WA'">
                                                                                                                                                                                                                                                                          <span x-show="!copied">{{ $inspectTrx->no_wa }}</span>
                                                                                                                                                                                                                                                                          <span x-show="copied" class="text-primary font-bold animate-in fade-in zoom-in duration-200">Copied!</span>
                                                                                                                                                                                                                                                                      </p>
                                                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                                                <div class="space-y-2 pt-2 border-t border-border/50">
                                                                                                                                                                                                                                                                                                    <div class="flex items-center gap-3 text-xs">
                                                                                                                                                                                                                                                                                                        <span class="text-muted-foreground w-24">WhatsApp</span>
                                                                                                                                                                                                                                                                                                        <a href="https://wa.me/{{ $inspectTrx->no_wa }}" target="_blank" class="text-primary font-bold hover:underline">
                                                                                                                                                                                                                                                                                                            {{ $inspectTrx->no_wa }}
                                                                                                                                                                                                                                                                                                        </a>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                                                            {{-- Col 2: Units & Time --}}
                                                                                                                                                                                                                                                                                            <div class="space-y-4 md:border-l border-border/50 md:pl-12">
                                                                                                                                                                                                                                                                                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest opacity-50">Detail Sewa</p>
                                                                                                                                                                                                                                                                                                <div class="space-y-2">
                                                                                                                                                                                                                                                                                                    <div class="flex items-center gap-3 text-xs">
                                                                                                                                                                                                                                                                                                        <span class="text-muted-foreground w-16">Mulai</span>
                                                                                                                                                                                                                                                                                                        <span class="text-foreground font-semibold">{{ $inspectTrx->waktu_mulai->format('d M Y, H:i') }}</span>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                    <div class="flex items-center gap-3 text-xs">
                                                                                                                                                                                                                                                                                                        <span class="text-muted-foreground w-16">Selesai</span>
                                                                                                                                                                                                                                                                                                        <span class="text-foreground font-semibold">{{ $inspectTrx->waktu_selesai->format('d M Y, H:i') }}</span>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                    <div class="pt-2">
                                                                                                                                                                                                                                                                                                        <div class="flex flex-wrap gap-1.5">
                                                                                                                                                                                                                                                                                                            @foreach($inspectTrx->units as $u)
                                                                                                                                                                                                                                                                                                                <span class="inline-flex items-center rounded-md border border-border bg-muted/30 px-2 py-0.5 text-[10px] font-semibold text-foreground">
                                                                                                                                                                                                                                                                                                                    {{ $u->seri }}

                                                                                                                                                                                                                                                                                                                        <span class="ml-1 opacity-60 font-mono text-[9px] tracking-tighter">
                                                                                                                                                                                                                                                                                                                            [#{{ str_pad($u->id, 3, '0', STR_PAD_LEFT) }}]
                                                                                                                                                                                                                                                                                                                        </span>

                                                                                                                                                                                                                                                                                                                </span>
                                                                                                                                                                                                                                                                                                            @endforeach
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                                                            {{-- Col 3: Financials --}}
                                                                                                                                                                                                                                                                                            <div class="space-y-4 md:border-l border-border/50 md:pl-12">
                                                                                                                                                                                                                                                                                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest opacity-50">Rincian Pembayaran</p>
                                                                                                                                                                                                                                                                                                <div class="space-y-2.5">
                                                                                                                                                                                                                                                                                                    <div class="flex justify-between items-center text-xs">
                                                                                                                                                                                                                                                                                                        <span class="text-muted-foreground">Harga Dasar</span>
                                                                                                                                                                                                                                                                                                        <span class="font-medium text-foreground">Rp {{ number_format($inspectTrx->subtotal_harga, 0, ',', '.') }}</span>
                                                                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                                                                    @php 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        $details = $inspectTrx->payment_details;
                                                                                                                                                                                                                                                                                                        $paymentFee = is_array($details) ? ($details['payment_fee'] ?? 0) : data_get($details, 'payment_fee', 0);
                                                                                                                                                                                                                                                                                                    @endphp

                                                                                                                                                                                                                                                                                                    @if($paymentFee > 0)
                                                                                                                                                                                                                                                                                                        <div class="flex justify-between items-center text-xs">
                                                                                                                                                                                                                                                                                                            <span class="text-muted-foreground">Biaya Bank</span>
                                                                                                                                                                                                                                                                                                            <span class="font-medium text-foreground">Rp {{ number_format($paymentFee, 0, ',', '.') }}</span>
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    @endif

                                                                                                                                                                                                                                                                                                    @if($inspectTrx->kode_unik_pembayaran > 0)
                                                                                                                                                                                                                                                                                                        <div class="flex justify-between items-center text-xs">
                                                                                                                                                                                                                                                                                                            <span class="text-muted-foreground">Kode Unik</span>
                                                                                                                                                                                                                                                                                                            <span class="font-medium text-foreground">Rp {{ number_format($inspectTrx->kode_unik_pembayaran, 0, ',', '.') }}</span>
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    @endif

                                                                                                                                                                                                                                                                                                    @if($inspectTrx->potongan_diskon > 0)
                                                                                                                                                                                                                                                                                                        <div class="flex justify-between items-center text-xs text-red-500 font-medium">
                                                                                                                                                                                                                                                                                                            <span>Diskon</span>
                                                                                                                                                                                                                                                                                                            <span>- Rp {{ number_format($inspectTrx->potongan_diskon, 0, ',', '.') }}</span>
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    @endif

                                                                                                                                                                                                                                                                                                    <div class="pt-3 border-t border-border/50 flex justify-between items-baseline">
                                                                                                                                                                                                                                                                                                        <span class="text-[10px] font-bold text-muted-foreground/60 uppercase">Grand Total</span>
                                                                                                                                                                                                                                                                                                        <span class="text-lg font-black text-foreground">Rp {{ number_format($inspectTrx->grand_total, 0, ',', '.') }}</span>
                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                  </div>

                                                                                                                                                                                                                                                                                                                            {{-- Footer: Actions (Theme Aware) --}}
                                                                                                                                                                                                                                                                                                                            <div class="flex flex-col md:flex-row md:items-center justify-between mt-10 pt-6 border-t border-border gap-4">
                                                                                                                                                                                                                                                                                                                                <div class="flex flex-wrap items-center gap-3">
                                                                                                                                                                                                                                                                                                                                    @if(in_array(auth()->user()->role, ['admin', 'staff']))
                                                                                                                                                                                                                                                                                                                                        @if($inspectTrx->status === 'pending' || $inspectTrx->status === 'pending_confirmation')
                                                                                                                                                                                                                                                                                                                                            <x-ui.button wire:click="markAsPaid({{ $inspectTrx->id }})" wire:confirm="Validasi Lunas?" variant="primary" size="sm" class="px-8 shadow-lg shadow-primary/20">Validasi Lunas</x-ui.button>
                                                                                                                                                                                                                                                                                                                                            <x-ui.button wire:click="cancel({{ $inspectTrx->id }})" wire:confirm="Batal?" variant="destructive" size="sm" class="px-6">Batalkan</x-ui.button>
                                                                                                                                                                                                                                                                                                                                         @elseif($inspectTrx->status === "paid")
                                                                                                                                                                                                                                                                                                                                             <x-ui.button wire:click="handover({{ $inspectTrx->id }})" wire:confirm="Validasi ambil unit?" variant="primary" size="sm" class="px-8 shadow-lg shadow-primary/20">Validasi Ambil</x-ui.button>
                                                                                                                                                                                                                                                                                                                                         @elseif($inspectTrx->status === "renting")
                                                                                                                                                                                                                                                                                                                                             <button wire:click="openExtendModal({{ $inspectTrx->id }})" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition-colors shadow-sm shadow-amber-500/20"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Perpanjang Sewa</button>
                                                                                                                                                                                                                                                <x-ui.button wire:click="openDendaModal({{ $inspectTrx->id }})" variant="primary" size="sm" class="px-8 shadow-lg shadow-primary/20">Validasi Pengembalian</x-ui.button>
                                                                                                                                                                                                                                                                                                                                         @endif
                                                                                                                                                                                                                                                                                                                                        <x-ui.button wire:click="editTrx({{ $inspectTrx->id }})" variant="outline" size="sm">Edit Transaksi</x-ui.button>

                                                                                                                                                                                                                                                                                                                                        {{-- New Invoice Button --}}
                                                                                                                                                                                                                                                                                                                                        <a href="{{ route('public.success', $inspectTrx->booking_code) }}" target="_blank"
                                                                                                                                                                                                                                                                                                                                           class="inline-flex items-center gap-2 px-6 py-2 rounded-md bg-secondary text-secondary-foreground text-xs font-bold hover:bg-secondary/80 transition-colors shadow-sm">
                                                                                                                                                                                                                                                                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                                                                                                                                                                                                                                                                                                                                            Invoice
                                                                                                                                                                                                                                                                                                                                        </a>
                                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                                                                        {{-- WA Confirmation Button --}}
                                                                                                                                                                                                                                                                                                                                        @php
                                                                                                                                                                                                                                                                                                                                            $waText = "Permisi, konfirmasi pesanan Kakak.\n\nLink Invoice: " . route('public.success', $inspectTrx->booking_code) . "\n\nApakah detail pesanan tersebut sudah benar?";
                                                                                                                                                                                                                                                                                                                                            $waUrl = "https://wa.me/" . \App\Helpers\CustomerHelper::formatWa($inspectTrx->no_wa) . "?text=" . rawurlencode($waText);
                                                                                                                                                                                                                                                                                                                                        @endphp
                                                                                                                                                                                                                                                                                                                                        <a href="{{ $waUrl }}" target="_blank"
                                                                                                                                                                                                                                                                                                                                           class="inline-flex items-center gap-2 px-6 py-2 rounded-md bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500 transition-colors shadow-sm shadow-emerald-600/10">
                                                                                                                                                                                                                                                                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-0.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                                                                                                                                                                                                                                                                                                            Konfirmasi WA
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
                    <h3 class="text-lg font-bold mb-1 text-foreground">Validasi Pengembalian Unit</h3>
                    <p class="text-[11px] text-muted-foreground mb-1 leading-relaxed italic">Catat jika ada denda tambahan
                        sebelum menutup pesanan.</p>
                    <div class="mb-6 flex items-center justify-between p-4 rounded-2xl {{ $isOverdue ? 'bg-rose-500/5 border border-rose-500/10' : 'bg-emerald-500/5 border border-emerald-500/10' }}">
                        <div class="flex flex-col">
                            <p class="text-[8px] font-black {{ $isOverdue ? 'text-rose-600' : 'text-emerald-600' }} tracking-widest uppercase mb-1">
                                {{ $isOverdue ? 'Telat' : 'Sisa Waktu' }}
                            </p>
                            <p class="text-2xl font-black {{ $isOverdue ? 'text-rose-600' : 'text-emerald-600' }} font-mono tracking-tighter leading-none">
                                {{ $lateDurationText }}
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-xl {{ $isOverdue ? 'bg-rose-500/10 text-rose-500' : 'bg-emerald-500/10 text-emerald-500' }} flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
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
                                                                                                                                                                                                <p class="text-2xl font-black text-foreground">Rp {{ number_format((int) $dendaAmount +
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
                            Batal
                        </x-ui.button>
                        <x-ui.button wire:click="confirmDenda"
                            wire:loading.attr="disabled"
                            wire:target="confirmDenda"
                            variant="success" size="sm" class="w-[180px]">
                            <span wire:loading.remove wire:target="confirmDenda">Validasi & Selesaikan</span>
                            <span wire:loading wire:target="confirmDenda" class="flex items-center gap-2">
                                <span class="h-3 w-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                Memproses...
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif

        
        <!-- Quick Extend Modal (Perpanjang Sewa) -->
        @if($isExtendingTrx)
            @php
                $currTrx = \App\Models\Rental::with('units')->find($extendTrxId);
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-3 sm:p-4 overscroll-contain animate-in fade-in duration-200"
                 x-data
                 x-init="document.body.classList.add('overflow-hidden')"
                 x-destroy="document.body.classList.remove('overflow-hidden')">
                <div class="bg-background rounded-2xl shadow-2xl w-full max-w-lg border border-border flex flex-col max-h-[85vh] sm:max-h-[88vh] overflow-hidden my-auto animate-in zoom-in-95 duration-150">
                    
                    {{-- Header (Fixed, aman dari notch HP) --}}
                    <div class="px-3.5 py-3 sm:p-4 border-b border-border flex items-center justify-between bg-muted/20 shrink-0">
                        <div class="flex items-center gap-2 min-w-0 pr-2">
                            <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xs sm:text-sm font-bold text-foreground flex items-center gap-1.5 flex-wrap leading-tight">
                                    <span>Perpanjang Sewa</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono font-semibold">
                                        {{ $currTrx?->booking_code }}
                                    </span>
                                </h3>
                                <p class="text-[10px] text-muted-foreground truncate max-w-[180px] sm:max-w-xs mt-0.5">
                                    {{ $currTrx?->nama }} &bull; {{ $currTrx?->units->pluck('seri')->implode(', ') }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="closeExtendModal" class="h-7 w-7 rounded-md flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body: Form Input atau Dialog Konfirmasi Diff --}}
                    @if($isConfirmingExtend)
                        {{-- Tampilan Konfirmasi Diff Perpanjang --}}
                        <div class="p-3.5 sm:p-4 overflow-y-auto space-y-3.5 text-xs flex-1 overscroll-contain touch-pan-y" style="-webkit-overflow-scrolling: touch;">
                            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300">
                                <div class="flex items-center gap-1.5 font-bold text-xs mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>Konfirmasi Ringkasan Perpanjangan</span>
                                </div>
                                <p class="text-[11px] leading-relaxed opacity-90">
                                    Periksa perubahan waktu & tagihan di bawah sebelum data disimpan.
                                </p>
                            </div>

                            <div class="rounded-xl border border-border overflow-hidden divide-y divide-border text-[11px]">
                                <div class="p-2.5 bg-muted/30 flex justify-between items-center">
                                    <span class="text-muted-foreground font-medium">Pelanggan & Unit</span>
                                    <span class="font-bold text-foreground text-right">{{ $extendDiffData['nama'] }} ({{ $extendDiffData['unit_names'] }})</span>
                                </div>
                                <div class="p-2.5 flex justify-between items-center">
                                    <span class="text-muted-foreground font-medium">Jadwal Selesai</span>
                                    <div class="text-right">
                                        <div class="line-through text-muted-foreground text-[10px]">{{ $extendDiffData['old_selesai'] }}</div>
                                        <div class="font-bold text-amber-600 dark:text-amber-400">{{ $extendDiffData['new_selesai'] }} (+{{ $extendDiffData['durasi_tambah'] }}j)</div>
                                    </div>
                                </div>
                                <div class="p-2.5 flex justify-between items-center">
                                    <span class="text-muted-foreground font-medium">Sewa Tambahan</span>
                                    <span class="font-mono font-bold text-foreground">Rp {{ number_format($extendDiffData['biaya_tambah'], 0, ',', '.') }}</span>
                                </div>
                                @if($extendDiffData['denda_telat'] > 0)
                                    <div class="p-2.5 flex justify-between items-center">
                                        <span class="text-rose-600 dark:text-rose-400 font-medium">Denda Telat</span>
                                        <span class="font-mono font-bold text-rose-600 dark:text-rose-400">+ Rp {{ number_format($extendDiffData['denda_telat'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($extendDiffData['diskon'] > 0)
                                    <div class="p-2.5 flex justify-between items-center">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">Potongan Diskon</span>
                                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">- Rp {{ number_format($extendDiffData['diskon'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="p-3 bg-muted/40 flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-foreground block text-xs">Total Tagihan Tambahan</span>
                                        <span class="text-[10px] text-muted-foreground">Kekurangan yg ditagih</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-base font-black text-amber-600 dark:text-amber-400 font-mono">
                                            Rp {{ number_format($extendDiffData['total_tambah'], 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Konfirmasi Diff Extend --}}
                        <div class="p-2.5 sm:p-3.5 border-t border-border flex items-center justify-between gap-2 bg-muted/20 shrink-0">
                            <button type="button" wire:click="cancelConfirmExtend" class="px-3 py-1.5 rounded-lg border border-border text-xs font-semibold text-foreground hover:bg-muted">
                                Kembali Edit
                            </button>
                            <button type="button" wire:click="saveExtend"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-md active:scale-95">
                                <span wire:loading wire:target="saveExtend" class="h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>Ya, Simpan Perpanjangan</span>
                            </button>
                        </div>
                    @else
                        {{-- Form Input Perpanjangan --}}
                        <div class="p-3.5 sm:p-4 overflow-y-auto space-y-3.5 text-xs flex-1 overscroll-contain touch-pan-y" style="-webkit-overflow-scrolling: touch;">
                            {{-- Quick Presets --}}
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1.5">Pilihan Durasi</label>
                                <div class="grid grid-cols-4 gap-1.5">
                                    <button type="button" wire:click="$set('extendPreset', '12')"
                                        class="py-1.5 px-1 rounded-lg border text-center font-bold transition-all text-xs flex flex-col items-center justify-center active:scale-95 {{ $extendPreset === '12' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-muted/40 hover:bg-muted border-border text-foreground' }}">
                                        <span>+12 Jam</span>
                                        <span class="text-[9px] font-normal opacity-80">0.5 Hari</span>
                                    </button>
                                    <button type="button" wire:click="$set('extendPreset', '24')"
                                        class="py-1.5 px-1 rounded-lg border text-center font-bold transition-all text-xs flex flex-col items-center justify-center active:scale-95 {{ $extendPreset === '24' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-muted/40 hover:bg-muted border-border text-foreground' }}">
                                        <span>+24 Jam</span>
                                        <span class="text-[9px] font-normal opacity-80">1 Hari</span>
                                    </button>
                                    <button type="button" wire:click="$set('extendPreset', '48')"
                                        class="py-1.5 px-1 rounded-lg border text-center font-bold transition-all text-xs flex flex-col items-center justify-center active:scale-95 {{ $extendPreset === '48' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-muted/40 hover:bg-muted border-border text-foreground' }}">
                                        <span>+2 Hari</span>
                                        <span class="text-[9px] font-normal opacity-80">48 Jam</span>
                                    </button>
                                    <button type="button" wire:click="$set('extendPreset', 'custom')"
                                        class="py-1.5 px-1 rounded-lg border text-center font-bold transition-all text-xs flex flex-col items-center justify-center active:scale-95 {{ $extendPreset === 'custom' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-muted/40 hover:bg-muted border-border text-foreground' }}">
                                        <span>Custom</span>
                                        <span class="text-[9px] font-normal opacity-80">Pilih</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Info Waktu --}}
                            <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-muted/40 border border-border">
                                <div>
                                    <span class="text-[9px] font-bold text-muted-foreground uppercase block">Selesai Lama</span>
                                    <div class="font-mono font-semibold text-foreground text-[11px]">
                                        {{ \Carbon\Carbon::parse($extendCurrentSelesai)->format('d M, H:i') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 uppercase block">Selesai Baru (+{{ $extendHours }}j)</span>
                                    <div class="font-mono font-bold text-amber-600 dark:text-amber-400 text-[11px]">
                                        {{ $extendNewSelesai ? \Carbon\Carbon::parse($extendNewSelesai)->format('d M, H:i') : '-' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Input Custom DateTime Picker --}}
                            @if($extendPreset === 'custom')
                                <div class="p-2.5 bg-amber-500/5 border border-amber-500/20 rounded-xl space-y-1.5">
                                    <label class="block text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">Waktu Selesai Baru</label>
                                    <input type="datetime-local" wire:model.live="extendNewSelesai"
                                        class="w-full h-8 rounded-lg border border-input bg-background px-2.5 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                                    @error('extendNewSelesai')
                                        <span class="text-[10px] text-red-500 font-medium block">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            {{-- Penyesuaian Biaya --}}
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Rincian Tagihan Tambahan</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-bold text-foreground mb-0.5">Biaya Sewa</label>
                                        <div class="relative">
                                            <input type="number" wire:model.live.debounce.300ms="extendBiayaSewa" min="0" step="1000"
                                                class="w-full h-8 px-2 rounded-lg border border-input bg-background text-[11px] font-semibold focus:ring-1 focus:ring-amber-500 outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-rose-600 dark:text-rose-400 mb-0.5">Denda Telat</label>
                                        <input type="number" wire:model.live.debounce.300ms="extendDendaTelat" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-rose-300 dark:border-rose-900 bg-rose-500/5 text-[11px] font-semibold text-rose-600 dark:text-rose-400 focus:ring-1 focus:ring-rose-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-emerald-600 dark:text-emerald-400 mb-0.5">Diskon</label>
                                        <input type="number" wire:model.live.debounce.300ms="extendDiskon" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-emerald-300 dark:border-emerald-900 bg-emerald-500/5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    </div>
                                </div>
                                <input type="text" wire:model="extendCatatan"
                                    placeholder="Catatan perpanjangan (opsional)..."
                                    class="w-full h-7 px-2.5 rounded-lg border border-input bg-background text-[11px] focus:ring-1 focus:ring-amber-500 outline-none">
                            </div>

                            {{-- Total Tagihan Tambahan Card --}}
                            <div class="p-3 rounded-xl bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/20 flex items-center justify-between">
                                <div>
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300 block">Total Tagihan</span>
                                    <span class="text-[10px] text-muted-foreground">Kekurangan customer</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-black text-amber-600 dark:text-amber-400 font-mono leading-none">
                                        Rp {{ number_format($extendTotalTagihan, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Form Input Extend (Ringkas & Sederhana) --}}
                        <div class="p-2.5 sm:p-3.5 border-t border-border flex items-center justify-between gap-1.5 bg-muted/20 shrink-0">
                            <button type="button" wire:click="closeExtendModal" class="px-2.5 py-1.5 rounded-lg border border-border text-xs text-muted-foreground hover:text-foreground">
                                Batal
                            </button>
                            
                            <div class="flex items-center gap-1.5">
                                <button type="button" wire:click="confirmExtend(false)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-border bg-background hover:bg-muted text-foreground transition-all active:scale-95">
                                    Simpan
                                </button>
                                <button type="button" wire:click="confirmExtend(true)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-all active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <span>Simpan & WA</span>
                                </button>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif

        <!-- Edit Transaction Modal (Staff & Admin Friendly) -->
        @if($isEditingTrx)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3 sm:p-4 backdrop-blur-sm overscroll-contain animate-in fade-in duration-200"
                 x-data
                 x-init="document.body.classList.add('overflow-hidden')"
                 x-destroy="document.body.classList.remove('overflow-hidden')">
                <div class="bg-background rounded-2xl shadow-2xl w-full max-w-xl border border-border flex flex-col max-h-[85vh] sm:max-h-[88vh] overflow-hidden my-auto animate-in zoom-in-95 duration-150">
                    
                    {{-- Header Edit Modal --}}
                    <div class="px-3.5 py-3 border-b border-border flex items-center justify-between bg-muted/20 shrink-0">
                        <div class="min-w-0 pr-2">
                            <h3 class="text-xs sm:text-sm font-bold text-foreground flex items-center gap-1.5 flex-wrap leading-tight">
                                <span>Edit Transaksi</span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-primary/10 text-primary font-mono font-semibold">
                                    {{ $editOriginalData['booking_code'] ?? '' }}
                                </span>
                            </h3>
                            <p class="text-[10px] text-muted-foreground mt-0.5 truncate max-w-xs">
                                {{ $editOriginalData['nama'] ?? '' }} &bull; {{ $editOriginalData['unit_names'] ?? '' }}
                            </p>
                        </div>
                        <button wire:click="closeEditModal" class="h-7 w-7 rounded-md flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body: Form Edit atau Konfirmasi Diff --}}
                    @if($isConfirmingEdit)
                        {{-- Tampilan Konfirmasi Diff Sebelum Simpan --}}
                        <div class="p-3.5 sm:p-4 overflow-y-auto space-y-3.5 text-xs flex-1 overscroll-contain touch-pan-y" style="-webkit-overflow-scrolling: touch;">
                            <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-800 dark:text-blue-300">
                                <div class="flex items-center gap-1.5 font-bold text-xs mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                    <span>Konfirmasi Perubahan Data</span>
                                </div>
                                <p class="text-[11px] leading-relaxed opacity-90">
                                    Pastikan bagian data yang Anda ubah di bawah ini sudah benar sebelum disimpan ke sistem:
                                </p>
                            </div>

                            @if(empty($editDiffs))
                                <div class="p-4 text-center text-muted-foreground text-xs italic bg-muted/20 rounded-xl border border-dashed border-border">
                                    Tidak ada data yang berubah.
                                </div>
                            @else
                                <div class="rounded-xl border border-border overflow-hidden divide-y divide-border text-[11px]">
                                    @foreach($editDiffs as $field => $change)
                                        <div class="p-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                            <span class="font-semibold text-foreground">{{ $field }}</span>
                                            <div class="flex items-center gap-2 text-right">
                                                <span class="line-through text-muted-foreground text-[10px]">{{ $change['old'] }}</span>
                                                <span class="text-xs">›</span>
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $change['new'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Footer Konfirmasi Diff Edit --}}
                        <div class="p-2.5 sm:p-3.5 border-t border-border flex items-center justify-between gap-2 bg-muted/20 shrink-0">
                            <button type="button" wire:click="cancelConfirmEdit" class="px-3 py-1.5 rounded-lg border border-border text-xs font-semibold text-foreground hover:bg-muted">
                                Kembali Edit
                            </button>
                            <button type="button" wire:click="updateTrx"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md active:scale-95">
                                <span wire:loading wire:target="updateTrx" class="h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>Ya, Simpan Perubahan</span>
                            </button>
                        </div>
                    @else
                        {{-- Form Edit Transaksi --}}
                        <div class="p-3.5 sm:p-4 overflow-y-auto space-y-3.5 text-xs flex-1 overscroll-contain touch-pan-y" style="-webkit-overflow-scrolling: touch;">
                            
                            {{-- Info Banner Harga Awal (Staf tidak bingung) --}}
                            <div class="p-2.5 rounded-xl bg-muted/40 border border-border flex items-center justify-between text-[11px]">
                                <div>
                                    <span class="text-muted-foreground block text-[10px]">Subtotal Awal:</span>
                                    <span class="font-mono font-bold text-foreground">Rp {{ number_format($editOriginalData['subtotal'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-muted-foreground block text-[10px]">Grand Total Awal:</span>
                                    <span class="font-mono font-bold text-foreground">Rp {{ number_format($editOriginalData['grand_total'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            {{-- Data Pelanggan --}}
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Data Pelanggan</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">Nama Lengkap</label>
                                        <input type="text" wire:model="edit_nama"
                                            class="w-full h-8 rounded-lg border border-input bg-background px-2.5 text-xs focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">No. WhatsApp</label>
                                        <input type="text" wire:model="edit_no_wa"
                                            class="w-full h-8 rounded-lg border border-input bg-background px-2.5 text-xs focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                            </div>

                            {{-- Waktu Sewa --}}
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Jadwal Sewa</label>
                                    <span class="text-[9px] text-muted-foreground italic">Harga tidak otomatis berubah</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">Mulai</label>
                                        <input type="datetime-local" wire:model="edit_waktu_mulai"
                                            class="w-full h-8 rounded-lg border border-input bg-background px-2 text-[11px] focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">Selesai</label>
                                        <input type="datetime-local" wire:model="edit_waktu_selesai"
                                            class="w-full h-8 rounded-lg border border-input bg-background px-2 text-[11px] focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                            </div>

                            {{-- Pilihan Unit & Tombol Opsi Hitung Ulang Manual --}}
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Pilih Unit</label>
                                    <button type="button" wire:click="recalculateEditSubtotal"
                                        class="text-[10px] font-semibold text-primary hover:underline flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        <span>Hitung Ulang Tarif</span>
                                    </button>
                                </div>
                                <div class="max-h-28 overflow-y-auto rounded-lg border border-input p-1.5 space-y-1 bg-muted/10">
                                    @foreach($allUnitsList as $unit)
                                        <label class="flex items-center justify-between p-1 rounded hover:bg-muted/40 cursor-pointer text-xs transition-colors {{ in_array($unit->id, $edit_unit_ids) ? 'bg-primary/10 font-bold' : '' }}">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" wire:model="edit_unit_ids" value="{{ $unit->id }}"
                                                    class="rounded border-input text-primary focus:ring-primary h-3.5 w-3.5">
                                                <span class="text-foreground text-[11px]">{{ $unit->seri }}</span>
                                            </div>
                                            <span class="text-[9px] font-mono text-muted-foreground">Rp {{ number_format($unit->harga_per_hari, 0, ',', '.') }}/hr</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Rincian Biaya Transaksi --}}
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Penyesuaian Biaya (Rp)</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-bold text-foreground mb-0.5">Subtotal Sewa</label>
                                        <input type="number" wire:model="edit_subtotal" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-input bg-background text-[11px] font-semibold focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-emerald-600 dark:text-emerald-400 mb-0.5">Diskon</label>
                                        <input type="number" wire:model="edit_diskon" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-input bg-background text-[11px] font-semibold focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-rose-600 dark:text-rose-400 mb-0.5">Denda Telat</label>
                                        <input type="number" wire:model="edit_denda" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-input bg-background text-[11px] font-semibold focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-rose-600 dark:text-rose-400 mb-0.5">Denda Rusak</label>
                                        <input type="number" wire:model="edit_denda_kerusakan" min="0" step="1000"
                                            class="w-full h-8 px-2 rounded-lg border border-input bg-background text-[11px] font-semibold focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>
                            </div>

                            {{-- Status & Metode Bayar --}}
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">Status</label>
                                    <select wire:model="edit_status"
                                        class="w-full h-8 rounded-lg border border-input bg-background px-2 text-xs focus:ring-1 focus:ring-primary outline-none">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="renting">Rent</option>
                                        <option value="completed">Done</option>
                                        <option value="cancelled">Cancel</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-muted-foreground mb-0.5">Metode Bayar</label>
                                    <select wire:model="edit_metode_pembayaran"
                                        class="w-full h-8 rounded-lg border border-input bg-background px-2 text-xs focus:ring-1 focus:ring-primary outline-none">
                                        @foreach($this->paymentMethods as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Form Input Edit --}}
                        <div class="p-2.5 sm:p-3.5 border-t border-border flex items-center justify-between gap-1.5 bg-muted/20 shrink-0">
                            <button type="button" wire:click="closeEditModal" class="px-2.5 py-1.5 rounded-lg border border-border text-xs text-muted-foreground hover:text-foreground">
                                Batal
                            </button>
                            <button type="button" wire:click="confirmEdit"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold bg-primary hover:bg-primary/90 text-primary-foreground shadow-sm transition-all active:scale-95">
                                <span>Tinjau Perubahan</span>
                                <span class="text-[10px]">›</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        @endif
    </div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-url', (event) => {
            if (event.url) window.open(event.url, '_blank');
        });

        // Kunci scrolling body ketika modal perpanjang/edit terbuka di mobile
        Livewire.hook('commit', ({ component, commit, succeed }) => {
            succeed(() => {
                const hasModalOpen = document.querySelector('[x-data][x-init*="overflow-hidden"]');
                if (hasModalOpen) {
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
        });
    });
</script>
</div>