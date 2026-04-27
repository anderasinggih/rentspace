<div class="p-2 sm:p-6">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-foreground">Audit Trail: Staff Activity Logs</h1>
            <p class="mt-2 text-sm text-muted-foreground italic">Pelacakan otomatis untuk semua tindakan yang dilakukan oleh staff di sistem admin.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 bg-background rounded-xl border border-border p-3 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <!-- Search -->
            <div class="flex-1 min-w-[240px] group">
                <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1">Cari Tindakan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        class="block w-full h-8 pl-8 pr-3 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                        placeholder="Cari aksi, detail, atau staff...">
                </div>
            </div>

            <!-- Filter User -->
            <div class="w-full md:w-44">
                <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1">Staff</label>
                <select wire:model.live="selectedUser" class="block w-full h-8 px-2.5 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none cursor-pointer">
                    <option value="">Semua Staff</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Role -->
            <div class="w-full md:w-32">
                <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1">Role</label>
                <select wire:model.live="selectedRole" class="block w-full h-8 px-2.5 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none cursor-pointer">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="w-full md:w-auto flex items-end gap-2 flex-1 min-w-[280px]">
                <div class="flex-1">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1 text-center md:text-left">Dari</label>
                    <input type="date" wire:model.live="dateFrom" class="block w-full h-8 px-2 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none">
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2 block ml-1 text-center md:text-left">Sampai</label>
                    <input type="date" wire:model.live="dateTo" class="block w-full h-8 px-2 text-[11px] font-medium rounded-lg border border-input bg-muted/20 focus:bg-background shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none">
                </div>
            </div>

            <!-- Reset -->
            <button wire:click="resetFilters" 
                class="w-full md:w-auto h-8 px-4 rounded-lg border border-border bg-background text-[10px] font-black uppercase tracking-widest hover:bg-muted active:scale-95 transition-all shadow-sm">
                Reset
            </button>
        </div>
    </div>

    <div class="overflow-hidden shadow ring-1 ring-border rounded-xl bg-background">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Waktu</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Staff</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Tindakan</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">Detail Perubahan</th>
                    <th class="px-4 py-3.5 text-left text-xs font-bold text-muted-foreground uppercase tracking-widest">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border bg-background">
                @forelse($logs as $log)
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4 text-xs">
                            <div class="font-semibold text-foreground">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-muted-foreground opacity-70">{{ $log->created_at->format('H:i:s') }} WIB</div>
                        </td>
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-[10px]">
                                    {{ substr($log->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-foreground leading-none">{{ $log->user->name }}</div>
                                    <div class="text-[10px] text-muted-foreground mt-1 lowercase">{{ $log->user->role }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-tighter
                                {{ str_contains($log->action, 'paid') || str_contains($log->action, 'handover') ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                {{ str_contains($log->action, 'cancel') ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                {{ str_contains($log->action, 'edit') || str_contains($log->action, 'complete') ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : '' }}
                                {{ !str_contains($log->action, 'paid') && !str_contains($log->action, 'handover') && !str_contains($log->action, 'cancel') && !str_contains($log->action, 'edit') && !str_contains($log->action, 'complete') ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-400' : '' }}
                            ">
                                {{ $log->action === 'handover_unit' ? 'validasi ambil' : str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td class="px-3 sm:px-4 py-4 text-xs text-muted-foreground leading-relaxed">
                            {{ $log->description }}
                        </td>
                        <td class="whitespace-nowrap px-3 sm:px-4 py-4 text-[10px] font-mono text-muted-foreground">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-muted-foreground">
                            Belum ada log aktivitas staff yang tersimpan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-border mt-4 overflow-hidden shadow ring-1 ring-border rounded-xl bg-background">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 px-2">
                <!-- Left: Rows & Info -->
                <div class="flex items-center gap-6 order-2 md:order-1">
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none">Rows</label>
                        <select wire:model.live="perPage" class="h-8 rounded-lg border border-border bg-background px-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all shadow-sm uppercase">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none opacity-70">
                            Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
                        </p>
                    </div>
                </div>

                <!-- Right: Navigation -->
                <div class="flex items-center gap-3 order-1 md:order-2">
                    <button wire:click="previousPage" @disabled($logs->onFirstPage())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    
                    <div class="flex items-center gap-2 px-3 h-8 bg-muted/50 rounded-lg border border-border/50">
                        <span class="text-xs font-black text-foreground">{{ $logs->currentPage() }}</span>
                        <span class="text-[10px] font-bold text-muted-foreground uppercase opacity-50">/</span>
                        <span class="text-xs font-black text-foreground">{{ $logs->lastPage() }}</span>
                    </div>

                    <button wire:click="nextPage" @disabled(!$logs->hasMorePages())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
    </div>
</div>
