<div>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Campaign & Announcements</h1>
            <p class="mt-2 text-sm text-muted-foreground">Manage promo banners, flash messages, and site-wide alerts.
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <button wire:click="create"
                class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Create New Campaign
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div
            class="mt-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 px-4 py-2 rounded-md text-sm font-medium">
            {{ session('message') }}
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($announcements as $ann)
            <div
                class="relative overflow-hidden rounded-xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                <!-- Style Indicator -->
                <div class="absolute top-0 right-0 h-1 w-24 
                    {{ $ann->style === 'promo' ? 'bg-purple-500' : '' }}
                    {{ $ann->style === 'info' ? 'bg-blue-500' : '' }}
                    {{ $ann->style === 'warning' ? 'bg-amber-500' : '' }}
                    {{ $ann->style === 'success' ? 'bg-emerald-500' : '' }}">
                </div>

                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                            {{ $ann->type === 'banner' ? 'bg-primary/10 text-primary' : 'bg-secondary text-secondary-foreground' }}">
                            {{ $ann->type }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleStatus({{ $ann->id }})"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $ann->is_active ? 'bg-primary' : 'bg-muted' }}">
                            <span
                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $ann->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-medium text-foreground line-clamp-3 min-h-[3rem]">{{ $ann->message }}</p>
                    @if($ann->link_text)
                        <div class="mt-2 flex items-center text-xs text-primary font-semibold">
                            {{ $ann->link_text }} <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="ml-1">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-border pt-4">
                    <div class="text-[10px] text-muted-foreground uppercase font-black">
                        @if($ann->starts_at)
                            {{ $ann->starts_at->format('d M') }} - {{ $ann->ends_at ? $ann->ends_at->format('d M Y') : 'Life' }}
                        @else
                            Selalu Aktif
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="edit({{ $ann->id }})"
                            class="p-1 px-2 text-xs font-bold text-muted-foreground hover:text-foreground hover:bg-muted rounded transition-colors">Edit</button>
                        <button wire:click="delete({{ $ann->id }})"
                            class="p-1 px-2 text-xs font-bold text-destructive hover:bg-destructive/10 rounded transition-colors"
                            onclick="confirm('Hapus campaign ini?') || event.stopImmediatePropagation()">Delete</button>
                    </div>
                </div>
            </div>
        @endforeach

        @if(count($announcements) === 0)
            <div class="col-span-full py-16 text-center border-2 border-dashed rounded-xl border-muted bg-muted/20">
                <p class="text-muted-foreground italic">Belum ada campaign yang dibuat.</p>
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div
                class="relative w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-2xl sm:p-8 animate-in zoom-in-95 duration-200">
                <h2 class="text-lg font-bold tracking-tight mb-6">
                    {{ $isEditing ? 'Edit Campaign' : 'Ciptakan Campaign Baru' }}</h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Tipe Tampilan</label>
                        <div class="flex gap-2 bg-muted p-1 rounded-lg">
                            <button type="button" @click="$wire.set('type', 'banner')"
                                class="flex-1 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $type === 'banner' ? 'bg-background shadow-sm' : 'text-muted-foreground' }}">BANNER</button>
                            <button type="button" @click="$wire.set('type', 'flash')"
                                class="flex-1 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $type === 'flash' ? 'bg-background shadow-sm' : 'text-muted-foreground' }}">FLASH</button>
                            <button type="button" @click="$wire.set('type', 'popup')"
                                class="flex-1 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $type === 'popup' ? 'bg-background shadow-sm' : 'text-muted-foreground' }}">POPUP</button>
                            <button type="button" @click="$wire.set('type', 'container')"
                                class="flex-1 px-3 py-1.5 text-xs font-bold rounded-md transition-all {{ $type === 'container' ? 'bg-background shadow-sm' : 'text-muted-foreground' }}">CONTAINER</button>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Isi Pesan</label>
                        <textarea wire:model="message" rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            placeholder="Misal: Diskon Sewa iPhone 15 Pro Max Akhir Tahun!"></textarea>
                        @error('message') <span class="text-[10px] text-destructive mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Teks
                                Tombol/Link</label>
                            <input type="text" wire:model="link_text"
                                class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                                placeholder="Lihat Promo">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">URL
                                Tujuan</label>
                            <input type="text" wire:model="link_url"
                                class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                                placeholder="/sewa">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Tema Warna</label>
                        <div class="flex gap-4 items-center">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="style" value="promo" class="sr-only peer">
                                <div
                                    class="h-6 w-6 rounded-full bg-purple-500 border-2 border-transparent peer-checked:border-foreground ring-2 ring-transparent peer-checked:ring-purple-500/30">
                                </div>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="style" value="info" class="sr-only peer">
                                <div
                                    class="h-6 w-6 rounded-full bg-blue-500 border-2 border-transparent peer-checked:border-foreground ring-2 ring-transparent peer-checked:ring-blue-500/30">
                                </div>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="style" value="warning" class="sr-only peer">
                                <div
                                    class="h-6 w-6 rounded-full bg-amber-500 border-2 border-transparent peer-checked:border-foreground ring-2 ring-transparent peer-checked:ring-amber-500/30">
                                </div>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="style" value="success" class="sr-only peer">
                                <div
                                    class="h-6 w-6 rounded-full bg-emerald-500 border-2 border-transparent peer-checked:border-foreground ring-2 ring-transparent peer-checked:ring-emerald-500/30">
                                </div>
                            </label>
                            <span class="text-xs font-medium uppercase text-muted-foreground">{{ $style }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Mulai
                                Tanggal</label>
                            <input type="datetime-local" wire:model="starts_at"
                                class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase text-muted-foreground block mb-1.5">Selesai
                                Tanggal</label>
                            <input type="datetime-local" wire:model="ends_at"
                                class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 bg-muted/40 p-3 rounded-lg border border-border mt-2">
                        <input type="checkbox" id="is_active_form" wire:model="is_active"
                            class="h-4 w-4 rounded border-border text-primary focus:ring-primary cursor-pointer">
                        <label for="is_active_form"
                            class="text-sm font-bold text-foreground cursor-pointer select-none">Aktifkan Campaign
                            Sekarang</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-6">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 text-sm font-medium border border-input rounded-md hover:bg-accent transition-colors">Batal</button>
                        <button type="submit"
                            class="px-6 py-2 text-sm font-bold bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-all shadow-md">Simpan
                            Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>