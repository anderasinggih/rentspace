<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg bg-background border border-border rounded-2xl shadow-xl overflow-hidden">
        <div class="p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 mb-6">
                <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-foreground mb-2">Terima Kasih, {{ $rental->nama }}!</h1>
            <p class="text-muted-foreground mb-4">
                Pesanan Anda telah kami terima. Silakan lakukan pembayaran sesuai instruksi sebelumnya jika belum tuntas.
            </p>
            <div class="mb-8 p-3 bg-primary/10 border border-primary/20 rounded-xl">
                <p class="text-sm font-bold text-primary flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L3 21"/></svg>
                    Mohon SCREENSHOT halaman ini sebagai bukti pesanan Anda!
                </p>
            </div>

            <!-- Info Card -->
            <div class="bg-muted/40 rounded-xl border border-border p-6 text-left space-y-4 mb-8">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1">Unit yang Disewa</span>
                    <span class="text-sm font-bold text-foreground">{{ $rental->unit->seri }}</span>
                </div>

                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1">Waktu Pengembalian</span>
                    <span class="text-sm font-bold text-primary">{{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d M Y, H:i') }}</span>
                    <p class="text-[11px] text-muted-foreground mt-1">
                        Sesuai jadwal yang Anda pilih saat checkout.
                    </p>
                </div>

                <div class="flex flex-col pt-2 border-t border-border">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md border border-amber-200/50 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950 dark:text-amber-300">Toleransi</span>
                        <span class="text-xs font-medium text-foreground">Paling lambat: <span class="font-bold text-amber-600">{{ $tolerance }} menit</span></span>
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1.5 italic">
                        *Keterlambatan melebihi batas toleransi akan dikenakan denda sesuai ketentuan.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-3">
                <a href="{{ route('public.timeline') }}" wire:navigate class="inline-flex items-center justify-center rounded-xl bg-primary text-primary-foreground h-11 px-8 font-bold shadow transition-all hover:bg-primary/90 hover:scale-[1.02] active:scale-[0.98]">
                    Selesai & Lihat Jadwal
                </a>
                <a href="/" wire:navigate class="text-sm text-muted-foreground hover:text-foreground font-medium transition-colors">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Footer Decoration -->
        <div class="h-2 bg-gradient-to-r from-primary/50 via-primary to-primary/50"></div>
    </div>
</div>
