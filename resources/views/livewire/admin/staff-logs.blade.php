<div class="p-6">
    <div class="sm:flex sm:items-center mb-8">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-foreground">Audit Trail: Staff Activity Logs</h1>
            <p class="mt-2 text-sm text-muted-foreground italic">Pelacakan otomatis untuk semua tindakan yang dilakukan oleh staff di sistem admin.</p>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" 
                class="block w-full h-10 pl-10 pr-3 text-sm rounded-lg border border-input bg-background shadow-sm focus:ring-1 focus:ring-primary outline-none" 
                placeholder="Cari staff, tindakan, atau deskripsi...">
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
                        <td class="whitespace-nowrap px-4 py-4 text-xs">
                            <div class="font-semibold text-foreground">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-muted-foreground opacity-70">{{ $log->created_at->format('H:i:s') }} WIB</div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4">
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
                        <td class="whitespace-nowrap px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-tighter
                                {{ str_contains($log->action, 'paid') ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                {{ str_contains($log->action, 'cancel') ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                {{ str_contains($log->action, 'edit') ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : '' }}
                                {{ !str_contains($log->action, 'paid') && !str_contains($log->action, 'cancel') && !str_contains($log->action, 'edit') ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-400' : '' }}
                            ">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-xs text-muted-foreground leading-relaxed">
                            {{ $log->description }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-[10px] font-mono text-muted-foreground">
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

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
