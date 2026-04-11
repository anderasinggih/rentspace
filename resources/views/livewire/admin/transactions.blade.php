<div>
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Transactions & Mutations</h1>
                <p class="mt-2 text-sm text-muted-foreground">Verify payments via unique codes and manage rental schedules.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button wire:click="exportCsv" class="inline-flex items-center gap-2 justify-center rounded-md bg-secondary text-secondary-foreground shadow hover:bg-secondary/80 h-9 px-4 text-sm font-semibold transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Export CSV
                </button>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col" class="py-3 pl-3 pr-3 text-left text-xs sm:text-sm font-semibold text-foreground sm:pl-6">Invoice & Customer</th>
                                    <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">Unit Sewa</th>
                                    <th scope="col" class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">Jadwal Sewa</th>
                                    <th scope="col" class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-foreground">Subtotal</th>
                                    <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-bold text-primary">Tagihan Akhir</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs sm:text-sm font-semibold text-foreground">Status</th>
                                    <th scope="col" class="relative py-3 pl-3 pr-2 sm:pr-6"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border text-sm">
                                @forelse ($transactions as $trx)
                                <tr>
                                    <td class="whitespace-nowrap py-2 sm:py-4 pl-3 pr-3 text-xs sm:text-sm sm:pl-6">
                                        <div class="font-medium text-foreground">INV-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div class="text-muted-foreground mt-0.5 sm:mt-1">{{ $trx->nama }} <br/> <a href="https://wa.me/{{ preg_replace('/^0/', '62', $trx->no_wa) }}" target="_blank" class="text-primary hover:underline">{{ $trx->no_wa }}</a></div>
                                    </td>
                                    <td class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        <span class="font-medium text-foreground">{{ $trx->unit->seri ?? 'Unit Terhapus' }}</span><br/>
                                        <span class="text-xs">{{ $trx->unit->imei ?? '-' }}</span>
                                    </td>
                                    <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground text-xs">
                                        {{ \Carbon\Carbon::parse($trx->waktu_mulai)->format('d M Y, H:i') }}<br/>s/d<br/>
                                        {{ \Carbon\Carbon::parse($trx->waktu_selesai)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        Rp {{ number_format($trx->subtotal_harga, 0, ',', '.') }}<br/>
                                        <span class="text-xs text-red-500">Diskon: -Rp {{ number_format($trx->potongan_diskon, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-sm font-bold text-primary">
                                        Rp {{ number_format($trx->grand_total, 0, ',', '.') }}<br/>
                                        <div class="mt-1 flex items-center gap-1">
                                            <span class="inline-flex rounded border border-orange-300 bg-orange-100 text-orange-800 px-1.5 font-mono text-xs font-semibold uppercase">
                                                Kode: {{ $trx->kode_unik_pembayaran }}
                                            </span>
                                            <span class="inline-flex rounded border border-blue-300 bg-blue-100 text-blue-800 px-1.5 font-mono text-xs font-semibold uppercase">
                                                {{ $trx->metode_pembayaran }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-2 sm:px-3 py-2 sm:py-4">
                                        @if($trx->status === 'pending')
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] sm:text-xs font-semibold border-transparent bg-orange-500 text-white">Pending</span>
                                        @elseif($trx->status === 'paid')
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] sm:text-xs font-semibold border-transparent bg-blue-500 text-white">Lunas</span>
                                        @elseif($trx->status === 'completed')
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] sm:text-xs font-semibold border-transparent bg-emerald-500 text-white">Selesai</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] sm:text-xs font-semibold border-transparent bg-secondary text-secondary-foreground">Batal</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-2 sm:py-4 pl-2 pr-2 sm:pr-6 text-right">
                                        <div class="flex flex-col gap-1 sm:gap-2 items-end">
                                        @if($trx->status === 'pending')
                                            @if(auth()->user()->role === 'admin')
                                            <button wire:click="markAsPaid({{ $trx->id }})" wire:confirm="Transaksi ini sudah valid transfer?" class="inline-flex items-center justify-center rounded-md border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Validasi</button>
                                            <button wire:click="cancel({{ $trx->id }})" wire:confirm="Batalkan pesanan ini?" class="text-[10px] sm:text-xs text-red-500 hover:text-red-700 hover:underline">Batalkan</button>
                                            @endif
                                        @elseif($trx->status === 'paid')
                                            @php
                                                $tolerance = (int) \App\Models\Setting::getVal('late_tolerance_minutes', 60);
                                                $isLate = (\Carbon\Carbon::parse($trx->waktu_selesai)->addMinutes($tolerance) < now());
                                            @endphp
                                            @if(auth()->user()->role === 'admin')
                                                @if($isLate)
                                                    <button wire:click="openDendaModal({{ $trx->id }})" class="inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow hover:bg-red-700 h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Telat?</button>
                                                @else
                                                    <button wire:click="finishWithoutDenda({{ $trx->id }})" wire:confirm="Sewa sudah dikembalikan dan selesai?" class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-7 sm:h-8 px-2 sm:px-3 text-[10px] sm:text-xs font-medium">Selesai</button>
                                                @endif
                                            @endif
                                        @endif
                                        @if(auth()->user()->role === 'admin')
                                        <button wire:click="deleteRow({{ $trx->id }})" class="text-[10px] sm:text-xs text-muted-foreground hover:text-foreground mt-0.5 text-right flex self-end" onclick="confirm('Hapus data secara permanen?') || event.stopImmediatePropagation()">Hapus</button>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-sm text-muted-foreground">Belum ada transaksi penyewaan yang masuk.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($transactions->hasPages())
                        <div class="px-6 py-4 border-t border-border">
                            <div class="mt-4">
                                {{ $transactions->links(data: ['scrollTo' => false]) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Denda Modal -->
        @if($completingTrxId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-background rounded-xl p-6 shadow-xl w-full max-w-md border border-border">
                <h3 class="text-lg font-bold mb-2">Penyelesaian Masa Sewa Terlambat</h3>
                <p class="text-xs text-muted-foreground mb-4 leading-relaxed">Sistem mendeteksi jadwal melampaui batas toleransi. Jika ada denda yang dibebankan, masukkan nominalnya di bawah. Kosongkan (0) jika tidak ditarik denda.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nominal Denda (Rp)</label>
                        <input type="number" wire:model.live="dendaAmount" min="0" class="w-full h-10 rounded-md border border-input bg-background px-3 shadow-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="0">
                    </div>
                    
                    @if($dendaAmount > 0)
                    <div>
                        <label class="block text-sm font-medium mb-1">Metode Pembayaran Denda</label>
                        <div class="grid grid-cols-2 gap-3 mt-2">
                            <label class="relative flex cursor-pointer rounded-lg border bg-background p-3 shadow-sm focus:outline-none hover:border-primary/50 transition-colors {{ $dendaMethod === 'cash' ? 'border-primary ring-1 ring-primary' : 'border-border' }}">
                                <input type="radio" wire:model="dendaMethod" value="cash" class="sr-only">
                                <span class="flex flex-1 items-center justify-center">
                                    <span class="font-medium {{ $dendaMethod === 'cash' ? 'text-primary' : 'text-foreground' }}">💵 Tunai (Cash)</span>
                                </span>
                            </label>
                            <label class="relative flex cursor-pointer rounded-lg border bg-background p-3 shadow-sm focus:outline-none hover:border-primary/50 transition-colors {{ $dendaMethod === 'qris' ? 'border-primary ring-1 ring-primary' : 'border-border' }}">
                                <input type="radio" wire:model="dendaMethod" value="qris" class="sr-only">
                                <span class="flex flex-1 items-center justify-center">
                                    <span class="font-medium {{ $dendaMethod === 'qris' ? 'text-primary' : 'text-foreground' }}">📱 QRIS</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="closeDendaModal" class="h-[36px] px-4 rounded-md border border-input bg-background text-sm font-medium hover:bg-accent transition-colors">Batalkan</button>
                    <button wire:click="confirmDenda" class="h-[36px] px-4 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 shadow transition-colors flex items-center justify-center w-[160px]" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmDenda">Selesaikan & Tagih</span>
                        <span wire:loading wire:target="confirmDenda">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
