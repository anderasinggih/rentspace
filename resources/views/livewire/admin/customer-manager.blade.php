<div class="space-y-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-foreground">Customer Insights</h1>
            <p class="mt-2 text-sm text-muted-foreground">Monitor customer loyalty, frequency, and lifetime value across all rentals.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex flex-col md:flex-row items-center gap-4">
            <div class="relative w-full md:w-80 group">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pelanggan..." 
                    class="flex h-9 w-full rounded-md border border-input bg-background px-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary transition-all shadow-sm">
            </div>
            
        </div>
    </div>

    <!-- Quick Stats Cards (Minimalist Shadcn) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="text-xs font-medium tracking-tight uppercase text-muted-foreground">Total Customers</h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="text-2xl font-bold">{{ $customers->total() }}</div>
        </div>
                        <div class="bg-muted/40 p-4 rounded-md border">
                            <h3 class="text-xs font-semibold uppercase text-muted-foreground">Orders Count</h3>
                            <div class="text-2xl font-bold mt-1">{{ \App\Models\Rental::count() }}</div>
                        </div>
                        <div class="bg-muted/40 p-4 rounded-md border col-span-2">
                            <h3 class="text-xs font-semibold uppercase text-muted-foreground">Total Revenue (Gross)</h3>
                            <div class="text-2xl font-bold mt-1">Rp {{ number_format(\App\Models\Rental::whereIn('status', ['paid', 'completed'])->sum('grand_total'), 0, ',', '.') }}</div>
                        </div>
    </div>

    <!-- Data Table Container (Responsive Table) -->
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="w-full overflow-x-auto lg:overflow-visible">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-muted/50 border-b sticky top-0 z-10">
                    <tr class="transition-colors">
                        <th class="h-11 px-4 text-left align-middle font-bold text-muted-foreground uppercase tracking-wider text-[10px] whitespace-nowrap">Pelanggan</th>
                        <th class="h-11 px-4 text-center align-middle font-bold text-muted-foreground uppercase tracking-wider text-[10px] whitespace-nowrap">Status Tier</th>
                        <th class="h-11 px-4 text-center align-middle font-bold text-muted-foreground uppercase tracking-wider text-[10px] whitespace-nowrap">Order</th>
                        <th class="h-11 px-4 text-right align-middle font-bold text-muted-foreground uppercase tracking-wider text-[10px] whitespace-nowrap">Lifetime Value</th>
                        <th class="h-11 px-4 text-right align-middle font-bold text-muted-foreground uppercase tracking-wider text-[10px] whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y border-b">
                    @forelse($customers as $c)
                        <tr class="transition-colors hover:bg-muted/30 group">
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-bold text-sm tracking-tight text-foreground">{{ $c->nama }}</span>
                                    <span class="text-[10px] font-medium text-muted-foreground tracking-tighter">{{ $c->no_wa }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center whitespace-nowrap">
                                @php $tier = $this->getTier($c->ltv); @endphp
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-tighter {{ $tier->color }}">
                                    {{ $tier->label }}
                                </span>
                            </td>
                            <td class="p-4 align-middle text-center whitespace-nowrap">
                                <span class="text-[10px] font-bold bg-secondary/50 text-secondary-foreground px-2 py-1 rounded-md border border-secondary">
                                    {{ $c->total_orders }}x
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right font-black text-sm whitespace-nowrap">
                                <span class="text-xs font-medium text-muted-foreground mr-0.5">Rp</span>{{ number_format($c->ltv, 0, ',', '.') }}
                            </td>
                            <td class="p-4 align-middle text-right whitespace-nowrap">
                                <button wire:click="selectCustomer('{{ $c->nik }}')" 
                                    class="h-8 px-3 rounded-md bg-background border border-input text-[10px] font-bold hover:bg-accent hover:text-accent-foreground transition-all shadow-sm">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center text-muted-foreground italic">Pelanggan tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-border">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 px-2">
                <!-- Left: Rows & Info -->
                <div class="flex items-center gap-6 order-2 md:order-1">
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none">Rows</label>
                        <select wire:model.live="perPage" class="h-8 rounded-lg border border-border bg-background px-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all shadow-sm uppercase">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest leading-none opacity-70">
                            Showing {{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }}
                        </p>
                    </div>
                </div>

                <!-- Right: Navigation -->
                <div class="flex items-center gap-3 order-1 md:order-2">
                    <button wire:click="previousPage" @disabled($customers->onFirstPage())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    
                    <div class="flex items-center gap-2 px-3 h-8 bg-muted/50 rounded-lg border border-border/50">
                        <span class="text-xs font-black text-foreground">{{ $customers->currentPage() }}</span>
                        <span class="text-[10px] font-bold text-muted-foreground uppercase opacity-50">/</span>
                        <span class="text-xs font-black text-foreground">{{ $customers->lastPage() }}</span>
                    </div>

                    <button wire:click="nextPage" @disabled(!$customers->hasMorePages())
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-foreground shadow-sm transition-all hover:bg-muted disabled:pointer-events-none disabled:opacity-40 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Detail Dialog (Modal with Shadcn style) -->
    <!-- Customer Detail Modal (Pure Shadcn Style) -->
    @if($selectedNik)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="w-full max-w-2xl rounded-lg border bg-card text-card-foreground shadow-lg animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                
                <div class="flex items-center justify-between p-6 border-b">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-lg font-semibold text-muted-foreground border">
                            {{ substr($customerDetails->first()->nama, 0, 1) }}{{ substr(strrchr($customerDetails->first()->nama, " "), 1, 1) ?: '' }}
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold leading-none">{{ $customerDetails->first()->nama }}</h3>
                            <div class="flex items-center gap-2 mt-1.5">
                                @php $tier = $this->getTier($customerDetails->sum('grand_total')); @endphp
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $tier->color }}">
                                    {{ $tier->label }}
                                </span>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase bg-muted/50 text-muted-foreground">
                                    NIK: {{ $customerDetails->first()->nik }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="h-8 w-8 rounded-md flex items-center justify-center text-muted-foreground hover:bg-muted transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6 scrollbar-hide">
                    {{-- Quick Insights --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="space-y-1">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Unit Favorit</p>
                            <p class="text-sm font-medium">{{ $customerInsights['fav_unit'] }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Total Rental</p>
                            <p class="text-sm font-medium">{{ $customerInsights['total_rentals'] }}x</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Avg Transaksi</p>
                            <p class="text-sm font-medium">Rp {{ number_format($customerInsights['avg_transaction'], 0, ',', '.') }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Loyal Sejak</p>
                            <p class="text-sm font-medium">{{ $customerInsights['member_since']->format('M Y') }}</p>
                        </div>
                    </div>

                    {{-- Data Lengkap Section --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Data Lengkap Pelanggan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-px bg-border border rounded-lg overflow-hidden">
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">Nama Lengkap</p>
                                <p class="text-sm font-semibold">{{ $customerInsights['nama'] }}</p>
                            </div>
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">Nomor NIK</p>
                                <p class="text-sm font-semibold">{{ $customerInsights['nik'] }}</p>
                            </div>
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">WhatsApp</p>
                                <a href="https://wa.me/{{ \App\Helpers\CustomerHelper::formatWa($customerDetails->first()->no_wa) }}" target="_blank" class="text-sm font-semibold text-primary hover:underline">
                                    {{ $customerDetails->first()->no_wa }}
                                </a>
                            </div>
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">Email</p>
                                <p class="text-sm font-semibold">{{ $customerInsights['email'] }}</p>
                            </div>
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">Sosial Media</p>
                                <p class="text-sm font-semibold">{{ $customerInsights['sosmed'] }}</p>
                            </div>
                            <div class="bg-card p-4 space-y-1">
                                <p class="text-[10px] font-medium text-muted-foreground uppercase">Alamat Lengkap</p>
                                <p class="text-sm font-semibold leading-snug">{{ $customerInsights['address'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Riwayat Rental</h4>
                        <div class="rounded-lg border divide-y">
                            @foreach($customerDetails as $rent)
                                <div class="p-4 flex items-center justify-between hover:bg-muted/30 transition-colors">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm">#{{ $rent->booking_code }}</span>
                                            <span class="text-[10px] text-muted-foreground">{{ $rent->created_at->format('d M Y') }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($rent->units as $u)
                                                <span class="text-[9px] font-medium bg-secondary/50 px-1.5 py-0.5 rounded border border-secondary">{{ $u->seri }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold">Rp {{ number_format($rent->grand_total, 0, ',', '.') }}</p>
                                        <span class="text-[9px] font-bold uppercase {{ $rent->status === 'paid' || $rent->status === 'completed' ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ $rent->status }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t bg-muted/20">
                    <button wire:click="closeDetail" class="w-full h-10 rounded-md bg-primary text-primary-foreground font-medium hover:bg-primary/90 transition-all active:scale-[0.98]">
                        Tutup Profil
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
