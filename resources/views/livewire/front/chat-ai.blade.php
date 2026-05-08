<div class="fixed bottom-6 right-6 z-[100] font-sans" x-data>
    {{-- Floating Toggle Button --}}
    <div class="relative">
        <button @click="$store.chat.toggle()" 
            class="h-12 w-12 rounded-full bg-primary text-primary-foreground shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-300 relative group pointer-events-auto">
            <div class="absolute inset-0 rounded-full bg-primary animate-ping opacity-20 group-hover:opacity-40"></div>
            <svg x-show="!$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="relative z-10"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
            <svg x-show="$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="relative z-10"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>

        {{-- Chat Window --}}
        <div x-show="$store.chat.isOpen" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-10 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-10 scale-95"
            class="absolute bottom-16 right-0 w-[90vw] sm:w-[360px] h-[650px] bg-white/10 dark:bg-zinc-950/30 backdrop-blur-[20px] backdrop-saturate-[180%] border border-white/30 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.4)] flex flex-col overflow-hidden pointer-events-auto">
            
            {{-- Header --}}
            <div class="px-4 py-3 bg-white/5 border-b border-white/20 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-primary/20 flex items-center justify-center border border-primary/40 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-[10px] tracking-widest uppercase leading-tight text-foreground">CS AI RENT SPACE</h3>
                        <p class="text-[9px] text-primary font-black uppercase tracking-widest opacity-80">Online 24/7</p>
                    </div>
                </div>
                <button @click="$store.chat.close()" class="bg-white/10 hover:bg-white/20 p-1.5 rounded-lg transition-all active:scale-90 border border-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>

            {{-- Messages Body --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-hide" id="chat-body" 
                x-init="
                    $watch('$store.chat.isOpen', value => { if(value) { $nextTick(() => { $el.scrollTop = $el.scrollHeight; }) } });
                "
                x-effect="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })"
                @scroll-bottom.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })">
                @foreach($chatHistory as $chat)
                    <div class="flex {{ $chat['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in fade-in slide-in-from-bottom-1 duration-300">
                        <div class="max-w-[88%] rounded-xl px-3 py-2 text-[12px] leading-relaxed {{ $chat['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-tr-none shadow-md shadow-primary/20' : 'bg-white/15 dark:bg-white/5 backdrop-blur-md border border-white/20 shadow-sm rounded-tl-none text-foreground' }}">
                            @php
                                $content = e($chat['content']);
                                // Handle Bold
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                                // Handle WA Button
                                if (str_contains($content, '[CHAT_WA]')) {
                                    $waNumber = \App\Models\Setting::getVal('admin_wa') ?? '628123456789';
                                    $waBtn = '<a href="https://wa.me/'.$waNumber.'" target="_blank" class="mt-3 flex items-center justify-center gap-2.5 w-full py-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-500 border border-emerald-500/30 rounded-xl font-bold text-[10px] tracking-wider transition-all active:scale-95 shadow-lg shadow-emerald-500/5 group">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="text-emerald-500"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        HUBUNGI ADMIN VIA WA
                                    </a>';
                                    $content = str_replace('[CHAT_WA]', $waBtn, $content);
                                }
                            @endphp
                            {!! nl2br($content) !!}
                        </div>
                    </div>
                @endforeach

                @if($isTyping)
                    <div class="flex justify-start">
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl rounded-tl-none px-3 py-2 flex items-center gap-1">
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
                    <button type="submit" class="h-10 w-10 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
