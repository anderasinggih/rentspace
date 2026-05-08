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
        class="absolute bottom-20 right-0 w-[90vw] sm:w-[400px] h-[500px] bg-background border border-border rounded-3xl shadow-2xl flex flex-col overflow-hidden">
        
        {{-- Header --}}
        <div class="p-4 bg-primary text-primary-foreground flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-background/20 flex items-center justify-center border border-background/30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight">CS AI RENT SPACE</h3>
                    <p class="text-[10px] opacity-80 font-medium">Online 24/7 • Asisten Pintar</p>
                </div>
            </div>
            <button @click="$store.chat.close()" class="hover:bg-background/20 p-1.5 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
        </div>

        {{-- Messages Body --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-muted/5 scrollbar-hide" id="chat-body" x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
            @foreach($chatHistory as $chat)
                <div class="flex {{ $chat['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm {{ $chat['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-tr-none' : 'bg-background border border-border shadow-sm rounded-tl-none' }}">
                        {!! nl2br(preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($chat['content']))) !!}
                    </div>
                </div>
            @endforeach

            @if($isTyping)
                <div class="flex justify-start animate-pulse">
                    <div class="bg-background border border-border shadow-sm rounded-2xl rounded-tl-none px-4 py-2 flex items-center gap-1">
                        <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce delay-75"></div>
                        <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce delay-150"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="px-4 py-2 border-t border-border bg-background flex gap-2 overflow-x-auto scrollbar-hide">
            <button wire:click="quickAction('iPhone 14 Pro ready kapan?')" class="whitespace-nowrap px-3 py-1.5 bg-muted hover:bg-primary/10 hover:text-primary rounded-full text-[10px] font-bold transition-colors">Cek iPhone 14</button>
            <button wire:click="quickAction('Sony A7IV ready besok?')" class="whitespace-nowrap px-3 py-1.5 bg-muted hover:bg-primary/10 hover:text-primary rounded-full text-[10px] font-bold transition-colors">Cek Sony A7IV</button>
            <button wire:click="quickAction('Status pesanan NIK saya?')" class="whitespace-nowrap px-3 py-1.5 bg-muted hover:bg-primary/10 hover:text-primary rounded-full text-[10px] font-bold transition-colors">Lacak Pesanan</button>
        </div>

        {{-- Input Footer --}}
        <div class="p-4 bg-background border-t border-border">
            <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                <input type="text" wire:model="message" placeholder="Tanya apa saja, Bos..." 
                    class="flex-1 bg-muted/50 border border-border rounded-xl px-4 py-2 text-sm focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-muted-foreground/60">
                <button type="submit" class="h-10 w-10 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
