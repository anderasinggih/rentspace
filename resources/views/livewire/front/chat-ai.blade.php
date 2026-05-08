<div class="fixed bottom-6 right-6 z-[100] font-sans" x-data>
    {{-- Floating Toggle Button --}}
    <button @click="$store.chat.toggle()" 
        class="h-14 w-14 rounded-full bg-primary text-primary-foreground shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-300 relative group">
        <div class="absolute inset-0 rounded-full bg-primary animate-ping opacity-20 group-hover:opacity-40"></div>
        <svg x-show="!$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative z-10"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
        <svg x-show="$store.chat.isOpen" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative z-10"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>

    {{-- Chat Window --}}
    <div x-show="$store.chat.isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="absolute bottom-20 right-0 w-[90vw] sm:w-[400px] h-[550px] bg-white/10 dark:bg-zinc-950/20 backdrop-blur-[12px] backdrop-saturate-[180%] border-t border-l border-white/40 border-r border-b border-zinc-950/20 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] flex flex-col overflow-hidden">
        
        {{-- Header --}}
        <div class="p-5 bg-white/5 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-primary/20 flex items-center justify-center border border-primary/30 shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-xs tracking-wider uppercase leading-tight text-foreground">CS AI RENT SPACE</h3>
                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest opacity-80">Online 24/7</p>
                </div>
            </div>
            <button @click="$store.chat.close()" class="bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-all active:scale-90 border border-white/20">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
        </div>

        {{-- Messages Body --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-4 scrollbar-hide" id="chat-body" x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
            @foreach($chatHistory as $chat)
                <div class="flex {{ $chat['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-[13px] leading-relaxed {{ $chat['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-tr-none shadow-lg shadow-primary/20' : 'bg-white/10 dark:bg-white/5 backdrop-blur-md border border-white/20 shadow-sm rounded-tl-none text-foreground' }}">
                        {!! nl2br(preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($chat['content']))) !!}
                    </div>
                </div>
            @endforeach

            @if($isTyping)
                <div class="flex justify-start animate-pulse">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl rounded-tl-none px-4 py-3 flex items-center gap-1.5">
                        <div class="w-1 h-1 bg-primary rounded-full animate-bounce"></div>
                        <div class="w-1 h-1 bg-primary rounded-full animate-bounce delay-75"></div>
                        <div class="w-1 h-1 bg-primary rounded-full animate-bounce delay-150"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="px-5 py-3 border-t border-white/10 bg-white/5 flex gap-2 overflow-x-auto scrollbar-hide">
            <button wire:click="quickAction('iPhone 14 Pro ready kapan?')" class="whitespace-nowrap px-4 py-2 bg-white/10 hover:bg-primary hover:text-white border border-white/20 rounded-xl text-[10px] font-bold tracking-tight transition-all active:scale-95 uppercase">Cek iPhone 14</button>
            <button wire:click="quickAction('Sony A7IV ready besok?')" class="whitespace-nowrap px-4 py-2 bg-white/10 hover:bg-primary hover:text-white border border-white/20 rounded-xl text-[10px] font-bold tracking-tight transition-all active:scale-95 uppercase">Cek Sony A7IV</button>
            <button wire:click="quickAction('Status pesanan saya?')" class="whitespace-nowrap px-4 py-2 bg-white/10 hover:bg-primary hover:text-white border border-white/20 rounded-xl text-[10px] font-bold tracking-tight transition-all active:scale-95 uppercase">Lacak Pesanan</button>
        </div>

        {{-- Input Footer --}}
        <div class="p-5 bg-white/5 border-t border-white/10">
            <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                <input type="text" wire:model="message" placeholder="Tanya apa saja, Kak..." 
                    class="flex-1 bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-[13px] focus:ring-2 focus:ring-primary/50 outline-none transition-all placeholder:text-muted-foreground/50 text-foreground">
                <button type="submit" class="h-12 w-12 rounded-2xl bg-primary text-primary-foreground flex items-center justify-center shadow-lg shadow-primary/30 hover:scale-105 active:scale-95 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
