<div>
    <div class="bg-background pt-10 pb-16 min-h-[calc(100vh-200px)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl text-balance">Tentang & FAQ</h1>
                <p class="mt-4 text-sm sm:text-base text-muted-foreground">Pertanyaan yang sering diajukan seputar layanan penyewaan {{ \App\Models\Setting::getVal('home_title', 'kami') }}.</p>
            </div>

            @if(count($faq_items) > 0)
                <div class="w-full flex-col flex" x-data="{ active: null }">
                    @foreach($faq_items as $index => $item)
                        <div class="border-b border-border">
                            <button 
                                @click="active = active === {{ $index }} ? null : {{ $index }}" 
                                class="flex w-full items-center justify-between py-4 font-medium transition-all hover:underline text-left text-sm sm:text-base text-foreground">
                                <span>{{ $item['question'] ?? '' }}</span>
                                <svg 
                                    class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" 
                                    :class="active === {{ $index }} ? 'rotate-180' : ''" 
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div 
                                x-cloak
                                x-show="active === {{ $index }}"
                                x-transition:enter="transition-all duration-300 ease-out"
                                x-transition:enter-start="opacity-0 max-h-0"
                                x-transition:enter-end="opacity-100 max-h-screen"
                                x-transition:leave="transition-all duration-200 ease-in"
                                x-transition:leave-start="opacity-100 max-h-screen"
                                x-transition:leave-end="opacity-0 max-h-0"
                                class="overflow-hidden text-sm"
                            >
                                <div class="pb-4 pt-0 text-muted-foreground whitespace-pre-line leading-relaxed">
                                    {{ $item['answer'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 border rounded-xl bg-muted/20 text-muted-foreground flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 mb-4 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="font-medium text-foreground">Belum ada informasi faq</p>
                    <p class="text-sm mt-1">Informasi tentang kami dan tanya jawab akan segera diperbarui.</p>
                </div>
            @endif
        </div>
    </div>
</div>
