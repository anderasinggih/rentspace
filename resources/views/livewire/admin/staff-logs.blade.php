<div class="max-w-4xl mx-auto px-2 sm:px-4 py-2 sm:py-4">
    <!-- Header: Title & Quick Refresh -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-black text-foreground tracking-tight flex items-center gap-2">
                <span>📋</span>
                <span>Catatan Aktivitas Admin & Tim</span>
            </h1>
            <p class="text-xs text-muted-foreground mt-0.5">
                Pantau setiap aksi yang dilakukan tim kasir & admin (ubah sewa, serah unit, pembayaran, dll).
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
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
    </div>

    <!-- Category Filter Pills -->
    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar pb-2 mb-4">
        <button wire:click="setCategory('all')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'all' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <span>Semua Aktivitas</span>
            <span class="text-[10px] opacity-75 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="setCategory('transaksi')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'transaksi' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
            <span>Transaksi Sewa</span>
            <span class="text-[10px] opacity-75 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['transaksi'] }}</span>
        </button>

        <button wire:click="setCategory('unit')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'unit' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
            <span>Unit iPhone</span>
            <span class="text-[10px] opacity-75 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['unit'] }}</span>
        </button>

        <button wire:click="setCategory('promo')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'promo' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            <span>Promo & Kupon</span>
            <span class="text-[10px] opacity-75 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['promo'] }}</span>
        </button>

        <button wire:click="setCategory('system')"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {{ $category === 'system' ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border border-border/80 text-muted-foreground hover:text-foreground hover:bg-foreground/5' }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Pengaturan Sistem</span>
            <span class="text-[10px] opacity-75 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">{{ $counts['system'] }}</span>
        </button>
    </div>

    <!-- Search & Filter Card -->
    <div x-data="{ showAdvanced: false }" class="rounded-2xl border border-border/80 bg-card p-3 sm:p-4 mb-5 shadow-xs transition-all">
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
                    :class="showAdvanced || '{{ $selectedRole || $selectedUser || $dateFrom || $dateTo }}' ? 'bg-primary/10 text-primary border-primary/30' : 'bg-background'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Filter Lanjutan</span>
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
                    <input type="date" wire:model.live="dateFrom" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                    <span class="text-xs text-muted-foreground">-</span>
                    <input type="date" wire:model.live="dateTo" class="w-full h-8 rounded-lg border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Activity Feed -->
    <div class="space-y-3">
        @forelse($logs as $log)
            @php
                $userName = $log->user ? $log->user->name : 'Sistem Otomatis';
                $userRole = match($log->user?->role) {
                    'admin' => 'Administrator',
                    'staff' => 'Staf Kasir',
                    default => 'Sistem'
                };
                $action = $log->action;

                // Definisi label & badge ramah orang awam
                $badgeBg = 'bg-muted text-muted-foreground border-border/80';
                $badgeLabel = 'Aktivitas Sistem';
                $iconSvg = 'activity';

                if (str_contains($action, 'paid')) {
                    $badgeBg = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
                    $badgeLabel = 'Pembayaran Lunas';
                } elseif (str_contains($action, 'handover')) {
                    $badgeBg = 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
                    $badgeLabel = 'Penyerahan Unit';
                } elseif (str_contains($action, 'complete')) {
                    $badgeBg = 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20';
                    $badgeLabel = 'Pengembalian Selesai';
                } elseif (str_contains($action, 'cancel')) {
                    $badgeBg = 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
                    $badgeLabel = 'Transaksi Dibatalkan';
                } elseif (str_contains($action, 'extend')) {
                    $badgeBg = 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20';
                    $badgeLabel = 'Perpanjangan Sewa';
                } elseif (str_contains($action, 'edit_transaction')) {
                    $badgeBg = 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
                    $badgeLabel = 'Ubah Data Sewa';
                } elseif (str_contains($action, 'unit')) {
                    $badgeBg = 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20';
                    $badgeLabel = 'Manajemen Unit';
                } elseif (str_contains($action, 'promo') || str_contains($action, 'rule')) {
                    $badgeBg = 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
                    $badgeLabel = 'Promo & Harga';
                }

                $timeDiff = $log->created_at->diffForHumans();
                $fullTime = $log->created_at->translatedFormat('d F Y, H:i') . ' WIB';
                $hasDiff = !empty($log->data_before) || !empty($log->data_after);

                // Format kalimat deskripsi ramah orang awam
                $displayDesc = $log->description ?: 'Melakukan pembaruan pada data sistem.';
                
                // Normalisasi istilah "via Monitoring" atau kata teknis lainnya
                $displayDesc = str_ireplace('via Monitoring', 'lewat Menu Monitoring', $displayDesc);
                $displayDesc = str_ireplace('via QuickScan', 'lewat Scan Cepat Barcode', $displayDesc);
                $displayDesc = str_ireplace('via Transaksi', 'lewat Halaman Transaksi', $displayDesc);

                if ($log->target && ($log->target_type === 'App\Models\Rental' || $log->target_type === 'Rental' || str_contains($log->action, 'transaction') || str_contains($log->action, 'rental'))) {
                    $targetRental = $log->target;
                    if ($targetRental) {
                        $rentalIdent = $targetRental->nama . ' (Kode: ' . $targetRental->booking_code . ')';
                        if (preg_match('/#\d+/', $displayDesc)) {
                            $displayDesc = preg_replace('/#\d+/', $rentalIdent, $displayDesc);
                        }
                    }
                }

                // Hitung perubahan spesifik untuk ringkasan cepat
                $diffHighlights = [];
                if ($hasDiff) {
                    $b = $log->data_before ?? [];
                    $a = $log->data_after ?? [];
                    if (isset($b['denda']) && isset($a['denda']) && (float)$b['denda'] !== (float)$a['denda']) {
                        $diffHighlights[] = 'Denda telat disesuaikan dari Rp' . number_format((float)$b['denda'], 0, ',', '.') . ' jadi Rp' . number_format((float)$a['denda'], 0, ',', '.');
                    }
                    if (isset($b['denda_kerusakan']) && isset($a['denda_kerusakan']) && (float)$b['denda_kerusakan'] !== (float)$a['denda_kerusakan']) {
                        $diffHighlights[] = 'Denda kerusakan diubah jadi Rp' . number_format((float)$a['denda_kerusakan'], 0, ',', '.');
                    }
                    if (isset($b['status']) && isset($a['status']) && $b['status'] !== $a['status']) {
                        $diffHighlights[] = 'Status berubah dari "' . ucfirst($b['status']) . '" menjadi "' . ucfirst($a['status']) . '"';
                    }
                    if (isset($b['grand_total']) && isset($a['grand_total']) && (float)$b['grand_total'] !== (float)$a['grand_total']) {
                        $diffHighlights[] = 'Total bayar disesuaikan jadi Rp' . number_format((float)$a['grand_total'], 0, ',', '.');
                    }
                }
            @endphp

            <!-- Post Card -->
            <article wire:key="log-post-{{ $log->id }}"
                wire:click="openDetail({{ $log->id }})"
                class="group bg-card rounded-2xl border border-border/70 p-4 sm:p-5 shadow-xs hover:border-primary/40 hover:shadow-md transition-all duration-200 cursor-pointer relative overflow-hidden">
                
                <!-- Post Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- User Avatar -->
                        <div class="h-10 w-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0 font-bold text-xs text-primary group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($userName, 0, 2)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-foreground leading-tight">{{ $userName }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-foreground/5 text-muted-foreground uppercase tracking-tight">{{ $userRole }}</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground/80 font-medium leading-none mt-1" title="{{ $fullTime }}">
                                {{ $timeDiff }} • <span class="text-[10px] opacity-75">{{ $fullTime }}</span>
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

                <!-- Post Narrative Content -->
                <div class="mt-3 text-xs sm:text-sm text-foreground font-normal leading-relaxed pl-0 sm:pl-[52px]">
                    <p class="font-medium text-foreground">
                        {{ $displayDesc }}
                    </p>

                    <!-- Target Penyewa Tag -->
                    @if($log->target && ($log->target_type === 'App\Models\Rental' || $log->target_type === 'Rental'))
                        <div class="mt-2.5 flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/10 text-primary border border-primary/20 text-[11px] font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <span>Penyewa: {{ $log->target->nama ?? 'Penyewa' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-muted text-muted-foreground border border-border/80 font-mono text-[10px] font-medium">
                                Kode: #{{ $log->target->booking_code ?? '-' }}
                            </span>
                        </div>
                    @endif

                    <!-- Highlight Perubahan Cepat (Ramah Orang Awam) -->
                    @if(count($diffHighlights) > 0)
                        <div class="mt-2.5 p-2.5 rounded-xl bg-amber-500/5 border border-amber-500/20 text-xs space-y-1">
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Rincian Perubahan:</span>
                            @foreach($diffHighlights as $dh)
                                <div class="flex items-center gap-1.5 text-foreground/90 font-medium text-[11px]">
                                    <span class="text-amber-500 font-bold">•</span>
                                    <span>{{ $dh }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif($hasDiff)
                        <div class="mt-2 flex items-center gap-1.5 text-[11px] text-primary font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            <span>Ada perbandingan nilai data sebelum & sesudah</span>
                        </div>
                    @endif
                </div>

                <!-- Footer: Klik untuk Detail -->
                <div class="mt-3 pt-2.5 border-t border-border/40 flex items-center justify-between text-[11px] text-muted-foreground pl-0 sm:pl-[52px]">
                    <span class="text-[10px] text-muted-foreground/70">Nomor Catatan #{{ $log->id }}</span>
                    <span class="text-primary font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform text-xs">
                        <span>Lihat Rincian Lengkap</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
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

    <!-- Detail Activity Modal (Mudah Dipahami Orang Awam) -->
    @if($selectedLog)
        @php
            $mUserName = $selectedLog->user ? $selectedLog->user->name : 'Sistem Otomatis';
            $mUserRole = match($selectedLog->user?->role) {
                'admin' => 'Administrator (Pemilik Toko)',
                'staff' => 'Staf Kasir (Operasional)',
                default => 'Sistem Otomatis'
            };
            $mActionName = match($selectedLog->action) {
                'mark_as_paid' => 'Validasi Pembayaran Lunas',
                'handover_unit' => 'Penyerahan / Pengambilan Unit',
                'complete_rental' => 'Penyelesaian Sewa (Unit Kembali)',
                'cancel_transaction' => 'Pembatalan Transaksi',
                'edit_transaction' => 'Perubahan Data Transaksi',
                'extend_rental' => 'Perpanjangan Waktu Sewa',
                'create_unit', 'add_unit' => 'Penambahan Unit Baru',
                'edit_unit', 'update_unit' => 'Perubahan Info Unit',
                'delete_unit' => 'Penghapusan Unit ke Tong Sampah',
                'restore_unit' => 'Pemulihan Unit dari Sampah',
                'manage_category' => 'Pengaturan Kategori Unit',
                'add_promo' => 'Pembuatan Kupon Promo',
                'update_setting' => 'Pembaruan Pengaturan Sistem',
                default => ucwords(str_replace('_', ' ', $selectedLog->action))
            };

            $mDesc = $selectedLog->description ?: 'Tidak ada catatan tambahan.';
            $mDesc = str_ireplace('via Monitoring', 'lewat Menu Monitoring', $mDesc);
            $mDesc = str_ireplace('via QuickScan', 'lewat Scan Cepat Barcode', $mDesc);
            $mDesc = str_ireplace('via Transaksi', 'lewat Halaman Transaksi', $mDesc);

            // Kamus terjemahan nama kolom database ke bahasa Indonesia kasir
            $fieldLabels = [
                'nama' => 'Nama Penyewa',
                'subtotal' => 'Harga Sewa Dasar',
                'potongan_diskon' => 'Potongan Diskon',
                'diskon' => 'Potongan Diskon',
                'denda' => 'Denda Keterlambatan',
                'denda_kerusakan' => 'Denda Kerusakan Unit',
                'catatan_kerusakan' => 'Keterangan Kerusakan',
                'grand_total' => 'Total Akhir Pembayaran',
                'status' => 'Status Sewa',
                'metode_pembayaran' => 'Metode Pembayaran',
                'waktu_mulai' => 'Waktu Mulai Sewa',
                'waktu_selesai' => 'Waktu Berakhir Sewa',
                'no_wa' => 'Nomor WhatsApp',
                'alamat' => 'Alamat Penyewa',
                'unit_id' => 'ID Unit',
                'seri' => 'Seri / Model iPhone',
                'warna' => 'Warna Unit',
                'kondisi' => 'Kondisi Unit',
                'harga_per_hari' => 'Tarif Sewa Harian',
                'harga_per_jam' => 'Tarif Sewa Per Jam',
                'is_active' => 'Status Ketersediaan'
            ];

            // Helper format nilai agar tidak mentah angka polos
            $formatValue = function($key, $val) {
                if ($val === null || $val === '') return 'Kosong (Rp 0)';
                if (in_array($key, ['subtotal', 'diskon', 'potongan_diskon', 'denda', 'denda_kerusakan', 'grand_total', 'harga_per_hari', 'harga_per_jam'])) {
                    return 'Rp' . number_format((float)$val, 0, ',', '.');
                }
                if ($key === 'status') {
                    return match($val) {
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Sudah Dibayar (Lunas)',
                        'renting' => 'Sedang Disewa (Unit di Pelanggan)',
                        'completed' => 'Selesai (Unit Sudah Kembali)',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($val)
                    };
                }
                if ($key === 'is_active') {
                    return $val ? 'Aktif (Bisa Disewa)' : 'Nonaktif';
                }
                if (is_array($val)) {
                    return json_encode($val);
                }
                return (string)$val;
            };
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200"
            wire:click.self="closeDetail">
            
            <div class="w-full max-w-lg rounded-3xl border border-border/80 bg-card dark:bg-[#1c1c1e] shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-5 border-b border-border/50">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($mUserName, 0, 2)) }}
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
                    <!-- Kartu Info Ringkas -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-foreground/5 border border-border/50 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Pelaku Aksi</span>
                            <p class="font-semibold text-foreground mt-0.5">{{ $mUserName }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Peran Pengguna</span>
                            <p class="font-semibold text-foreground mt-0.5">{{ $mUserRole }}</p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Jenis Kegiatan</span>
                            <p class="font-semibold text-foreground mt-0.5 text-xs text-primary">{{ $mActionName }}</p>
                        </div>
                    </div>

                    <!-- Penjelasan Narasi -->
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Ringkasan Kegiatan</span>
                        <div class="p-3.5 rounded-2xl bg-muted/30 border border-border/60 text-xs leading-relaxed text-foreground font-medium">
                            {{ $mDesc }}
                        </div>

                        @if($selectedLog->target && ($selectedLog->target_type === 'App\Models\Rental' || $selectedLog->target_type === 'Rental'))
                            <div class="flex items-center gap-2 pt-1 flex-wrap">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground">Data Terkait:</span>
                                <span class="px-2.5 py-0.5 rounded-md bg-primary/10 text-primary text-xs font-semibold">
                                    {{ $selectedLog->target->nama }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-muted text-muted-foreground font-mono text-[10px]">
                                    #{{ $selectedLog->target->booking_code }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Perbandingan Data Sebelum & Sesudah (Sangat Jelas & Manusiawi) -->
                    @php
                        $before = $selectedLog->data_before ?? [];
                        $after = $selectedLog->data_after ?? [];
                        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
                    @endphp

                    @if(count($allKeys) > 0)
                        <div class="space-y-2 pt-2">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Perubahan Data yang Terjadi</span>
                                <p class="text-[11px] text-muted-foreground mt-0.5">Daftar nilai sebelum diubah dibanding setelah disimpan oleh staf.</p>
                            </div>

                            <div class="rounded-2xl border border-border/60 overflow-hidden text-xs">
                                <table class="w-full text-left font-sans">
                                    <thead class="bg-muted/50 border-b border-border/60 text-[10px] font-bold uppercase text-muted-foreground">
                                        <tr>
                                            <th class="px-3.5 py-2.5">Bagian yang Diubah</th>
                                            <th class="px-3.5 py-2.5">Nilai Semula</th>
                                            <th class="px-3.5 py-2.5 text-primary">Nilai Terbaru</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/40 text-[11px]">
                                        @foreach($allKeys as $key)
                                            @php
                                                $bVal = $before[$key] ?? null;
                                                $aVal = $after[$key] ?? null;
                                                $isDiff = (string)$bVal !== (string)$aVal;
                                                $labelName = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                                $formattedBefore = $formatValue($key, $bVal);
                                                $formattedAfter = $formatValue($key, $aVal);
                                            @endphp
                                            <tr class="{{ $isDiff ? 'bg-primary/5 font-semibold' : 'opacity-70' }}">
                                                <td class="px-3.5 py-2.5 font-bold text-foreground">
                                                    {{ $labelName }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-rose-500 line-through">
                                                    {{ $formattedBefore }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-emerald-600 dark:text-emerald-400 font-bold">
                                                    {{ $formattedAfter }}
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
</div>f
</div>
