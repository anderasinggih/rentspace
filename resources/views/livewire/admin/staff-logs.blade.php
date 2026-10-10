<div class="max-w-4xl mx-auto px-2 sm:px-4 py-2 sm:py-3">
    <!-- Top Bar: Category Pills & Refresh -->
    <div class="flex items-center justify-between gap-2 overflow-x-auto no-scrollbar pb-1 mb-3">
        <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar">
            <button wire:click="setCategory('all')"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'all' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
                <span>Semua Aktivitas</span>
                <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['all'] }}</span>
            </button>

            <button wire:click="setCategory('transaksi')"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'transaksi' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                <span>Transaksi Sewa</span>
                <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['transaksi'] }}</span>
            </button>

            <button wire:click="setCategory('unit')"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'unit' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                <span>Unit iPhone</span>
                <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['unit'] }}</span>
            </button>

            <button wire:click="setCategory('promo')"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'promo' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <span>Promo & Kupon</span>
                <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['promo'] }}</span>
            </button>

            <button wire:click="setCategory('system')"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'system' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Pengaturan Sistem</span>
                <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['system'] }}</span>
            </button>
        </div>

        <button wire:click="$refresh" 
            title="Segarkan data terbaru"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground border border-border/80 transition-all active:scale-95 shrink-0 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                <path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                <path d="M8 16H3v5"/>
            </svg>
            <span class="hidden sm:inline">Segarkan</span>
        </button>
    </div>

    <!-- Search & Advanced Filters -->
    <div x-data="{ showAdvanced: false }" class="rounded-2xl border border-border/80 bg-card p-3 sm:p-4 mb-4 shadow-xs transition-all">
        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative flex-1 w-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari aktivitas, nama penyewa, staf, atau kode booking..."
                    class="w-full h-9 rounded-xl border border-border/80 bg-background/60 pl-9 pr-3 text-xs text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button @click="showAdvanced = !showAdvanced"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-border/80 text-xs font-semibold text-foreground hover:bg-foreground/5 transition-all"
                    :class="showAdvanced || '{{ $selectedRole || $selectedUser || $dateStart || $dateEnd }}' ? 'bg-primary/10 text-primary border-primary/30' : 'bg-background'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Filter Lanjutan</span>
                </button>

                @if($search || $selectedRole || $selectedUser || $dateStart || $dateEnd || $category !== 'all')
                    <button wire:click="resetFilters"
                        class="h-9 px-3 rounded-xl text-xs font-semibold text-destructive hover:bg-destructive/10 transition-colors">
                        Reset
                    </button>
                @endif
            </div>
        </div>

        <!-- Advanced Collapsible Filters -->
        <div x-show="showAdvanced" x-collapse x-cloak class="pt-3 mt-3 border-t border-border/50 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Staf / Pelaku Aksi</label>
                <select wire:model.live="selectedUser" class="w-full h-8 rounded-lg border border-border bg-background px-2.5 text-xs text-foreground focus:outline-none focus:border-primary">
                    <option value="">Semua Orang</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role === 'admin' ? 'Administrator' : 'Staf Kasir' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Peran Akun</label>
                <select wire:model.live="selectedRole" class="w-full h-8 rounded-lg border border-border bg-background px-2.5 text-xs text-foreground focus:outline-none focus:border-primary">
                    <option value="">Semua Peran</option>
                    <option value="admin">Administrator (Pemilik)</option>
                    <option value="staff">Staf Kasir (Operasional)</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Rentang Tanggal</label>
                <div class="flex items-center gap-1">
                    <input type="date" wire:model.live="dateStart" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                    <span class="text-xs text-muted-foreground">-</span>
                    <input type="date" wire:model.live="dateEnd" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Activity Feed -->
    <div class="space-y-3">
        @forelse($logs as $log)
            <article wire:key="log-post-{{ $log->id }}"
                wire:click="openDetail({{ $log->id }})"
                class="group bg-card rounded-2xl border border-border/70 p-4 sm:p-5 shadow-xs hover:border-primary/40 hover:shadow-md transition-all duration-200 cursor-pointer relative overflow-hidden">
                
                <!-- Card Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Avatar Inisial -->
                        <div class="h-10 w-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0 font-bold text-xs text-primary group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($log->formatted_actor, 0, 2)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-foreground leading-tight">{{ $log->formatted_actor }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-foreground/5 text-muted-foreground uppercase tracking-tight">{{ $log->formatted_role }}</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground/80 font-medium leading-none mt-1">
                                {{ $log->created_at->diffForHumans() }} • <span class="text-[10px] opacity-75">{{ $log->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                            </p>
                        </div>
                    </div>

                    <!-- Category Badge Tag -->
                    <div class="self-start sm:self-auto pl-[52px] sm:pl-0 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $log->action_badge['class'] }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
                            <span>{{ $log->action_badge['label'] }}</span>
                        </span>
                    </div>
                </div>

                <!-- Card Content (Narasi Manusiawi & Jelas) -->
                <div class="mt-2 text-xs sm:text-sm text-foreground leading-relaxed pl-0 sm:pl-[52px]">
                    <p class="font-medium text-foreground">
                        {{ $log->human_description }}
                    </p>
                </div>

                <!-- Card Footer -->
                <div class="mt-2.5 pt-2 border-t border-border/40 flex items-center justify-between text-[11px] text-muted-foreground pl-0 sm:pl-[52px]">
                    <span class="text-[10px] text-muted-foreground/60">{{ $log->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                    @if($log->has_changes)
                        <span class="text-primary font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform text-xs">
                            <span>Lihat Rincian Perubahan</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    @else
                        <span class="text-[10px] text-muted-foreground/50">Tercatat di sistem</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-border bg-card p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h3 class="text-sm font-bold text-foreground">Tidak Ada Catatan Aktivitas</h3>
                <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">Belum ada aktivitas yang sesuai dengan filter pencarian Anda saat ini.</p>
                <button wire:click="resetFilters" class="mt-4 px-4 py-2 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground transition-all">
                    Kembalikan Semua Filter
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 px-1 text-xs">
        <p class="text-muted-foreground font-medium">
            Menampilkan <span class="font-bold text-foreground">{{ $logs->firstItem() ?? 0 }}</span> - <span class="font-bold text-foreground">{{ $logs->lastItem() ?? 0 }}</span> dari <span class="font-bold text-foreground">{{ $logs->total() }}</span> total catatan
        </p>

        <div class="flex items-center gap-1.5">
            <button wire:click="previousPage" @disabled($logs->onFirstPage())
                class="h-8 px-3 rounded-full border border-border/80 bg-card text-xs font-semibold text-foreground hover:bg-foreground/5 disabled:opacity-30 disabled:pointer-events-none transition-all">
                ‹ Halaman Sebelumnya
            </button>
            <button wire:click="nextPage" @disabled(!$logs->hasMorePages())
                class="h-8 px-3 rounded-full border border-border/80 bg-card text-xs font-semibold text-foreground hover:bg-foreground/5 disabled:opacity-30 disabled:pointer-events-none transition-all">
                Halaman Berikutnya ›
            </button>
        </div>
    </div>

    <!-- Detail Activity Modal -->
    @if($selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200"
            wire:click.self="closeDetail">
            
            <div class="w-full max-w-lg rounded-3xl border border-border/80 bg-card dark:bg-[#1c1c1e] shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-5 border-b border-border/50">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($selectedLog->formatted_actor, 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground leading-tight">Rincian Aktivitas Tim</h3>
                            <p class="text-[11px] text-muted-foreground mt-0.5">Catatan #{{ $selectedLog->id }} • {{ $selectedLog->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="h-8 w-8 rounded-full flex items-center justify-center text-muted-foreground hover:bg-foreground/5 hover:text-foreground transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto space-y-4">
                    <!-- Ringkasan Info Pelaku & Aksi -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-foreground/5 border border-border/50 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Pelaku Aksi</span>
                            <p class="font-semibold text-foreground mt-0.5">{{ $selectedLog->formatted_actor }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Peran Pengguna</span>
                            <p class="font-semibold text-foreground mt-0.5">{{ $selectedLog->formatted_role }}</p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Jenis Kegiatan</span>
                            <p class="font-semibold text-foreground mt-0.5 text-xs text-primary">{{ $selectedLog->action_badge['label'] }}</p>
                        </div>
                    </div>

                    <!-- Penjelasan Narasi -->
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Ringkasan Kegiatan</span>
                        <div class="p-3.5 rounded-2xl bg-muted/30 border border-border/60 text-xs leading-relaxed text-foreground font-medium">
                            {{ $selectedLog->human_description }}
                        </div>
                    </div>

                    <!-- Perbandingan Data (Hanya yang Berubah) -->
                    @if($selectedLog->has_changes)
                        <div class="space-y-2 pt-2">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Perubahan Data yang Terjadi</span>
                                <p class="text-[11px] text-muted-foreground mt-0.5">Hanya menampilkan rincian bagian yang nilainya diubah oleh staf.</p>
                            </div>

                            <div class="rounded-2xl border border-border/60 overflow-hidden text-xs">
                                <table class="w-full text-left font-sans">
                                    <thead class="bg-muted/50 border-b border-border/60 text-[10px] font-bold uppercase text-muted-foreground">
                                        <tr>
                                            <th class="px-3.5 py-2.5">Bagian yang Diubah</th>
                                            <th class="px-3.5 py-2.5 text-rose-500">Nilai Semula</th>
                                            <th class="px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400">Nilai Terbaru</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/40 text-[11px]">
                                        @foreach($selectedLog->changed_fields as $field)
                                            <tr class="bg-primary/5 font-semibold">
                                                <td class="px-3.5 py-2.5 font-bold text-foreground">
                                                    {{ $field['label'] }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-rose-500 line-through">
                                                    {{ $field['before'] }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400 font-bold">
                                                    {{ $field['after'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif(!empty($selectedLog->data_before) || !empty($selectedLog->data_after))
                        <div class="p-3.5 rounded-2xl bg-muted/20 border border-border/40 text-center text-xs text-muted-foreground">
                            Tidak ada perubahan nilai pada rincian data ini.
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-border/50 flex justify-end">
                    <button wire:click="closeDetail" class="px-5 py-2 rounded-full text-xs font-semibold bg-foreground/10 hover:bg-foreground/15 text-foreground transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
