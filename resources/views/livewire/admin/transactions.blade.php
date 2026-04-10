<div>
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Transactions & Mutations</h1>
                <p class="mt-2 text-sm text-muted-foreground">Verify payments via unique codes and manage rental schedules.</p>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-foreground sm:pl-6">Invoice & Customer</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Unit Sewa</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Jadwal Sewa</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Subtotal</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-bold text-primary">Tagihan Akhir (Transfer)</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">Status</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border text-sm">
                                @forelse ($transactions as $trx)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="font-medium text-foreground">INV-{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div class="text-muted-foreground mt-1">{{ $trx->nama }} <br/> <a href="https://wa.me/{{ preg_replace('/^0/', '62', $trx->no_wa) }}" target="_blank" class="text-primary hover:underline">{{ $trx->no_wa }}</a></div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        <span class="font-medium text-foreground">{{ $trx->unit->seri ?? 'Unit Terhapus' }}</span><br/>
                                        <span class="text-xs">{{ $trx->unit->imei ?? '-' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-muted-foreground text-xs">
                                        {{ \Carbon\Carbon::parse($trx->waktu_mulai)->format('d M Y, H:i') }}<br/>s/d<br/>
                                        {{ \Carbon\Carbon::parse($trx->waktu_selesai)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-muted-foreground">
                                        Rp {{ number_format($trx->subtotal_harga, 0, ',', '.') }}<br/>
                                        <span class="text-xs text-red-500">Diskon: -Rp {{ number_format($trx->potongan_diskon, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-base font-bold text-primary">
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
                                    <td class="whitespace-nowrap px-3 py-4">
                                        @if($trx->status === 'pending')
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-yellow-200 bg-yellow-100 text-yellow-800">Menunggu (Pending)</span>
                                        @elseif($trx->status === 'paid')
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-blue-200 bg-blue-100 text-blue-800">Lunas (Aktif)</span>
                                        @elseif($trx->status === 'completed')
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-green-200 bg-green-100 text-green-800">Selesai</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-border bg-muted text-muted-foreground">Batal</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right sm:pr-6">
                                        <div class="flex flex-col gap-2 items-end">
                                        @if($trx->status === 'pending')
                                            <button wire:click="markAsPaid({{ $trx->id }})" wire:confirm="Transaksi ini sudah valid transfer?" class="inline-flex items-center justify-center rounded-md border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3 text-xs font-medium">Validasi Lunas</button>
                                            <button wire:click="cancel({{ $trx->id }})" wire:confirm="Batalkan pesanan ini?" class="text-xs text-red-500 hover:text-red-700 hover:underline">Batalkan</button>
                                        @elseif($trx->status === 'paid')
                                            <button wire:click="complete({{ $trx->id }})" wire:confirm="Sewa sudah dikembalikan dan selesai?" class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-8 px-3 text-xs font-medium">Tandai Selesai</button>
                                        @endif
                                        <button wire:click="deleteRow({{ $trx->id }})" wire:confirm="Hapus data secara permanen?" class="text-xs text-muted-foreground hover:text-foreground mt-1">Delete Record</button>
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
                </div>
            </div>
        </div>

    </div>
</div>
