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
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Transactions & Mutations</h1>
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

                <select wire:model.live="filterStatus"
                    class="h-9 w-full sm:w-[180px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="">Semua Status</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="paid">💳 Lunas</option>
                    <option value="completed">✅ Selesai</option>
                    <option value="cancelled">❌ Batal</option>
                </select>
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
                                        class="py-3 pl-3 pr-3 text-left text-xs sm:text-sm font-semibold text-foreground sm:pl-6">
                                        Invoice & Customer</th>
                                    <th scope="col"
                                        class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Unit Sewa</th>
                                    <th scope="col"
                                        class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Jadwal Sewa</th>
                                    <th scope="col"
                                        class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Subtotal</th>
                                    <th scope="col"
                                        class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-bold text-primary">
                                        Tagihan Akhir</th>
                                    <th scope="col"
                                        class="px-3 py-3 text-left text-xs sm:text-sm font-semibold text-foreground">
                                        Status</th>
                                    <th scope="col" class="relative py-3 pl-3 pr-2 sm:pr-6"><span
                                            class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border text-sm">
                                @forelse ($transactions as $trx)
                                <tr>
                                    <td class="whitespace-nowrap py-2 sm:py-4 pl-3 pr-3 text-xs sm:text-sm sm:pl-6">
                                        <div class="font-medium text-foreground">INV-{{ str_pad($trx->id, 5, '0',
                                            STR_PAD_LEFT) }}</div>
                                        <div class="text-muted-foreground mt-0.5 sm:mt-1">{{ $trx->nama }} <br /> <a
                                                href="https://wa.me/{{ preg_replace('/^0/', '62', $trx->no_wa) }}"
                                                target="_blank" class="text-primary hover:underline">{{ $trx->no_wa
                                                }}</a></div>
                                    </td>
                                    <td class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        <span class="font-medium text-foreground">{{ $trx->unit->seri ?? 'Unit Terhapus'
                                            }}</span><br />
                                        <span class="text-xs">{{ $trx->unit->imei ?? '-' }}</span>
                                    </td>
                                    <td
                                        class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground text-xs">
                                        {{ \Carbon\Carbon::parse($trx->waktu_mulai)->format('d M Y, H:i')
                                        }}<br />s/d<br />
                                        {{ \Carbon\Carbon::parse($trx->waktu_selesai)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        Rp {{ number_format($trx->subtotal_harga, 0, ',', '.') }}<br />
                                        <span class="text-xs text-red-500">Diskon: -Rp {{
                                            number_format($trx->potongan_diskon, 0, ',', '.') }}</span>
                                    </td>
                                    <td
                                        class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-sm font-bold text-primary">
                                        Rp {{ number_format($trx->grand_total, 0, ',', '.') }}<br />
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span
                                                class="inline-flex rounded border bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border-purple-200/50 dark:border-purple-900/50 px-1.5 font-mono text-[10px] sm:text-xs font-semibold uppercase">
                                                Kode: {{ $trx->kode_unik_pembayaran }}
                                            </span>
                                            <span
                                                class="inline-flex rounded border bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border-sky-200/50 dark:border-sky-900/50 px-1.5 font-mono text-[10px] sm:text-xs font-semibold uppercase">
                                                {{ $trx->metode_pembayaran }}
                                            </span>
                                            @if($trx->denda > 0)
                                            <span
                                                class="inline-flex rounded border bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border-amber-200/50 dark:border-amber-900/50 px-1.5 font-mono text-[10px] sm:text-xs font-semibold uppercase">
                                                +Late: {{ number_format($trx->denda/1000, 0) }}k
                                            </span>
                                            @endif
                                            @if($trx->denda_kerusakan > 0)
                                            <span
                                                class="inline-flex rounded border bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300 border-red-200/50 dark:border-red-900/50 px-1.5 font-mono text-[10px] sm:text-xs font-semibold uppercase">
                                                +Dmg: {{ number_format($trx->denda_kerusakan/1000, 0) }}k
                                            </span>
                                            @if($trx->catatan_kerusakan)
                                            <div
                                                class="w-full text-[10px] text-red-600 dark:text-red-400 italic mt-0.5">
                                                Note: {{ $trx->catatan_kerusakan }}
                                            </div>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-2 sm:px-3 py-2 sm:py-4">
                                        @if($trx->status === 'pending')
                                        <x-ui.badge variant="amber" class="text-[10px] sm:text-xs">Pending</x-ui.badge>
                                        @elseif($trx->status === 'paid')
                                        <x-ui.badge variant="blue" class="text-[10px] sm:text-xs">Paid</x-ui.badge>
                                        @elseif($trx->status === 'completed')
                                        <x-ui.badge variant="green" class="text-[10px] sm:text-xs">Selesai</x-ui.badge>
                                        @else
                                        <x-ui.badge variant="red" class="text-[10px] sm:text-xs">Batal</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-2 sm:py-4 pl-2 pr-2 sm:pr-6 text-right">
                                        <div class="flex flex-col gap-1 sm:gap-2 items-end">
                                            @if($trx->status === 'pending')
                                            @if(auth()->user()->role === 'admin')
                                            <button wire:click="markAsPaid({{ $trx->id }})"
                                                wire:confirm="Transaksi ini sudah valid transfer?"
                                                class="inline-flex items-center justify-center rounded-md border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Validasi</button>
                                            <button wire:click="cancel({{ $trx->id }})"
                                                wire:confirm="Batalkan pesanan ini?"
                                                class="text-[10px] sm:text-xs text-red-500 hover:text-red-700 hover:underline">Batalkan</button>
                                            @endif
                                            @elseif($trx->status === 'paid')
                                            @php
                                            $tolerance = (int) \App\Models\Setting::getVal('late_tolerance_minutes',
                                            60);
                                            $isLate =
                                            (\Carbon\Carbon::parse($trx->waktu_selesai)->addMinutes($tolerance) <
                                                now()); @endphp @if(auth()->user()->role === 'admin')
                                                @if($isLate)
                                                <button wire:click="openDendaModal({{ $trx->id }})"
                                                    class="inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow hover:bg-red-700 h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Selesaikan
                                                    (Telat)</button>
                                                @else
                                                <button wire:click="openDendaModal({{ $trx->id }})"
                                                    class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Selesaikan</button>
                                                @endif
                                                @endif
                                                @endif
                                                @if(auth()->user()->role === 'admin')
                                                <div class="flex items-center gap-2 mt-1">
                                                    <button wire:click="openInspect({{ $trx->id }})"
                                                        class="text-[10px] sm:text-xs text-sky-600 hover:underline">Inspect</button>
                                                    <button wire:click="editTrx({{ $trx->id }})"
                                                        class="text-[10px] sm:text-xs text-primary hover:underline">Edit</button>
                                                    <button wire:click="deleteRow({{ $trx->id }})"
                                                        class="text-[10px] sm:text-xs text-muted-foreground hover:text-foreground"
                                                        onclick="confirm('Hapus data secara permanen?') || event.stopImmediatePropagation()">Hapus</button>
                                                </div>
                                                @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-sm text-muted-foreground">Belum ada
                                        transaksi penyewaan yang masuk.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                                class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Denda
                                Keterlambatan</label>
                            <input type="number" wire:model.live="dendaAmount" min="0"
                                class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="0">
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Denda
                                Kerusakan</label>
                            <input type="number" wire:model.live="dendaKerusakanAmount" min="0"
                                class="w-full h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="0">
                        </div>
                    </div>

                    @if($dendaKerusakanAmount > 0)
                    <div>
                        <label
                            class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Keterangan
                            Kerusakan</label>
                        <textarea wire:model="catatanKerusakan" rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-1 focus:ring-primary outline-none"
                            placeholder="Contoh: Layar retak, Kabel hilang..."></textarea>
                    </div>
                    @endif

                    @if($dendaAmount > 0 || $dendaKerusakanAmount > 0)
                    <div class="rounded-lg bg-muted/30 p-4 border border-border">
                        <label
                            class="block text-[11px] font-bold uppercase tracking-wider text-muted-foreground mb-3">Metode
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
                                <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-1">
                                    Total Denda Bayar</p>
                                <p class="text-2xl font-black text-primary">Rp {{ number_format((int)$dendaAmount +
                                    (int)$dendaKerusakanAmount, 0, ',', '.') }}</p>
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
                    <button wire:click="closeDendaModal"
                        class="h-[36px] px-4 rounded-md border border-input bg-background text-sm font-medium hover:bg-accent transition-colors">Batalkan</button>
                    <button wire:click="confirmDenda"
                        class="h-[36px] px-4 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 shadow transition-colors flex items-center justify-center w-[160px]"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmDenda">Selesaikan & Tagih</span>
                        <span wire:loading wire:target="confirmDenda">Memproses...</span>
                    </button>
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
                    <h3 class="text-lg font-bold">Edit Transaksi INV-{{ str_pad($editTrxId, 5, '0', STR_PAD_LEFT) }}
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
                            <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Data Pelanggan</h4>
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
                            <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Detail Sewa & Biaya</h4>
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
                    <button wire:click="closeEditModal"
                        class="h-10 px-4 rounded-md border border-input bg-background text-sm font-medium hover:bg-accent transition-colors">Batal</button>
                    <button wire:click="updateTrx"
                        class="h-10 px-6 rounded-md bg-primary text-primary-foreground text-sm font-bold shadow-lg hover:shadow-primary/20 transition-all">Simpan
                        Perubahan</button>
                </div>
            </div>
        </div>
        @endif

        <!-- Inspect Modal -->
        @if($inspectTrx)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-background/80 backdrop-blur-sm" wire:click="closeInspect"></div>
            <div
                class="relative bg-background rounded-xl shadow-2xl w-full max-w-xl border border-border overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div class="p-6 border-b border-border flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold tracking-tight text-foreground">Detail Transaksi</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">INV-{{ str_pad($inspectTrx->id, 5, '0',
                            STR_PAD_LEFT) }} • {{ $inspectTrx->created_at->format('d M Y') }}</p>
                    </div>
                    <button wire:click="closeInspect" class="rounded-md p-1 hover:bg-muted transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="text-muted-foreground">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-8 scrollbar-hide">
                    <!-- Status Section -->
                    <div class="flex items-center justify-between p-4 bg-muted/30 rounded-lg border border-border">
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Status
                                Transaksi</p>
                            <div class="flex items-center gap-2">
                                @if($inspectTrx->status === 'pending') <span
                                    class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span> <span
                                    class="text-sm font-semibold text-amber-600 dark:text-amber-400 uppercase">Menunggu
                                    Pembayaran</span>
                                @elseif($inspectTrx->status === 'paid') <span
                                    class="h-2 w-2 rounded-full bg-blue-500"></span> <span
                                    class="text-sm font-semibold text-blue-600 dark:text-blue-400 uppercase">Dibayar
                                    (Aktif)</span>
                                @elseif($inspectTrx->status === 'completed') <span
                                    class="h-2 w-2 rounded-full bg-green-500"></span> <span
                                    class="text-sm font-semibold text-green-600 dark:text-green-400 uppercase">Selesai</span>
                                @else <span class="h-2 w-2 rounded-full bg-red-500"></span> <span
                                    class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase">Dibatalkan</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right space-y-1 border-l border-border pl-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Pengembalian
                                Aktual</p>
                            <p
                                class="text-sm font-semibold {{ $inspectTrx->completed_at ? 'text-foreground' : 'text-muted-foreground italic' }}">
                                {{ $inspectTrx->completed_at ? $inspectTrx->completed_at->format('d/m/y H:i') : 'Belum
                                Selesai' }}
                            </p>
                        </div>
                    </div>

                    <!-- Grid Info -->
                    <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Customer -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70">
                                Informasi Penyewa</h4>
                            <div class="space-y-4">
                                <div class="group">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Nama Lengkap
                                    </p>
                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ $inspectTrx->nama
                                        }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Identitas
                                        (NIK)</p>
                                    <p class="text-sm font-medium text-foreground tracking-wider">{{ $inspectTrx->nik }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Kontap
                                        WhatsApp</p>
                                    <p class="text-sm font-bold text-primary">{{ $inspectTrx->no_wa }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Alamat
                                        Domisili</p>
                                    <p class="text-sm text-foreground leading-normal">{{ $inspectTrx->alamat }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Rental -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70">
                                Detail Unit & Waktu</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Unit Yang
                                        Disewa</p>
                                    <p class="text-sm font-bold text-foreground">{{ $inspectTrx->unit->seri ?? 'N/A' }}
                                    </p>
                                    <p class="text-[11px] text-muted-foreground">IMEI/SN: {{ $inspectTrx->unit->warna ??
                                        '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Jadwal Mulai
                                    </p>
                                    <p class="text-sm font-semibold text-foreground">{{
                                        $inspectTrx->waktu_mulai->format('d M Y, H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase mb-0.5">Jadwal
                                        Selesai</p>
                                    <p class="text-sm font-semibold text-foreground">{{
                                        $inspectTrx->waktu_selesai->format('d M Y, H:i') }}</p>
                                </div>
                                @if($inspectTrx->applied_promo_name)
                                <div class="p-2 bg-primary/5 rounded border border-primary/20">
                                    <p class="text-[9px] font-bold text-primary uppercase mb-0.5">Promo Aktif</p>
                                    <p class="text-[11px] font-bold text-primary">{{ $inspectTrx->applied_promo_name }}
                                    </p>
                                    @if($inspectTrx->hari_bonus > 0 || $inspectTrx->jam_bonus > 0)
                                    <p class="text-[9px] text-green-600 font-bold">🎁 Bonus +{{ $inspectTrx->hari_bonus
                                        > 0 ? $inspectTrx->hari_bonus.'H' : '' }}{{ $inspectTrx->jam_bonus > 0 ?
                                        $inspectTrx->jam_bonus.'J' : '' }}</p>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="space-y-4 pt-4 border-t border-border">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70">Ikhtisar
                            Pembayaran</h4>
                        <div class="rounded-lg border border-border overflow-hidden">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-border">
                                    <tr class="bg-muted/10">
                                        <td class="py-2.5 px-4 text-muted-foreground">Harga Dasar Sewa</td>
                                        <td class="py-2.5 px-4 text-right font-medium">Rp {{
                                            number_format($inspectTrx->subtotal_harga, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($inspectTrx->potongan_diskon > 0)
                                    <tr>
                                        <td class="py-2.5 px-4 text-red-500">Potongan Diskon / Promo</td>
                                        <td class="py-2.5 px-4 text-right font-medium text-red-500">- Rp {{
                                            number_format($inspectTrx->potongan_diskon, 0, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="py-2.5 px-4 text-muted-foreground">Kode Unik / Service Fee</td>
                                        <td class="py-2.5 px-4 text-right font-medium">+ Rp {{
                                            $inspectTrx->kode_unik_pembayaran }}</td>
                                    </tr>
                                    @if($inspectTrx->denda > 0)
                                    <tr class="bg-amber-500/5">
                                        <td class="py-2.5 px-4 text-amber-600">Denda Keterlambatan</td>
                                        <td class="py-2.5 px-4 text-right font-bold text-amber-600">+ Rp {{
                                            number_format($inspectTrx->denda, 0, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    @if($inspectTrx->denda_kerusakan > 0)
                                    <tr class="bg-red-500/5">
                                        <td class="py-2.5 px-4 text-red-600">
                                            Denda Kerusakan
                                            @if($inspectTrx->catatan_kerusakan)
                                            <p class="text-[10px] italic font-normal text-muted-foreground mt-0.5">Ket:
                                                {{ $inspectTrx->catatan_kerusakan }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-bold text-red-600">+ Rp {{
                                            number_format($inspectTrx->denda_kerusakan, 0, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                    <tr class="bg-primary/5">
                                        <td class="py-4 px-4 font-bold text-foreground">TOTAL AKHIR</td>
                                        <td class="py-4 px-4 text-right text-lg font-black text-primary">Rp {{
                                            number_format($inspectTrx->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-border flex justify-end bg-muted/20">
                    <button wire:click="closeInspect"
                        class="inline-flex items-center justify-center rounded-md text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8">
                        Selesai Meninjau
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>