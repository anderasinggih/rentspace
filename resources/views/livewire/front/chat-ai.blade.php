<div class="fixed bottom-6 right-6 z-[100] font-sans" x-data>
    {{-- Floating Toggle Button --}}
    <div class="relative">
        <button @click="$store.chat.toggle()"
            class="h-12 w-12 rounded-full bg-primary text-primary-foreground shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-300 relative group pointer-events-auto">
            <div class="absolute inset-0 rounded-full bg-primary animate-ping opacity-20 group-hover:opacity-40"></div>
            <svg x-show="!$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round" class="relative z-10">
                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z" />
            </svg>
            <svg x-show="$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round" class="relative z-10">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>

        {{-- Chat Window --}}
        <div x-show="$store.chat.isOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-10 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-10 scale-95"
            class="absolute bottom-16 right-0 w-[90vw] sm:w-[360px] h-[450px] sm:h-[600px] bg-white/10 dark:bg-zinc-950/30 backdrop-blur-[20px] backdrop-saturate-[180%] border border-white/30 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.4)] flex flex-col overflow-hidden pointer-events-auto">

            {{-- Header --}}
            <div class="px-4 py-3 bg-white/5 border-b border-white/20 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="h-8 w-8 rounded-full bg-primary/20 flex items-center justify-center border border-primary/40 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            class="text-primary">
                            <path d="M12 8V4H8" />
                            <rect width="16" height="12" x="4" y="8" rx="2" />
                            <path d="M2 14h2" />
                            <path d="M20 14h2" />
                            <path d="M15 13v2" />
                            <path d="M9 13v2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-[10px] tracking-widest uppercase leading-tight text-foreground">CS AI
                            RENT SPACE</h3>
                        <p class="text-[9px] text-primary font-black uppercase tracking-widest opacity-80">Online 24/7
                        </p>
                    </div>
                </div>
                <button @click="$store.chat.close()"
                    class="bg-white/10 hover:bg-white/20 p-1.5 rounded-lg transition-all active:scale-90 border border-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>

            {{-- Messages Body --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-hide overscroll-contain" id="chat-body" x-init="
                    $watch('$store.chat.isOpen', value => { if(value) { $nextTick(() => { $el.scrollTop = $el.scrollHeight; }) } });
                " x-effect="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })"
                @scroll-bottom.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })">
                @foreach($chatHistory as $chat)
                    <div
                        class="flex {{ $chat['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in fade-in slide-in-from-bottom-1 duration-300">
                        <div
                            class="max-w-[88%] rounded-xl px-3 py-2 text-[12px] leading-relaxed {{ $chat['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-tr-none shadow-md shadow-primary/20' : 'bg-white/15 dark:bg-white/5 backdrop-blur-md border border-white/20 shadow-sm rounded-tl-none text-foreground' }}">
                            @php
                                $content = e($chat['content']);
                                // Handle Bold
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                                // Handle Booking Button
                                if (str_contains($content, '[BOOKING]')) {
                                    $bookingBtn = '<a href="#explore" @click="$store.chat.close()" class="mt-2 flex items-center justify-center w-full py-1.5 bg-primary text-primary-foreground rounded-lg font-black text-[9px] tracking-widest transition-all active:scale-95 shadow-lg shadow-primary/20 uppercase">BOOKING SEKARANG</a>';
                                    $content = str_replace('[BOOKING]', $bookingBtn, $content);
                                }

                                // Handle WA Button
                                if (str_contains($content, '[CHAT_WA]')) {
                                    $waNumber = \App\Models\Setting::getVal('admin_wa') ?? '628123456789';
                                    $waBtn = '<a href="https://wa.me/' . $waNumber . '" target="_blank" class="mt-2 flex items-center justify-center w-full py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-500 border border-emerald-500/20 rounded-lg font-black text-[9px] tracking-widest transition-all active:scale-95 shadow-sm shadow-emerald-500/5 uppercase">HUBUNGI ADMIN VIA WA</a>';
                                    $content = str_replace('[CHAT_WA]', $waBtn, $content);
                                }
                            @endphp
                            {!! nl2br($content) !!}
                        </div>
                    </div>
                @endforeach

                @if($isTyping)
                    <div class="flex justify-start">
                        <div
                            class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl rounded-tl-none px-3 py-2 flex items-center gap-1">
                            <div class="w-0.5 h-0.5 bg-primary rounded-full animate-bounce"></div>
                            <div class="w-0.5 h-0.5 bg-primary rounded-full animate-bounce delay-75"></div>
                            <div class="w-0.5 h-0.5 bg-primary rounded-full animate-bounce delay-150"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Input Footer --}}
            <div class="p-4 bg-white/5 border-t border-white/10">
                <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                    <input type="text" wire:model="message" placeholder="Tanya apa saja, Kak..."
                        class="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-2.5 text-[12px] focus:ring-1 focus:ring-primary/40 outline-none transition-all placeholder:text-muted-foreground/40 text-foreground">
                    <button type="submit"
                        class="h-10 w-10 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>