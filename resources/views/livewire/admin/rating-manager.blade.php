<div class="p-2 sm:p-6">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-foreground">Moderasi Rating & Feedback</h1>
            <p class="mt-2 text-sm text-muted-foreground italic">Kelola ulasan yang diberikan pelanggan untuk ditampilkan di marquee beranda.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
             <a href="{{ route('admin.settings') }}" class="inline-flex h-8 items-center gap-2 px-4 rounded-lg border border-border bg-background text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-foreground transition-all shadow-sm active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali
            </a>
        </div>
    </div>

    <!-- Filters (Samain Logs) -->
    <div class="mb-4 bg-background rounded-xl border border-border p-3 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <!-- Search -->
            <div class="flex-1 min-w-[240px] group">
                <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1">Cari Ulasan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        class="block w-full h-8 pl-8 pr-3 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                        placeholder="Cari pelanggan, ulasan, atau kode booking...">
                </div>
            </div>

            <!-- perPage -->
            <div class="w-full md:w-24">
                <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1">Rows</label>
                <select wire:model.live="perPage" class="block w-full h-8 px-2.5 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none cursor-pointer uppercase font-bold">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    @if (session()->has('rating_message'))
        <div class="p-3 mb-6 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200 animate-in fade-in slide-in-from-top-2">
            {{ session('rating_message') }}
        </div>
    @endif

    <div class="overflow-hidden shadow ring-1 ring-border rounded-xl bg-background">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Pelanggan</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Rating</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Rincian Feedback</th>
                    <th class="px-4 py-3.5 text-right text-xs font-bold text-muted-foreground uppercase tracking-widest">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border bg-background">
                @forelse($ratings as $item)
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4">
                            <div>
                                <div class="text-sm font-bold text-foreground leading-none">{{ $item->nama }}</div>
                                <div class="text-[10px] text-muted-foreground mt-1 font-mono uppercase tracking-tighter opacity-70">{{ $item->booking_code }}</div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4 text-xs">
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" 
                                        fill="{{ $item->rating >= $i ? 'currentColor' : 'none' }}" 
                                        stroke="currentColor" stroke-width="2.5" 
                                        class="{{ $item->rating >= $i ? 'text-amber-400' : 'text-zinc-300' }}">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                @endfor
                                <span class="ml-1.5 text-[10px] font-black text-foreground">{{ $item->rating }}</span>
                            </div>
                        </td>
                        <td class="px-3 sm:px-4 py-4 text-xs text-muted-foreground leading-relaxed">
                            <div class="max-w-md">
                                <span class="text-foreground font-medium">"{{ $item->feedback }}"</span>
                                <div class="text-[9px] mt-1 opacity-50 italic uppercase font-bold tracking-tighter">{{ $item->created_at->format('d M Y, H:i') }} WIB</div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4 text-right">
                            @if(auth()->user()->role === 'admin')
                                <button wire:click="deleteRating({{ $item->id }})" 
                                    wire:confirm="Hapus feedback dari pelanggan ini? Rating akan dikosongkan."
                                    class="p-2 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-all active:scale-90">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-xs font-bold text-muted-foreground uppercase tracking-widest opacity-40">
                            Belum ada rating yang masuk untuk dimoderasi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination (Style samain Logs) -->
    <div class="p-4 border-t border-border mt-4 overflow-hidden shadow ring-1 ring-border rounded-xl bg-background">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 px-2">
                <!-- Left: Info -->
                <div class="flex items-center gap-6 order-2 md:order-1">
                    <div class="hidden sm:block">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none opacity-70">
                            Showing {{ $ratings->firstItem() ?? 0 }}-{{ $ratings->lastItem() ?? 0 }} of {{ $ratings->total() }}
                        </p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center gap-3 order-1 md:order-2">
                    <button wire:click="previousPage" @disabled($ratings->onFirstPage())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    
                    <div class="flex items-center gap-2 px-3 h-8 bg-muted/50 rounded-lg border border-border/50">
                        <span class="text-xs font-black text-foreground">{{ $ratings->currentPage() }}</span>
                        <span class="text-[10px] font-bold text-muted-foreground uppercase opacity-50">/</span>
                        <span class="text-xs font-black text-foreground">{{ $ratings->lastPage() }}</span>
                    </div>

                    <button wire:click="nextPage" @disabled(!$ratings->hasMorePages())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
    </div>
</div>
