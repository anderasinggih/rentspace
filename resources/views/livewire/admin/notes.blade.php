<div class="max-w-4xl mx-auto px-2 sm:px-4 py-2 sm:py-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-xl bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="17" x2="12" y2="22"/>
                        <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
                    </svg>
                </span>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground">Notes Grup Reporting</h1>
            </div>
            <p class="text-xs text-muted-foreground mt-0.5">
                Pesan penting yang di-PIN di grup WhatsApp tim/reporting.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="$refresh" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-card hover:bg-foreground/5 text-foreground border border-border/80 transition-all duration-200 active:scale-95 shadow-xs">
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

    <!-- Info Banner if Group not registered -->
    @if(empty($reportGroupId))
        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 mb-5 flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500 shrink-0 mt-0.5">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div class="text-xs">
                <p class="font-semibold text-amber-600 dark:text-amber-400">ID Grup WA Report Belum Terdaftar</p>
                <p class="text-muted-foreground mt-0.5">
                    Ketik <code>!getid</code> di grup WhatsApp tim Anda, lalu daftarkan ID-nya pada menu 
                    <a href="{{ route('admin.settings', ['tab' => 'whatsapp']) }}" class="underline font-semibold text-foreground">Pengaturan &rarr; Tab WhatsApp &rarr; ID Grup WA Report</a>.
                </p>
            </div>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="rounded-2xl border border-border/80 bg-card p-3 sm:p-4 mb-5 shadow-xs">
        <div class="flex flex-col sm:flex-row items-center gap-2.5">
            <!-- Search -->
            <div class="relative flex-1 w-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari isi catatan, nama pengirim, atau no HP..."
                    class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-border/80 bg-background text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary shadow-xs">
            </div>

            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 w-full sm:w-auto shrink-0">
                <button wire:click="$set('filter', 'all')"
                    class="flex-1 sm:flex-initial px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 {{ $filter === 'all' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/60 text-muted-foreground hover:text-foreground' }}">
                    Semua ({{ $totalCount }})
                </button>
                <button wire:click="$set('filter', 'pinned')"
                    class="flex-1 sm:flex-initial px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 {{ $filter === 'pinned' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/60 text-muted-foreground hover:text-foreground' }}">
                    📌 Aktif Pinned ({{ $pinnedCount }})
                </button>
                <button wire:click="$set('filter', 'unpinned')"
                    class="flex-1 sm:flex-initial px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 {{ $filter === 'unpinned' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-muted/60 text-muted-foreground hover:text-foreground' }}">
                    Arsip
                </button>
            </div>
        </div>
    </div>

    <!-- Notes List -->
    <div class="space-y-3">
        @forelse($notes as $note)
            <div class="relative rounded-2xl border border-border/80 bg-card p-4 shadow-xs transition-all duration-200 hover:border-primary/40 {{ $note->is_pinned ? 'ring-1 ring-primary/20' : 'opacity-85' }}">
                
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-3 mb-2.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <!-- Avatar / Icon -->
                        <div class="h-8 w-8 rounded-full {{ $note->is_pinned ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground' }} flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr($note->sender_name ?: ($note->sender_phone ?: 'W'), 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-xs text-foreground truncate">
                                    {{ $note->sender_name ?: 'Anggota Grup' }}
                                </span>
                                @if($note->sender_phone)
                                    <span class="text-[10px] text-muted-foreground font-mono">
                                        ({{ $note->sender_phone }})
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-muted-foreground">
                                <span>{{ $note->created_at->format('d M Y, H:i') }}</span>
                                @if($note->pinned_at)
                                    <span>&bull;</span>
                                    <span class="text-primary font-medium">Di-pin: {{ $note->pinned_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Pin Badge & Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($note->is_pinned)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20">
                                <span>📌 Pinned</span>
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-muted text-muted-foreground">
                                <span>Unpinned</span>
                            </span>
                        @endif

                        <button wire:click="togglePin({{ $note->id }})" 
                            title="{{ $note->is_pinned ? 'Tandai sebagai tidak di-pin' : 'Tandai sebagai di-pin' }}"
                            class="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition">
                            @if($note->is_pinned)
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none" class="text-primary">
                                    <path d="M12 17v5"/>
                                    <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="17" x2="12" y2="22"/>
                                    <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
                                </svg>
                            @endif
                        </button>

                        <button wire:click="deleteNote({{ $note->id }})" wire:confirm="Hapus catatan ini dari daftar web?"
                            title="Hapus"
                            class="p-1.5 rounded-lg hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Message Body -->
                <div class="bg-background/60 rounded-xl p-3 border border-border/50 text-xs sm:text-sm text-foreground whitespace-pre-wrap leading-relaxed font-sans selection:bg-primary/20">
                    {{ $note->message_text }}
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-border/80 p-10 text-center bg-card/40">
                <div class="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="17" x2="12" y2="22"/>
                        <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"/>
                    </svg>
                </div>
                <p class="font-bold text-sm text-foreground">Belum Ada Notes Tersemat</p>
                <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
                    Saat pesan di-PIN di grup WhatsApp reporting oleh admin, pesan tersebut akan otomatis muncul di halaman ini secara real-time.
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $notes->links() }}
    </div>
</div>
