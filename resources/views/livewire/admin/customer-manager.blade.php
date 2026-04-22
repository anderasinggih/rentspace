<div class="space-y-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-foreground">Customer Insights</h1>
            <p class="mt-2 text-sm text-muted-foreground">Monitor customer loyalty, frequency, and lifetime value across all rentals.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <div class="relative w-full md:w-80">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pelanggan..." 
                    class="flex h-9 w-full rounded-md border border-input bg-background px-9 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-all">
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

    <!-- Data Table Container -->
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
                <thead class="bg-muted/50 border-b">
                    <tr class="transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Customer</th>
                        <th class="h-12 px-4 text-center align-middle font-medium text-muted-foreground">Tier Status</th>
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Orders</th>
                        <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground text-right pr-6">LTV (Lifetime Value)</th>
                        <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground invisible">Actions</th>
                    </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0 divide-y">
                    @forelse($customers as $c)
                        <tr class="transition-colors hover:bg-muted/30">
                            <td class="p-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-semibold leading-none">{{ $c->nama }}</span>
                                    <span class="text-xs text-muted-foreground mt-1">{{ $c->no_wa }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                @php $tier = $this->getTier($c->ltv); @endphp
                                <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 {{ $tier->color }}">
                                    {{ $tier->label }}
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <span class="text-xs font-medium bg-secondary text-secondary-foreground px-2 py-0.5 rounded-md">{{ $c->total_orders }}x Sewa</span>
                            </td>
                            <td class="p-4 align-middle text-right font-medium pr-6">
                                Rp {{ number_format($c->ltv, 0, ',', '.') }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <button wire:click="selectCustomer('{{ $c->nik }}')" class="inline-flex items-center justify-center rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-4">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center text-muted-foreground italic">No customers found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-6 py-4 border-t flex items-center justify-between bg-muted/10">
                <div class="text-xs text-muted-foreground">
                    Menampilkan <span class="font-bold text-foreground">{{ $customers->firstItem() }}</span> ke <span class="font-bold text-foreground">{{ $customers->lastItem() }}</span> dari <span class="font-bold text-foreground">{{ $customers->total() }}</span> pelanggan
                </div>
                <div class="flex items-center gap-2">
                    {{-- Previous Page --}}
                    @if ($customers->onFirstPage())
                        <button class="h-8 w-8 flex items-center justify-center rounded-md border border-input bg-background opacity-50 cursor-not-allowed text-muted-foreground shadow-sm" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                    @else
                        <button wire:click="previousPage" wire:loading.attr="disabled"
                            class="h-8 w-8 flex items-center justify-center rounded-md border border-input bg-background text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                    @endif

                    <div class="flex items-center gap-1.5 px-3">
                        <span class="text-xs font-black text-foreground">{{ $customers->currentPage() }}</span>
                        <span class="text-xs font-medium text-muted-foreground/50">/</span>
                        <span class="text-xs font-bold text-muted-foreground">{{ $customers->lastPage() }}</span>
                    </div>

                    {{-- Next Page --}}
                    @if ($customers->hasMorePages())
                        <button wire:click="nextPage" wire:loading.attr="disabled"
                            class="h-8 w-8 flex items-center justify-center rounded-md border border-input bg-background text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @else
                        <button class="h-8 w-8 flex items-center justify-center rounded-md border border-input bg-background opacity-50 cursor-not-allowed text-muted-foreground shadow-sm" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Customer Detail Dialog (Modal with Shadcn style) -->
    @if($selectedNik)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="w-full max-w-2xl rounded-lg border bg-card text-card-foreground shadow-2xl animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                <div class="flex flex-col p-6 space-y-1.5 border-b relative">
                    <h3 class="text-xl font-semibold leading-none tracking-tight">{{ $customerDetails->first()->nama }}</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">Detailed rental history and customer insights.</p>
                    <button wire:click="closeDetail" class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto lg:hide-scrollbar space-y-6">
                    <!-- Quick Metrics -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-muted/40 p-4 rounded-md border">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Orders</p>
                            <p class="text-lg font-bold leading-none mt-1">{{ $customerDetails->count() }}</p>
                        </div>
                        <div class="bg-muted/40 p-4 rounded-md border">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Revenue</p>
                            <p class="text-lg font-bold leading-none mt-1">Rp {{ number_format($customerDetails->sum('grand_total'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-muted/40 p-4 rounded-md border">
                            @php $tier = $this->getTier($customerDetails->sum('grand_total')); @endphp
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase">Tier</p>
                            <div class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold mt-1 {{ $tier->color }}">
                                {{ $tier->label }}
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Rental History</h4>
                        <div class="grid grid-cols-1 gap-3">
                            @foreach($customerDetails as $rent)
                                <div class="flex items-center justify-between p-3 rounded-md border bg-card/50 transition-all hover:bg-muted/30">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold truncate">#{{ $rent->booking_code }}</span>
                                            <span class="text-xs text-muted-foreground font-medium">{{ $rent->created_at->format('d M Y') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @foreach($rent->units as $u)
                                                <span class="text-[10px] bg-secondary text-secondary-foreground px-1.5 py-0.5 rounded-sm">{{ $u->seri }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold">Rp {{ number_format($rent->grand_total, 0, ',', '.') }}</p>
                                        <p class="text-[10px] font-semibold uppercase tracking-tighter text-muted-foreground mt-0.5">{{ $rent->status }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center p-6 border-t pt-4">
                    <button wire:click="closeDetail" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full">
                        Close Details
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
