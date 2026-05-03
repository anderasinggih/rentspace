<div>
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Promo & Pricing Rules</h1>
                <p class="mt-2 text-sm text-muted-foreground">Manage dynamic discounts, like "35% off for 12 hours" or
                    "Rent 1 day, free 1 day".</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                @if(auth()->user()->role === 'admin')
                <button wire:click="create"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                    Create Rule
                </button>
                @endif
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-border rounded-lg bg-background">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-foreground sm:pl-6">
                                        Nama Promo</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Kriteria</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Kode Promo</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Status</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground">
                                        Kuota</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span
                                            class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($rules as $rule)
                                @php
                                $isExpired = $rule->end_date && \Carbon\Carbon::now()->isAfter($rule->end_date);
                                $isDeleted = $rule->trashed();
                                $dimmed = $isExpired || $isDeleted || !$rule->is_active;
                                @endphp
                                <tr class="{{ $dimmed ? 'opacity-50 grayscale bg-muted/20' : '' }} transition-all">
                                    <td
                                        class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-foreground sm:pl-6">
                                        {{ $rule->nama_promo }}
                                        @if($isDeleted)
                                        <x-ui.badge variant="red" class="ml-1 text-[10px] uppercase font-bold">Soft
                                            Deleted</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-sm text-muted-foreground">
                                        <div class="font-bold text-primary">
                                            @if($rule->tipe === 'diskon_persen') Diskon {{ $rule->value }}%
                                            @elseif($rule->tipe === 'hari_gratis') Gratis {{ $rule->value }} Hari
                                            @elseif($rule->tipe === 'jam_gratis') Gratis {{ $rule->value }} Jam
                                            @elseif($rule->tipe === 'fix_price') Harga Spesial Rp {{
                                            number_format($rule->value, 0, ',', '.') }}
                                            @elseif($rule->tipe === 'diskon_nominal') Potongan Rp {{
                                            number_format($rule->value, 0, ',', '.') }}
                                            @elseif($rule->tipe === 'cashback') Cashback Rp {{
                                            number_format($rule->value, 0, ',', '.') }}
                                            @else {{ $rule->value }} @endif
                                        </div>
                                        <div class="text-[10px]">
                                            @if($rule->tipe === 'diskon_persen') Diskon Persentase
                                            @elseif($rule->tipe === 'hari_gratis') Hari Tambahan
                                            @elseif($rule->tipe === 'fix_price') Harga Pas (Fix)
                                            @elseif($rule->tipe === 'diskon_nominal') Diskon Nominal (Rp)
                                            @elseif($rule->tipe === 'jam_gratis') Jam Tambahan
                                            @elseif($rule->tipe === 'cashback') Cashback Tunai
                                            @endif
                                            &bull; {{ $rule->syarat_minimal_durasi ? '> '.$rule->syarat_minimal_durasi.'
                                            '.$rule->syarat_tipe_durasi : 'Tanpa Syarat' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-xs text-muted-foreground">
                                        @if($rule->kode_promo)
                                        <code class="px-1.5 py-0.5 rounded bg-muted text-primary font-bold">{{ $rule->kode_promo }}</code>
                                        @else
                                        <span class="italic opacity-50">—</span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        <div class="flex flex-col items-start gap-1">
                                            @if($isDeleted)
                                            <x-ui.badge variant="red" class="text-[10px] uppercase">Dihapus</x-ui.badge>
                                            @elseif($isExpired)
                                            <x-ui.badge variant="red" class="text-[10px] uppercase">Expired</x-ui.badge>
                                            @elseif($rule->is_active)
                                            <x-ui.badge variant="green" class="text-[10px] uppercase">Aktif</x-ui.badge>
                                            @else
                                            <x-ui.badge variant="zinc" class="text-[10px] uppercase">Nonaktif</x-ui.badge>
                                            @endif

                                            <div class="flex gap-1">
                                                @if($rule->is_hidden)
                                                <x-ui.badge variant="zinc" class="text-[9px] uppercase px-1">Hidden</x-ui.badge>
                                                @endif
                                                @if($rule->can_stack)
                                                <x-ui.badge variant="sky" class="text-[9px] uppercase px-1">Stackable</x-ui.badge>
                                                @endif
                                                @if($rule->is_affiliate_only)
                                                <x-ui.badge variant="zinc" class="text-[9px] uppercase px-1 border-primary/30 text-primary">Affiliate Only</x-ui.badge>
                                                @endif
                                                @if($rule->requires_referral)
                                                <x-ui.badge variant="zinc" class="text-[9px] uppercase px-1 border-sky-300 text-sky-600">Ref Required</x-ui.badge>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-muted-foreground">
                                        <div class="flex flex-col items-start gap-1">
                                            @if($rule->usage_limit)
                                                @if($rule->rentals_count >= $rule->usage_limit)
                                                    <x-ui.badge variant="red" class="text-[10px] font-bold">FULL</x-ui.badge>
                                                @endif
                                                <p class="text-xs font-bold text-foreground">{{ $rule->rentals_count }} / {{ $rule->usage_limit }}</p>
                                                <p class="text-[9px] text-muted-foreground tracking-tighter uppercase">Terpakai</p>
                                            @else
                                                <p class="text-xs text-muted-foreground italic">Unlimited</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-xs font-medium sm:pr-6 space-x-2">
                                        @if(auth()->user()->role === 'admin')
                                        @if($isDeleted)
                                        <button wire:click="restore({{ $rule->id }})"
                                            class="text-green-600 hover:underline">Restore</button>
                                        <button wire:click="delete({{ $rule->id }})"
                                            wire:confirm="Hapus PERMANEN aturan ini?"
                                            class="text-red-600 font-bold hover:underline">Destroy</button>
                                        @else
                                        <button wire:click="edit({{ $rule->id }})"
                                            class="text-primary hover:underline">Edit</button>
                                        <button wire:click="duplicate({{ $rule->id }})"
                                            class="text-sky-600 hover:underline">Duplikat</button>
                                        <button wire:click="delete({{ $rule->id }})"
                                            wire:confirm="Hapus (Soft Delete) aturan ini?"
                                            class="text-red-500 hover:text-red-700">Del</button>
                                        @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-sm text-muted-foreground">Belum ada
                                        promo / rules yang dibuat.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>

        <!-- Modal Form -->
        @if($showModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-background/80 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)"></div>

            <!-- Modal Content Wrapper -->
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-background p-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-8 border border-border">
                    <h2 class="text-lg font-semibold">{{ $isEditing ? 'Edit Rule' : 'Tambah Rule / Promo Baru' }}</h2>
                    <form wire:submit="save" class="mt-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-1">
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Nama Promo</label>
                                <input type="text" wire:model="nama_promo"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Diskon Lebaran">
                                @error('nama_promo') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-1">
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Kode Promo (Voucher)</label>
                                <input type="text" wire:model="kode_promo"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="COBACOBA">
                                @error('kode_promo') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Tipe Potongan</label>
                                <select wire:model="tipe"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-2 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    <option value="diskon_persen">Diskon Persentase (%)</option>
                                    <option value="diskon_nominal">Diskon Nominal (Rp)</option>
                                    <option value="hari_gratis">Gratis Hari Tambahan</option>
                                    <option value="jam_gratis">Gratis Jam Tambahan</option>
                                    <option value="fix_price">Harga Pas (Fix Price)</option>
                                    <option value="cashback">Cashback Tunai (Rp)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Value</label>
                                <input type="number" wire:model="value"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Misal: 35">
                                @error('value') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Tgl Mulai (Opsional)</label>
                                <input type="date" wire:model="start_date"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-2 py-1 text-[11px] sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('start_date') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Tgl Berakhir (Opsional)</label>
                                <input type="date" wire:model="end_date"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-2 py-1 text-[11px] sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('end_date') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-border pt-4 mt-2">
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none whitespace-nowrap">Min. Durasi (Opsional)</label>
                                <input type="number" wire:model="syarat_minimal_durasi"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="0">
                                <p class="text-[9px] text-muted-foreground mt-1">Acuhkan jika tanpa syarat.</p>
                            </div>
                            <div>
                                <label class="text-[11px] sm:text-sm font-medium leading-none">Satuan</label>
                                <select wire:model="syarat_tipe_durasi"
                                    class="mt-1 flex h-8 sm:h-9 w-full rounded-md border border-input bg-transparent px-2 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    <option value="jam">Jam</option>
                                    <option value="hari">Hari</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 p-3 rounded-xl bg-muted/30 border border-border">
                            <label class="text-[11px] sm:text-sm font-bold text-foreground">Kuota Penggunaan Promo</label>
                            <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-2">
                                <div class="w-full sm:w-24">
                                    <input type="number" wire:model="usage_limit"
                                        class="flex h-8 sm:h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="0">
                                </div>
                                <div class="text-[9px] sm:text-[10px] text-muted-foreground bg-background/50 px-2.5 py-1.5 rounded border border-border/50 flex-1">
                                    Kosongkan untuk unlimited.
                                </div>
                            </div>
                            @error('usage_limit') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 py-3 border-y border-border mt-2">
                            <div class="flex items-center space-x-2" wire:key="aff-only-toggle">
                                <input type="checkbox" id="is_affiliate_only_rule" wire:model="is_affiliate_only"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                                <label for="is_affiliate_only_rule" class="text-[11px] font-medium text-primary cursor-pointer leading-none">Khusus Affiliator</label>
                            </div>
                            <div class="flex items-center space-x-2" wire:key="req-ref-toggle">
                                <input type="checkbox" id="requires_referral_rule" wire:model="requires_referral"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                                <label for="requires_referral_rule" class="text-[11px] font-medium text-sky-600 cursor-pointer leading-none">Wajib Kode Referral</label>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <div class="flex items-center space-x-2" wire:key="active-toggle">
                                <input type="checkbox" id="is_active_rule" wire:model.live="is_active"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                                <label for="is_active_rule" class="text-xs font-medium leading-none cursor-pointer">Rule Aktif</label>
                            </div>
                            <div class="flex items-center space-x-2" wire:key="hidden-toggle">
                                <input type="checkbox" id="is_hidden_rule" wire:model.live="is_hidden"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                                <label for="is_hidden_rule" class="text-xs font-medium leading-none cursor-pointer">Sembunyikan</label>
                            </div>
                            <div class="flex items-center space-x-2" wire:key="stack-toggle">
                                <input type="checkbox" id="can_stack_rule" wire:model.live="can_stack"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                                <label for="can_stack_rule" class="text-xs font-medium leading-none cursor-pointer">Stackable</label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                class="inline-flex items-center justify-center rounded-md border border-input bg-background h-9 px-4 text-sm font-medium shadow-sm hover:bg-muted hover:text-foreground">
                                Batal
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-primary h-9 px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>