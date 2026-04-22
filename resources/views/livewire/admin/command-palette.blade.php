<div x-data="{ 
    isOpen: @entangle('isOpen'),
    selectedIndex: @entangle('selectedIndex'),
    resultsCount: 0
}" 
x-on:keydown.window.ctrl.k.prevent="isOpen = !isOpen; if(isOpen) setTimeout(() => $refs.searchInput.focus(), 100)"
x-on:keydown.window.meta.k.prevent="isOpen = !isOpen; if(isOpen) setTimeout(() => $refs.searchInput.focus(), 100)"
x-on:keydown.window.escape="isOpen = false"
x-on:keydown.window.arrow-down.prevent="if(isOpen) selectedIndex = (selectedIndex + 1) % resultsCount"
x-on:keydown.window.arrow-up.prevent="if(isOpen) selectedIndex = (selectedIndex - 1 + resultsCount) % resultsCount"
x-on:keydown.window.enter.prevent="if(isOpen) { const activeLink = document.querySelector('.command-result-item-' + selectedIndex); if (activeLink) activeLink.click(); isOpen = false; }"
x-on:livewire:navigated.window="isOpen = false"
class="relative z-[200]">

    <!-- Modal Backdrop -->
    <div x-show="isOpen" 
        x-transition:enter="duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-background/80 backdrop-blur-sm" 
        @click="isOpen = false">
    </div>

    <!-- Modal Content -->
    <div x-show="isOpen" 
        x-transition:enter="duration-200 ease-out"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="duration-150 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-1/2 top-1/4 -translate-x-1/2 w-full max-w-xl max-h-[60vh] flex flex-col bg-popover text-popover-foreground border shadow-2xl rounded-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Search Header -->
        <div class="flex items-center px-4 border-b">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" 
                x-ref="searchInput"
                wire:model.live.debounce.150ms="search"
                class="flex h-12 w-full bg-transparent py-4 px-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50" 
                placeholder="Type a command or search (NIK, Name, Unit, Menu)..."
                @keydown.escape="isOpen = false">
            <kbd class="hidden md:inline-flex items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100">
                <span class="text-xs">ESC</span>
            </kbd>
        </div>

        <!-- Results Area -->
        <div class="flex-1 overflow-y-auto px-2 py-3 lg:hide-scrollbar" x-init="resultsCount = {{ count($results) }}">
            @if(strlen($search) < 2)
                <div class="p-6 text-center text-sm text-muted-foreground">
                    <p>Mulai ketik (minimal 2 karakter) untuk mencari...</p>
                    <div class="grid grid-cols-2 gap-2 mt-4 max-w-sm mx-auto">
                        <div class="p-2 border rounded-md text-[10px] uppercase font-bold text-muted-foreground/50">Cari Unit: "13 Pro"</div>
                        <div class="p-2 border rounded-md text-[10px] uppercase font-bold text-muted-foreground/50">Cari Pelanggan: "Agus"</div>
                        <div class="p-2 border rounded-md text-[10px] uppercase font-bold text-muted-foreground/50">Navigasi: "Settings"</div>
                        <div class="p-2 border rounded-md text-[10px] uppercase font-bold text-muted-foreground/50">Cari Booking: "RSV"</div>
                    </div>
                </div>
            @elseif(count($results) > 0)
                <div class="space-y-1">
                    @php 
                        $currentType = '';
                        $globalIndex = 0;
                    @endphp
                    @foreach($results as $item)
                        @if($item->type !== $currentType)
                            <div class="px-2 py-1.5 text-[10px] font-semibold text-muted-foreground uppercase tracking-widest mt-2 first:mt-0">
                                {{ $item->type }}
                            </div>
                            @php $currentType = $item->type; @endphp
                        @endif
                        
                        <button 
                            wire:click="selectResult('{{ $item->url }}', '{{ $item->action ?? '' }}')"
                            @click="isOpen = false"
                            class="command-result-item-{{ $globalIndex }} w-full text-left px-3 py-2 rounded-md flex items-center justify-between transition-colors
                            {{ $selectedIndex === $globalIndex ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/50' }}"
                            @mouseenter="selectedIndex = {{ $globalIndex }}">
                            
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded-md bg-muted text-muted-foreground">
                                    <x-icon :name="$item->icon" class="w-4 h-4" />
                                </div>
                                <span class="text-sm font-medium">{{ $item->title }}</span>
                            </div>
                            
                            @if($selectedIndex === $globalIndex)
                                <div class="px-1.5 py-0.5 rounded border bg-background text-[10px] font-mono text-muted-foreground">ENTER</div>
                            @endif
                        </button>
                        @php $globalIndex++; @endphp
                    @endforeach
                </div>
            @else
                <div class="p-10 text-center">
                    <p class="text-sm text-muted-foreground">Tidak ada hasil ditemukan untuk "<span class="font-bold text-foreground">{{ $search }}</span>"</p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="p-3 border-t bg-muted/50 flex items-center justify-between text-[10px] font-medium text-muted-foreground">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><kbd class="px-1 bg-background border rounded">↑↓</kbd> Navigasi</span>
                <span class="flex items-center gap-1"><kbd class="px-1 bg-background border rounded">ENTER</kbd> Pilih</span>
                <span class="flex items-center gap-1"><kbd class="px-1 bg-background border rounded">ESC</kbd> Tutup</span>
            </div>
            <div class="hidden sm:block">Command Palette Admin</div>
        </div>
    </div>
</div>
