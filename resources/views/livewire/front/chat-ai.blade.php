<div class="fixed bottom-6 right-6 z-[100] font-sans" 
    x-data="{ 
        secondsLeft: 0,
        init() {
            if (window.Alpine && !Alpine.store('chat')) {
                Alpine.store('chat', {
                    isOpen: false,
                    open() { this.isOpen = true },
                    close() { this.isOpen = false },
                    toggle() { this.isOpen = !this.isOpen }
                });
            }
            setInterval(() => {
                if (this.secondsLeft > 0) this.secondsLeft--;
            }, 1000);
            // Sync with Livewire when spamUntil changes
            this.$watch('$wire.spamUntil', value => {
                const diff = Math.ceil((value - Date.now()) / 1000);
                this.secondsLeft = diff > 0 ? diff : 0;
            });
            // Initial check
            const initialDiff = Math.ceil(($wire.spamUntil - Date.now()) / 1000);
            this.secondsLeft = initialDiff > 0 ? initialDiff : 0;
        }
    }">
    {{-- Floating Toggle Button --}}
    <div class="relative">
        <button @click="$store.chat?.toggle()"
            class="h-12 w-12 rounded-full bg-primary text-primary-foreground shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-300 relative group pointer-events-auto">
            <div class="absolute inset-0 rounded-full bg-primary animate-ping opacity-20 group-hover:opacity-40"></div>
            <svg x-show="!$store.chat?.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round" class="relative z-10">
                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z" />
            </svg>
            <svg x-show="$store.chat?.isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                stroke-linejoin="round" class="relative z-10">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>

        {{-- Chat Window (Clean Apple Dark Surface) --}}
        <div x-show="$store.chat?.isOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="absolute bottom-16 right-0 w-[90vw] sm:w-[380px] h-[480px] sm:h-[560px] bg-card border border-border rounded-2xl shadow-2xl flex flex-col overflow-hidden pointer-events-auto">

            {{-- Header --}}
            <div class="px-4 py-3.5 bg-secondary/50 border-b border-border flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center border border-primary/20 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8V4H8" />
                            <rect width="16" height="12" x="4" y="8" rx="2" />
                            <path d="M2 14h2" />
                            <path d="M20 14h2" />
                            <path d="M15 13v2" />
                            <path d="M9 13v2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-xs text-foreground tracking-normal">CS AI RentSpace</h3>
                        <p class="text-[10px] text-muted-foreground font-medium flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                            Online 24/7
                        </p>
                    </div>
                </div>
                <button @click="$store.chat?.close()"
                    class="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>

            {{-- Messages Body --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3.5 scrollbar-hide overscroll-contain" id="chat-body" x-init="
                    $watch('$store.chat?.isOpen', value => { if(value) { $nextTick(() => { $el.scrollTop = $el.scrollHeight; }) } });
                " x-effect="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })"
                @scroll-bottom.window="$nextTick(() => { $el.scrollTop = $el.scrollHeight; })">
                @foreach($chatHistory as $chat)
                    <div
                        class="flex {{ $chat['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in fade-in duration-200">
                        <div
                            class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-xs leading-relaxed {{ $chat['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-tr-xs shadow-xs' : 'bg-secondary text-foreground rounded-tl-xs border border-border/60' }}">
                            @php
                                $content = e($chat['content']);
                                // Handle Bold
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                                // Handle Booking Button
                                if (str_contains($content, '[BOOKING]')) {
                                    $bookingBtn = '<a href="#explore" @click="$store.chat?.close()" class="mt-2.5 flex items-center justify-center w-full py-2 bg-primary text-primary-foreground rounded-xl font-bold text-xs tracking-normal transition-all active:scale-[0.98] shadow-xs">Pesan Sekarang</a>';
                                    $content = str_replace('[BOOKING]', $bookingBtn, $content);
                                }

                                // Handle WA Button
                                if (str_contains($content, '[CHAT_WA]')) {
                                    $waNumber = \App\Models\Setting::getVal('admin_wa') ?? '628123456789';
                                    $waBtn = '<a href="https://wa.me/' . $waNumber . '" target="_blank" class="mt-2.5 flex items-center justify-center w-full py-2 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-xl font-bold text-xs tracking-normal transition-all active:scale-[0.98]">Hubungi WhatsApp</a>';
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
                            class="bg-secondary border border-border/60 rounded-2xl rounded-tl-xs px-3.5 py-2.5 flex items-center gap-1.5">
                            <div class="w-1.5 h-1.5 bg-muted-foreground/60 rounded-full animate-bounce"></div>
                            <div class="w-1.5 h-1.5 bg-muted-foreground/60 rounded-full animate-bounce delay-75"></div>
                            <div class="w-1.5 h-1.5 bg-muted-foreground/60 rounded-full animate-bounce delay-150"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Input Footer --}}
            <div class="p-3.5 bg-secondary/30 border-t border-border">
                <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                    <input type="text" wire:model="message" 
                        x-bind:placeholder="secondsLeft > 0 ? 'Tunggu ' + secondsLeft + ' detik lagi...' : 'Ketik pertanyaan Anda...'"
                        x-bind:disabled="secondsLeft > 0"
                        class="flex-1 bg-background border border-border rounded-xl px-3.5 py-2 text-xs focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-muted-foreground/60 text-foreground"
                        x-bind:class="secondsLeft > 0 ? 'opacity-50 cursor-not-allowed' : ''">
                    <button type="submit"
                        x-bind:disabled="secondsLeft > 0"
                        class="h-9 w-9 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-xs hover:bg-primary/90 active:scale-95 transition-all shrink-0"
                        x-bind:class="secondsLeft > 0 ? 'opacity-50 cursor-not-allowed' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // Prevent multiple initializations during Livewire navigation
            if (window.chatAudioInitialized) return;

            window.chatAudioCtx = null;

            window.playChatSound = (type) => {
                try {
                    if (!window.chatAudioCtx) {
                        window.chatAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    }
                    
                    if (window.chatAudioCtx.state === 'suspended') {
                        window.chatAudioCtx.resume();
                    }

                    if (type === 'sent') {
                        // iMessage-like 'Swoosh/Pop' (Upward sweep)
                        const osc = window.chatAudioCtx.createOscillator();
                        const gain = window.chatAudioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(400, window.chatAudioCtx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(1200, window.chatAudioCtx.currentTime + 0.1);
                        
                        gain.gain.setValueAtTime(0.05, window.chatAudioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, window.chatAudioCtx.currentTime + 0.1);
                        
                        osc.connect(gain);
                        gain.connect(window.chatAudioCtx.destination);
                        osc.start();
                        osc.stop(window.chatAudioCtx.currentTime + 0.1);
                    } else if (type === 'received') {
                        // iMessage-like 'Note' (Two-tone chime)
                        [1046.50, 1567.98].forEach((freq, i) => {
                            const osc = window.chatAudioCtx.createOscillator();
                            const g = window.chatAudioCtx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, window.chatAudioCtx.currentTime + (i * 0.08));
                            g.gain.setValueAtTime(0.03, window.chatAudioCtx.currentTime + (i * 0.08));
                            g.gain.exponentialRampToValueAtTime(0.001, window.chatAudioCtx.currentTime + (i * 0.08) + 0.2);
                            
                            osc.connect(g);
                            g.connect(window.chatAudioCtx.destination);
                            osc.start(window.chatAudioCtx.currentTime + (i * 0.08));
                            osc.stop(window.chatAudioCtx.currentTime + (i * 0.08) + 0.2);
                        });
                    }
                } catch (e) {
                    console.warn('Audio feedback failed:', e);
                }
            };

            window.addEventListener('chat-sent', () => window.playChatSound('sent'));
            window.addEventListener('chat-received', () => window.playChatSound('received'));
            
            window.chatAudioInitialized = true;
        })();
    </script>
</div>