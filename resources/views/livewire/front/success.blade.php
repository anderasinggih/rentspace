<div class="py-0 px-4 sm:py-16 flex flex-col items-center">
    <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-sm overflow-hidden mt-4">

        <!-- Header -->
        <div class="p-5 text-center border-b border-border/50 bg-muted/20">
            <div
                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-500 mb-3">
                <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-foreground">Pesanan Berhasil!</h1>
            <p class="text-xs text-muted-foreground mt-1">ID Pesanan: #{{ str_pad($rental->id, 5, '0', STR_PAD_LEFT) }}
                &bull; {{ $rental->nama }}</p>
            <p class="text-[10px] text-muted-foreground mt-1 opacity-70">
                {{ $rental->created_at->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </div>

        <div class="p-5 space-y-4">
            <!-- Units -->
            <div class="space-y-2">
                <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Detail Item</p>
                @foreach($rental->units as $unit)
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-foreground">{{ $unit->seri }}</span>
                        <span class="text-xs text-muted-foreground">{{ $unit->warna }} &bull; {{ $unit->memori }}</span>
                    </div>
                @endforeach
            </div>

            <div class="h-px bg-border/50"></div>

            <!-- Schedule -->
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1">Pengambilan
                    </p>
                    <p class="font-medium">{{ $rental->waktu_mulai->format('d/m/Y') }}</p>
                    <p class="text-xs text-muted-foreground">{{ $rental->waktu_mulai->format('H:i') }} WIB</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1">Kembali</p>
                    <p class="font-medium text-primary">{{ $rental->waktu_selesai->format('d/m/Y') }}</p>
                    <p class="text-xs text-muted-foreground">{{ $rental->waktu_selesai->format('H:i') }} WIB</p>
                </div>
            </div>

            <div class="h-px bg-border/50"></div>

            <!-- Price -->
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between text-muted-foreground">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($rental->subtotal_harga, 0, ',', '.') }}</span>
                </div>
                @if($rental->potongan_diskon > 0)
                    <div class="flex justify-between text-rose-500">
                        <span>Diskon</span>
                        <span>- Rp {{ number_format($rental->potongan_diskon, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($rental->kode_unik_pembayaran)
                    <div class="flex justify-between text-amber-500">
                        <span>Kode Unik</span>
                        <span>+ {{ $rental->kode_unik_pembayaran }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center pt-2 text-sm font-bold text-foreground">
                    <span>Total Bayar</span>
                    <span class="text-base text-primary">Rp
                        {{ number_format($rental->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            @if($isOwner)
                <!-- Warning -->
                <div
                    class="flex items-start gap-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 p-3 rounded-lg text-xs">
                    <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4m0 4h.01" />
                    </svg>
                    <p>Wajib <span class="font-bold underline">SCREENSHOT</span> halaman ini sebagai bukti pesanan Anda.</p>
                </div>

                <!-- Actions -->
                <div class="space-y-2 pt-2">
                    <a href="{{ $waUrl }}" target="_blank"
                        class="w-full flex items-center justify-center gap-2 h-11 rounded-xl bg-primary text-primary-foreground text-sm font-semibold transition-all shadow-sm group mb-3">
                        <span>Konfirmasi Pesanan</span>

                    </a>

                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('public.check-order', ['nik' => $rental->nik, 'no_wa' => $rental->no_wa]) }}"
                            wire:navigate
                            class="flex items-center justify-center gap-1.5 h-11 rounded-xl bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-semibold transition-all border border-border/50">
                            Cek Status
                        </a>
                        <a href="/" wire:navigate
                            class="flex items-center justify-center gap-1.5 h-11 rounded-xl bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-semibold transition-all border border-border/50">
                            Beranda
                        </a>
                    </div>
                </div>
            @endif

            @if(auth()->check() && auth()->user()->role === 'admin')
                <div class="pt-4 border-t border-border/50 mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Admin Panel</p>
                        <span class="text-[10px] font-bold uppercase {{ $rental->status === 'paid' ? 'text-emerald-500' : ($rental->status === 'cancelled' ? 'text-rose-500' : 'text-amber-500') }}">
                            {{ $rental->status }}
                        </span>
                    </div>

                    @if(session()->has('admin_message'))
                        <div class="bg-emerald-500/10 text-emerald-600 text-[10px] p-2 rounded text-center">
                            {{ session('admin_message') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2">
                        @if($rental->status === 'pending')
                            <button wire:click="validateOrder" 
                                wire:confirm="Apakah Anda yakin ingin memvalidasi pesanan ini?"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center h-10 rounded-xl bg-foreground text-background text-xs font-bold hover:opacity-90 transition-all shadow-sm disabled:opacity-50">
                                Validasi Pesanan
                            </button>

                            <button wire:click="cancelOrder" 
                                wire:confirm="Apakah Anda yakin ingin membatalkan pesanan ini?"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center h-10 rounded-xl bg-background text-foreground border border-border text-xs font-bold hover:bg-muted transition-all shadow-sm disabled:opacity-50">
                                Batalkan
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Footer -->
            <p class="text-[10px] text-muted-foreground text-center pt-4 opacity-50">
                &copy; {{ date('Y') }} All Rights Reserved Rent Space
            </p>
        </div>
    </div>
</div>