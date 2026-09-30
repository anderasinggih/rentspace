<div class="max-w-4xl mx-auto px-2 sm:px-4 py-2 sm:py-4">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-end mb-3">
        <button wire:click="$refresh" 
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground border border-border/80 transition-all duration-200 active:scale-95 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                <path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                <path d="M8 16H3v5"/>
            </svg>
            <span>Segarkan</span>
        </button>
    </div>

    <!-- Category Filter Pills (DailyPhone Style) -->
    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar pb-2 mb-4">
        <button wire:click="setCategory('all')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'all' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <span>Semua</span>
            <span class="text-[10px] opacity-75 px-1 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="setCategory('transaksi')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'transaksi' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
            <span>Transaksi & Sewa</span>
            <span class="text-[10px] opacity-75 px-1 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['transaksi'] }}</span>
        </button>

        <button wire:click="setCategory('unit')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'unit' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
            <span>Inventory Unit</span>
            <span class="text-[10px] opacity-75 px-1 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['unit'] }}</span>
        </button>

        <button wire:click="setCategory('promo')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'promo' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            <span>Promo & Harga</span>
            <span class="text-[10px] opacity-75 px-1 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['promo'] }}</span>
        </button>

        <button wire:click="setCategory('system')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'system' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Sistem & Lainnya</span>
            <span class="text-[10px] opacity-75 px-1 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['system'] }}</span>
        </button>
    </div>

    <!-- Search & Filter Card (Rounded-2xl Apple Material) -->
    <div x-data="{ showAdvanced: false }" class="rounded-2xl border border-border/80 bg-card p-3 sm:p-4 mb-5 shadow-xs transition-all">
        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative flex-1 w-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari aktivitas, nama staff, IP address..."
                    class="w-full h-9 rounded-xl border border-border/80 bg-background/60 pl-9 pr-3 text-xs text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button @click="showAdvanced = !showAdvanced"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-border/80 text-xs font-semibold text-foreground hover:bg-foreground/5 transition-all"
                    :class="showAdvanced || '{{ $selectedRole || $selectedUser || $dateFrom || $dateTo }}' ? 'bg-primary/10 text-primary border-primary/30' : 'bg-background'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Filter</span>
                </button>

                @if($search || $selectedRole || $selectedUser || $dateFrom || $dateTo || $category !== 'all')
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
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Staff / User</label>
                <select wire:model.live="selectedUser" class="w-full h-8 rounded-lg border border-border bg-background px-2.5 text-xs text-foreground focus:outline-none focus:border-primary">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Role Akun</label>
                <select wire:model.live="selectedRole" class="w-full h-8 rounded-lg border border-border bg-background px-2.5 text-xs text-foreground focus:outline-none focus:border-primary">
                    <option value="">Semua Role</option>
                    <option value="admin">Administrator</option>
                    <option value="staff">Staff Operasional</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Tanggal</label>
                <div class="flex items-center gap-1">
                    <input type="date" wire:model.live="dateFrom" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                    <span class="text-xs text-muted-foreground">-</span>
                    <input type="date" wire:model.live="dateTo" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Posts Feed (Social/Activity Post Stream ala DailyPhone) -->
    <div class="space-y-3">
        @forelse($logs as $log)
            @php
                $userName = $log->user ? $log->user->name : 'System Robot';
                $userRole = $log->user ? $log->user->role : 'system';
                $action = $log->action;

                // Category & Badge style logic
                $badgeBg = 'bg-muted text-muted-foreground border-border/80';
                $badgeLabel = str_replace('_', ' ', $action);
                $iconClass = 'text-primary';

                if (str_contains($action, 'paid')) {
                    $badgeBg = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
                    $badgeLabel = 'Pembayaran Lunas';
                    $iconClass = 'text-emerald-500';
                } elseif (str_contains($action, 'handover')) {
                    $badgeBg = 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
                    $badgeLabel = 'Unit Diambil / Diserahkan';
                    $iconClass = 'text-blue-500';
                } elseif (str_contains($action, 'complete')) {
                    $badgeBg = 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20';
                    $badgeLabel = 'Sewa Selesai (Kembali)';
                    $iconClass = 'text-teal-500';
                } elseif (str_contains($action, 'cancel')) {
                    $badgeBg = 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
                    $badgeLabel = 'Transaksi Dibatalkan';
                    $iconClass = 'text-rose-500';
                } elseif (str_contains($action, 'unit')) {
                    $badgeBg = 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20';
                    $badgeLabel = 'Manajemen Unit';
                    $iconClass = 'text-purple-500';
                } elseif (str_contains($action, 'promo') || str_contains($action, 'rule')) {
                    $badgeBg = 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
                    $badgeLabel = 'Promo & Pricing';
                    $iconClass = 'text-amber-500';
                }

                $timeDiff = $log->created_at->diffForHumans();
                $fullTime = $log->created_at->translatedFormat('d M Y, H:i') . ' WIB';
                $hasDiff = !empty($log->data_before) || !empty($log->data_after);
            @endphp

            <!-- Post Card -->
            <article wire:key="log-post-{{ $log->id }}"
                wire:click="openDetail({{ $log->id }})"
                class="group bg-card rounded-2xl border border-border/70 p-4 sm:p-5 shadow-xs hover:border-primary/40 hover:shadow-md transition-all duration-200 cursor-pointer relative overflow-hidden">
                
                <!-- Post Header: Avatar, Name, Category Pill, Relative Time -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- User Avatar Initials -->
                        <div class="h-10 w-10 rounded-full bg-foreground/5 border border-border/80 flex items-center justify-center shrink-0 font-bold text-xs text-foreground group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($userName, 0, 2)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-foreground leading-tight">{{ $userName }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-foreground/5 text-muted-foreground uppercase tracking-tight">{{ $userRole }}</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground/70 font-medium leading-none mt-1" title="{{ $fullTime }}">
                                {{ $timeDiff }} • <span class="font-mono text-[10px]">{{ $log->ip_address ?? 'Local' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Category Badge Tag -->
                    <div class="self-start sm:self-auto pl-[52px] sm:pl-0 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badgeBg }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
                            <span>{{ $badgeLabel }}</span>
                        </span>
                    </div>
                </div>

                @php
                    $displayDesc = $log->description ?: 'Melakukan operasi ' . $action . ' pada sistem.';
                    
                    // Jika log adalah transaksi rental (atau deskripsi masih berisi format lama #123)
                    if ($log->target_type === 'App\Models\Rental' || $log->target_type === 'Rental' || str_contains($log->action, 'transaction') || str_contains($log->action, 'rental') || str_contains($log->action, 'paid') || str_contains($log->action, 'handover')) {
                        $targetRental = $log->target;
                        if ($targetRental) {
                            $rentalIdent = ($targetRental->nama ? $targetRental->nama : 'Penyewa') . ' (' . $targetRental->booking_code . ')';
                            // Ganti pola #123 atau transaksi #123 dengan Nama (Kode Booking)
                            if (preg_match('/#\d+/', $displayDesc)) {
                                $displayDesc = preg_replace('/#\d+/', $rentalIdent, $displayDesc);
                            }
                        }
                    }
                @endphp

                <!-- Post Content / Narrative Body -->
                <div class="mt-3 text-xs sm:text-sm text-foreground/90 font-normal leading-relaxed pl-0 sm:pl-[52px]">
                    <div class="flex items-start gap-2">
                        <p class="flex-1 font-medium text-foreground/90">
                            {{ $displayDesc }}
                        </p>
                    </div>

                    <!-- Target / Booking Badge if available -->
                    @if($log->target && ($log->target_type === 'App\Models\Rental' || $log->target_type === 'Rental'))
                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/10 text-primary border border-primary/20 text-[11px] font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <span>{{ $log->target->nama ?? 'Penyewa' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-muted text-muted-foreground border border-border/80 font-mono text-[10px] font-medium">
                                {{ $log->target->booking_code ?? '-' }}
                            </span>
                        </div>
                    @endif

                    <!-- Quick Changes Preview Tag if available -->
                    @if($hasDiff)
                        <div class="mt-2.5 flex items-center gap-1.5 text-[11px] text-primary/90 font-medium bg-primary/5 border border-primary/15 rounded-xl px-2.5 py-1.5 w-fit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/>
                            </svg>
                            <span>Terdapat audit perubahan data sebelum & sesudah</span>
                        </div>
                    @endif
                </div>

                <!-- Post Footer Action -->
                <div class="mt-3 pt-2.5 border-t border-border/40 flex items-center justify-between text-[11px] text-muted-foreground pl-0 sm:pl-[52px]">
                    <span class="font-mono text-[10px] text-muted-foreground/60">Log #{{ $log->id }}</span>
                    <span class="text-primary font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform text-xs">
                        <span>Lihat Rincian</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-border bg-card p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                </div>
                <h3 class="text-sm font-bold text-foreground">Tidak Ada Aktivitas Ditemukan</h3>
                <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">Belum ada rekaman log pada filter ini atau kriteria pencarian tidak cocok.</p>
                <button wire:click="resetFilters" class="mt-4 px-4 py-2 rounded-full text-xs font-semibold bg-foreground/5 hover:bg-foreground/10 text-foreground transition-all">
                    Reset Filter
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination (Apple Minimalist) -->
    <div class="mt-6 flex items-center justify-between gap-4 px-1">
        <p class="text-xs text-muted-foreground font-medium">
            Menampilkan <span class="font-bold text-foreground">{{ $logs->firstItem() ?? 0 }}</span> - <span class="font-bold text-foreground">{{ $logs->lastItem() ?? 0 }}</span> dari <span class="font-bold text-foreground">{{ $logs->total() }}</span> aktivitas
        </p>

        <div class="flex items-center gap-1.5">
            <button wire:click="previousPage" @disabled($logs->onFirstPage())
                class="h-8 px-3 rounded-full border border-border/80 bg-card text-xs font-semibold text-foreground hover:bg-foreground/5 disabled:opacity-30 disabled:pointer-events-none transition-all">
                ‹ Sebelumnya
            </button>
            <button wire:click="nextPage" @disabled(!$logs->hasMorePages())
                class="h-8 px-3 rounded-full border border-border/80 bg-card text-xs font-semibold text-foreground hover:bg-foreground/5 disabled:opacity-30 disabled:pointer-events-none transition-all">
                Berikutnya ›
            </button>
        </div>
    </div>

    <!-- Detail Activity Modal (Pure Apple Sheet / Dialog) -->
    @if($selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200"
            wire:click.self="closeDetail">
            
            <div class="w-full max-w-lg rounded-3xl border border-border/80 bg-card/95 dark:bg-[#1c1c1e]/95 backdrop-blur-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-5 border-b border-border/50">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($selectedLog->user->name ?? 'S', 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground leading-tight">Detail Aktivitas</h3>
                            <p class="text-[11px] text-muted-foreground mt-0.5">ID #{{ $selectedLog->id }} • {{ $selectedLog->created_at->format('d M Y, H:i') }} WIB</p>
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="h-8 w-8 rounded-full flex items-center justify-center text-muted-foreground hover:bg-foreground/5 hover:text-foreground transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto space-y-4">
                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-foreground/5 border border-border/50 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Staff / Pelaku</span>
                            <p class="font-semibold text-foreground mt-0.5">{{ $selectedLog->user->name ?? 'System' }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Role Akun</span>
                            <p class="font-semibold text-foreground mt-0.5 capitalize">{{ $selectedLog->user->role ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Jenis Tindakan</span>
                            <p class="font-semibold text-foreground mt-0.5 font-mono text-[11px]">{{ $selectedLog->action }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">IP Address</span>
                            <p class="font-semibold text-foreground mt-0.5 font-mono text-[11px]">{{ $selectedLog->ip_address ?? '127.0.0.1' }}</p>
                        </div>
                    </div>

                    @php
                        $detailDesc = $selectedLog->description ?: 'Tidak ada deskripsi tambahan.';
                        if ($selectedLog->target && ($selectedLog->target_type === 'App\Models\Rental' || $selectedLog->target_type === 'Rental')) {
                            $r = $selectedLog->target;
                            $ident = ($r->nama ? $r->nama : 'Penyewa') . ' (' . $r->booking_code . ')';
                            if (preg_match('/#\d+/', $detailDesc)) {
                                $detailDesc = preg_replace('/#\d+/', $ident, $detailDesc);
                            }
                        }
                    @endphp

                    <!-- Narrative Description -->
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Deskripsi Aktivitas</span>
                        <div class="p-3.5 rounded-2xl bg-card border border-border/60 text-xs leading-relaxed text-foreground font-medium">
                            {{ $detailDesc }}
                        </div>

                        @if($selectedLog->target && ($selectedLog->target_type === 'App\Models\Rental' || $selectedLog->target_type === 'Rental'))
                            <div class="flex items-center gap-2 pt-1">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground">Data Sewa:</span>
                                <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-xs font-semibold">
                                    {{ $selectedLog->target->nama }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-muted text-muted-foreground font-mono text-[10px]">
                                    {{ $selectedLog->target->booking_code }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Before & After Comparison Table if available -->
                    @php
                        $before = $selectedLog->data_before ?? [];
                        $after = $selectedLog->data_after ?? [];
                        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
                    @endphp

                    @if(count($allKeys) > 0)
                        <div class="space-y-1.5 pt-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Perubahan Data (Audit Diff)</span>
                            <div class="rounded-2xl border border-border/60 overflow-hidden text-xs">
                                <table class="w-full text-left">
                                    <thead class="bg-muted/50 border-b border-border/60 text-[10px] font-bold uppercase text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-2">Field</th>
                                            <th class="px-3 py-2">Sebelum</th>
                                            <th class="px-3 py-2 text-primary">Sesudah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/40 font-mono text-[11px]">
                                        @foreach($allKeys as $key)
                                            @php
                                                $bVal = $before[$key] ?? null;
                                                $aVal = $after[$key] ?? null;
                                                $isDiff = $bVal !== $aVal;
                                            @endphp
                                            <tr class="{{ $isDiff ? 'bg-primary/5' : '' }}">
                                                <td class="px-3 py-2 font-semibold text-foreground font-sans">{{ str_replace('_', ' ', $key) }}</td>
                                                <td class="px-3 py-2 text-rose-500 line-through opacity-80">
                                                    {{ is_array($bVal) ? json_encode($bVal) : ($bVal !== null ? (is_numeric($bVal) ? number_format($bVal, 0, ',', '.') : $bVal) : '-') }}
                                                </td>
                                                <td class="px-3 py-2 text-emerald-500 font-bold">
                                                    {{ is_array($aVal) ? json_encode($aVal) : ($aVal !== null ? (is_numeric($aVal) ? number_format($aVal, 0, ',', '.') : $aVal) : '-') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
