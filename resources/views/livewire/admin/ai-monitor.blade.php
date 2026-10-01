<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 pb-28 sm:pb-12">
    <!-- Header: Title, Engine Status & Toggle -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border/40 pb-5">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-sm shadow-primary/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8V4H8"/>
                    <rect width="16" height="12" x="4" y="8" rx="2"/>
                    <path d="M2 14h2"/>
                    <path d="M20 14h2"/>
                    <path d="M15 13v2"/>
                    <path d="M9 13v2"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-foreground tracking-tight">AI Mission Control</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $botGatewayStatus === 'running' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : ($botGatewayStatus === 'idle' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20') }}">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $botGatewayStatus === 'running' ? 'bg-emerald-500 animate-pulse' : ($botGatewayStatus === 'idle' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                        Gateway {{ ucfirst($botGatewayStatus) }}
                    </span>
                </div>
                <p class="text-xs text-muted-foreground mt-0.5">Memonitor sesi percakapan, penggunaan token, respon Gemini AI, dan riwayat memori WhatsApp.</p>
            </div>
        </div>

        <!-- Controls: Refresh, Sandbox, & Auto-reply Toggle -->
        <div class="flex items-center flex-wrap gap-2.5">
            <button wire:click="checkGatewayStatus" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-card/60 hover:bg-muted text-xs font-semibold text-muted-foreground transition active:scale-95">
                <svg wire:loading.class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                <span>Ping Gateway</span>
            </button>

            @if(auth()->user()->role === 'admin')
            <button wire:click="toggleCustomerReply"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-sm active:scale-95 {{ $autoReplyCustomer ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-muted text-muted-foreground border border-border' }}">
                <span class="w-2 h-2 rounded-full {{ $autoReplyCustomer ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                <span>Auto-Reply Customer: {{ $autoReplyCustomer ? 'ON' : 'PAUSED' }}</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session()->has('success'))
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
            <span>✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Key Metrics / Statistics Tiles (ala Ruang / Mission Control) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Active Model -->
        <div class="p-4 rounded-2xl bg-card border border-border/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-muted-foreground mb-2">
                <span class="text-[10px] uppercase font-bold tracking-wider">AI Engine Model</span>
                <span class="h-2 w-2 rounded-full {{ $geminiKeyConfigured ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
            </div>
            <div>
                <p class="text-base sm:text-lg font-black text-foreground truncate" title="{{ $activeModel }}">{{ $activeModel }}</p>
                <p class="text-[11px] text-muted-foreground mt-0.5">
                    {{ $geminiKeyConfigured ? 'API Key Terhubung' : 'API Key Belum Diisi' }}
                </p>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="p-4 rounded-2xl bg-card border border-border/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-muted-foreground mb-2">
                <span class="text-[10px] uppercase font-bold tracking-wider">Total Sesi AI</span>
                <span class="text-xs">💬</span>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-foreground">{{ number_format($totalSessions, 0, ',', '.') }}</p>
                <p class="text-[11px] text-muted-foreground mt-0.5">
                    {{ $activeSessionsToday }} sesi aktif hari ini
                </p>
            </div>
        </div>

        <!-- Total Messages -->
        <div class="p-4 rounded-2xl bg-card border border-border/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-muted-foreground mb-2">
                <span class="text-[10px] uppercase font-bold tracking-wider">Pesan Diproses</span>
                <span class="text-xs">⚡</span>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-primary">{{ number_format($totalMessages, 0, ',', '.') }}</p>
                <p class="text-[11px] text-muted-foreground mt-0.5">
                    {{ $customerSessions }} Customer · {{ $reportGroupSessions }} Grup Report
                </p>
            </div>
        </div>

        <!-- Estimated Token Usage -->
        <div class="p-4 rounded-2xl bg-card border border-border/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-muted-foreground mb-2">
                <span class="text-[10px] uppercase font-bold tracking-wider">Est. Token Input</span>
                <span class="text-xs">◔</span>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-foreground">
                    {{ $totalTokensEstimated > 1000 ? round($totalTokensEstimated / 1000, 1) . 'k' : $totalTokensEstimated }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-0.5">
                    Hemat token via Smart Compression
                </p>
            </div>
        </div>
    </div>

    <!-- Visual Pixel Office: AI Team Work Simulation (ala Ruang / Hermes) -->
    <div class="bg-card border border-border/80 rounded-2xl shadow-xs overflow-hidden">
        <!-- Room Toolbar -->
        <div class="px-4 py-3 bg-muted/30 border-b border-border/60 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-500 text-xs">🏢</span>
                <span class="text-xs font-bold text-foreground">RentSpace AI Virtual Office</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-primary/10 text-primary font-bold">Live Simulation</span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-muted-foreground font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>3 AI Agents Working</span>
                </span>
                <span class="hidden sm:inline text-border">|</span>
                <span class="hidden sm:inline">Active Channel: WA Customer & Tim Report</span>
            </div>
        </div>

        <!-- Custom Pixel Office Canvas Styles -->
        <style>
            .pixel-office-stage {
                background-color: #17211e;
                background-image: 
                    linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                    linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                    linear-gradient(135deg, #1f2c28 0%, #16211d 50%, #101916 100%);
                background-size: 16px 16px, 16px 16px, auto;
                position: relative;
                overflow: hidden;
                min-height: 240px;
                border-bottom: 6px solid #0d1412;
            }
            .pixel-floor-line {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 48px;
                background: linear-gradient(180deg, #1b2622 0%, #141d1a 100%);
                border-top: 3px solid #283933;
            }
            /* Pixel Character Anatomy */
            .px-char {
                display: inline-block;
                height: 52px;
                width: 44px;
                position: relative;
                z-index: 20;
            }
            .px-head, .px-hair, .px-torso, .px-arm, .px-leg, .px-laptop {
                position: absolute;
                image-rendering: pixelated;
            }
            .px-head {
                background: #e9b57d;
                box-shadow: inset 3px 0 #d79666;
                height: 18px;
                width: 20px;
                left: 12px;
                top: 7px;
                border-radius: 2px;
            }
            .px-eye-l, .px-eye-r {
                position: absolute;
                background: #17201e;
                width: 3px;
                height: 3px;
                top: 8px;
            }
            .px-eye-l { left: 4px; }
            .px-eye-r { right: 4px; }
            .px-torso {
                height: 16px;
                width: 22px;
                left: 11px;
                top: 25px;
                border-radius: 1px;
            }
            .px-arm {
                background: #e9b57d;
                height: 13px;
                width: 5px;
                top: 27px;
                border-radius: 1px;
            }
            .px-arm.left { left: 6px; }
            .px-arm.right { right: 6px; }
            .px-leg {
                background: #1e293b;
                bottom: 0;
                height: 12px;
                width: 7px;
            }
            .px-leg.left { left: 13px; }
            .px-leg.right { right: 13px; }

            /* Working Animation (Mengetik di laptop) */
            @keyframes px-typing-l {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-3px) rotate(-8deg); }
            }
            @keyframes px-typing-r {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-3px) rotate(8deg); }
            }
            @keyframes px-screen-glow {
                0%, 100% { filter: drop-shadow(0 0 2px #38bdf8) brightness(1); }
                50% { filter: drop-shadow(0 0 6px #38bdf8) brightness(1.4); }
            }
            @keyframes px-bob {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-2px); }
            }
            @keyframes px-blink-led {
                0%, 100% { opacity: 0.3; }
                50% { opacity: 1; }
            }

            .is-working .px-arm.left {
                animation: px-typing-l 0.32s infinite ease-in-out;
            }
            .is-working .px-arm.right {
                animation: px-typing-r 0.32s infinite ease-in-out 0.16s;
            }
            .is-working .px-head {
                animation: px-bob 1.4s infinite ease-in-out;
            }
            .is-working .screen-light {
                animation: px-screen-glow 1.2s infinite ease-in-out;
            }

            /* Pixel Furniture */
            .px-desk {
                width: 90px;
                height: 34px;
                background: #473224;
                border: 3px solid #1a120c;
                border-top: 4px solid #785338;
                position: relative;
                box-shadow: 0 4px 6px rgba(0,0,0,0.4);
            }
            .px-laptop-screen {
                position: absolute;
                top: -24px;
                left: 28px;
                width: 32px;
                height: 22px;
                background: #0f172a;
                border: 2px solid #334155;
                border-radius: 2px;
            }
            .px-laptop-screen .inner {
                margin: 2px;
                height: 14px;
                background: #0284c7;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: monospace;
                font-size: 8px;
                color: #e0f2fe;
            }
            .px-bubble {
                background: #ffffff;
                color: #0f172a;
                border: 2px solid #0f172a;
                border-radius: 6px;
                font-size: 10px;
                font-weight: 700;
                font-family: ui-monospace, monospace;
                padding: 3px 8px;
                position: relative;
                box-shadow: 0 2px 4px rgba(0,0,0,0.25);
                white-space: nowrap;
                animation: px-bob 2s infinite ease-in-out;
            }
            .px-bubble::after {
                content: '';
                position: absolute;
                bottom: -5px;
                left: 50%;
                transform: translateX(-50%);
                border-width: 5px 5px 0;
                border-style: solid;
                border-color: #ffffff transparent;
                display: block;
                width: 0;
            }
        </style>

        <!-- Room Scene -->
        <div class="pixel-office-stage p-4 sm:p-6 flex flex-col justify-end">
            <!-- Background Props: Server Rack, Window & Whiteboard -->
            <div class="absolute top-4 left-6 hidden sm:flex items-center gap-4 opacity-75 pointer-events-none">
                <!-- Pixel Window -->
                <div class="w-16 h-14 bg-gradient-to-b from-sky-400/20 via-sky-600/20 to-indigo-900/30 border-2 border-emerald-950 rounded-sm p-1 flex items-center justify-center">
                    <div class="w-full h-full border border-sky-400/20 grid grid-cols-2 grid-rows-2 gap-0.5">
                        <div class="bg-sky-300/10"></div>
                        <div class="bg-sky-300/10"></div>
                        <div class="bg-sky-300/10"></div>
                        <div class="bg-sky-300/10"></div>
                    </div>
                </div>
                <!-- Status Board -->
                <div class="bg-emerald-950/60 border border-emerald-500/20 rounded px-2.5 py-1.5 text-[9px] font-mono text-emerald-400/90 leading-tight">
                    <p class="font-bold text-emerald-300">⚡ RENTSPACE AGENT CLUSTER</p>
                    <p>STATUS: <span class="text-emerald-400 font-bold">ONLINE</span> · GEMINI-FLASH</p>
                </div>
            </div>

            <!-- Server Rack (Right Background) -->
            <div class="absolute top-4 right-6 hidden md:block pointer-events-none opacity-80">
                <div class="w-12 h-20 bg-zinc-900 border-2 border-zinc-950 rounded p-1 space-y-1">
                    <div class="h-2 bg-zinc-800 rounded-xs flex items-center justify-between px-1">
                        <span class="w-1 h-1 rounded-full bg-emerald-400" style="animation: px-blink-led 0.6s infinite;"></span>
                        <span class="w-1 h-1 rounded-full bg-sky-400" style="animation: px-blink-led 0.9s infinite 0.2s;"></span>
                    </div>
                    <div class="h-2 bg-zinc-800 rounded-xs flex items-center justify-between px-1">
                        <span class="w-1 h-1 rounded-full bg-emerald-400" style="animation: px-blink-led 0.8s infinite 0.4s;"></span>
                        <span class="w-1 h-1 rounded-full bg-amber-400" style="animation: px-blink-led 1.2s infinite;"></span>
                    </div>
                    <div class="h-2 bg-zinc-800 rounded-xs flex items-center justify-between px-1">
                        <span class="w-1 h-1 rounded-full bg-emerald-400" style="animation: px-blink-led 0.5s infinite 0.1s;"></span>
                        <span class="w-1 h-1 rounded-full bg-emerald-400" style="animation: px-blink-led 0.7s infinite 0.3s;"></span>
                    </div>
                </div>
            </div>

            <!-- Floor bar -->
            <div class="pixel-floor-line"></div>

            <!-- Workstations Row -->
            <div class="relative z-10 flex items-end justify-around sm:justify-center sm:gap-14 pt-10 pb-1">
                <!-- Workstation 1: CS Rental Bot (Mengetik Balas Customer) -->
                <div class="flex flex-col items-center is-working">
                    <div class="px-bubble mb-2">
                        <span>💬 Menjawab Sewa...</span>
                    </div>
                    <!-- Pixel Agent: CS Bot -->
                    <div class="px-char">
                        <div class="px-hair" style="background: #1e1b4b; height: 5px; width: 22px; left: 11px; top: 3px; border-radius: 2px;"></div>
                        <div class="px-head">
                            <span class="px-eye-l"></span>
                            <span class="px-eye-r"></span>
                        </div>
                        <div class="px-torso" style="background: #0284c7;"></div>
                        <div class="px-arm left"></div>
                        <div class="px-arm right"></div>
                        <div class="px-leg left"></div>
                        <div class="px-leg right"></div>
                    </div>
                    <!-- Desk & Laptop -->
                    <div class="px-desk -mt-2.5">
                        <div class="px-laptop-screen screen-light">
                            <div class="inner">WA</div>
                        </div>
                    </div>
                    <span class="mt-2 text-[10px] font-mono font-bold text-sky-400 bg-zinc-950/70 px-2 py-0.5 rounded border border-sky-500/30">
                        CS Customer Bot
                    </span>
                </div>

                <!-- Workstation 2: Lead AI Core / Dispatcher (Monitoring & Memory) -->
                <div class="flex flex-col items-center is-working">
                    <div class="px-bubble mb-2" style="animation-delay: 0.7s;">
                        <span>🧠 Parsing Intent...</span>
                    </div>
                    <!-- Pixel Agent: Hermes Core Agent -->
                    <div class="px-char">
                        <div class="px-hair" style="background: #78350f; height: 6px; width: 22px; left: 11px; top: 2px; border-radius: 2px;"></div>
                        <div class="px-head">
                            <span class="px-eye-l"></span>
                            <span class="px-eye-r"></span>
                        </div>
                        <div class="px-torso" style="background: #059669;"></div>
                        <div class="px-arm left"></div>
                        <div class="px-arm right"></div>
                        <div class="px-leg left"></div>
                        <div class="px-leg right"></div>
                    </div>
                    <!-- Desk & Dual Laptop -->
                    <div class="px-desk -mt-2.5">
                        <div class="px-laptop-screen screen-light" style="background: #064e3b; border-color: #059669;">
                            <div class="inner" style="background: #10b981; color: #022c22;">AI</div>
                        </div>
                    </div>
                    <span class="mt-2 text-[10px] font-mono font-bold text-emerald-400 bg-zinc-950/70 px-2 py-0.5 rounded border border-emerald-500/30">
                        Gemini 3.6 Engine
                    </span>
                </div>

                <!-- Workstation 3: Financial & Tim Reporter (Membuat Rekap Omset) -->
                <div class="flex flex-col items-center is-working">
                    <div class="px-bubble mb-2" style="animation-delay: 1.4s;">
                        <span>📊 Rekap Omset...</span>
                    </div>
                    <!-- Pixel Agent: Finance/Report Bot -->
                    <div class="px-char">
                        <div class="px-hair" style="background: #312e81; height: 5px; width: 22px; left: 11px; top: 3px; border-radius: 2px;"></div>
                        <div class="px-head">
                            <span class="px-eye-l"></span>
                            <span class="px-eye-r"></span>
                        </div>
                        <div class="px-torso" style="background: #d97706;"></div>
                        <div class="px-arm left"></div>
                        <div class="px-arm right"></div>
                        <div class="px-leg left"></div>
                        <div class="px-leg right"></div>
                    </div>
                    <!-- Desk & Laptop -->
                    <div class="px-desk -mt-2.5">
                        <div class="px-laptop-screen screen-light" style="background: #451a03; border-color: #d97706;">
                            <div class="inner" style="background: #f59e0b; color: #451a03;">RPT</div>
                        </div>
                    </div>
                    <span class="mt-2 text-[10px] font-mono font-bold text-amber-400 bg-zinc-950/70 px-2 py-0.5 rounded border border-amber-500/30">
                        Group Report Bot
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace: Split Screen (Left: Sessions List, Right: Chat Transcript / Test Sandbox) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT: Conversations Session Directory (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 p-1 bg-muted/50 rounded-xl border border-border/50 text-xs">
                    <button wire:click="$set('filterChannel', 'all')"
                        class="px-2.5 py-1 rounded-lg font-bold transition {{ $filterChannel === 'all' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' }}">
                        Semua
                    </button>
                    <button wire:click="$set('filterChannel', 'wa_customer')"
                        class="px-2.5 py-1 rounded-lg font-bold transition {{ $filterChannel === 'wa_customer' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' }}">
                        Customer
                    </button>
                    <button wire:click="$set('filterChannel', 'wa_group_report')"
                        class="px-2.5 py-1 rounded-lg font-bold transition {{ $filterChannel === 'wa_group_report' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground' }}">
                        Grup Tim
                    </button>
                </div>

                <div class="relative flex-1 max-w-[180px]">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari sesi..."
                        class="w-full h-8 pl-7 pr-2.5 text-xs rounded-lg border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary text-foreground">
                    <svg class="absolute left-2 top-2.5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>

            <!-- Sessions List Card -->
            <div class="bg-card border border-border rounded-2xl overflow-hidden divide-y divide-border/40 shadow-xs">
                @forelse($conversations as $conv)
                    @php
                        $lastMsg = $conv->messages->first();
                        $isSelected = $selectedConversationId === $conv->id;
                    @endphp
                    <div wire:click="selectConversation({{ $conv->id }})"
                        class="p-3.5 transition cursor-pointer {{ $isSelected ? 'bg-primary/10 border-l-4 border-l-primary' : 'hover:bg-muted/40' }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="text-xs font-bold text-foreground truncate">
                                    {{ $conv->peer_name ?: 'Pengguna Anonim' }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ $conv->channel === 'wa_customer' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-purple-500/10 text-purple-600 dark:text-purple-400' }}">
                                    {{ $conv->channel === 'wa_customer' ? 'WA Cust' : 'Grup Tim' }}
                                </span>
                            </div>
                            <span class="text-[10px] text-muted-foreground shrink-0">
                                {{ $conv->last_active_at ? $conv->last_active_at->diffForHumans() : '-' }}
                            </span>
                        </div>

                        <p class="text-xs text-muted-foreground line-clamp-1">
                            @if($lastMsg)
                                <span class="font-semibold text-foreground/80">{{ $lastMsg->role === 'user' ? 'Cust: ' : 'AI: ' }}</span>
                                {{ Str::limit($lastMsg->content, 60) }}
                            @else
                                <span class="italic opacity-60">Belum ada transkrip pesan</span>
                            @endif
                        </p>

                        <div class="flex items-center gap-3 mt-2 text-[10px] text-muted-foreground/70">
                            <span>{{ $conv->turn_count }} interaksi</span>
                            <span>•</span>
                            <span>{{ number_format($conv->input_tokens) }} tokens</span>
                            @if(!empty($conv->memory))
                                <span>•</span>
                                <span class="text-primary font-medium">Memori aktif</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-muted-foreground text-xs">
                        Tidak ada riwayat sesi yang cocok dengan pencarian.
                    </div>
                @endforelse
            </div>

            <div class="pt-1">
                {{ $conversations->links() }}
            </div>
        </div>

        <!-- RIGHT: Detail Transcript / Live Test Sandbox (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            @if($activeConversation)
                <!-- Active Conversation Inspection Box -->
                <div class="bg-card border border-border rounded-2xl shadow-xs overflow-hidden flex flex-col h-[640px]">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-border/60 bg-muted/20 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-foreground flex items-center gap-2">
                                <span>{{ $activeConversation->peer_name ?: 'Pengguna Anonim' }}</span>
                                <span class="text-[10px] font-normal text-muted-foreground">({{ $activeConversation->channel }})</span>
                            </h3>
                            <p class="text-[10px] text-muted-foreground">Key: <code class="bg-muted px-1 py-0.5 rounded">{{ substr($activeConversation->peer_key, 0, 14) }}...</code></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="clearConversationHistory({{ $activeConversation->id }})" wire:confirm="Yakin ingin membersihkan memori dan riwayat percakapan sesi ini?"
                                class="px-2.5 py-1 text-[11px] font-semibold text-rose-500 hover:bg-rose-500/10 rounded-lg transition">
                                Reset Sesi
                            </button>
                            <button wire:click="closeConversation" class="p-1 text-muted-foreground hover:text-foreground rounded-lg">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Memory Snippet Banner (if present) -->
                    @if(!empty($activeConversation->memory))
                        <div class="bg-primary/[0.03] border-b border-primary/10 px-4 py-2 text-[11px] text-muted-foreground flex items-center justify-between">
                            <span class="font-medium text-foreground">🧠 Fakta Memori AI:</span>
                            <span class="text-[10px] opacity-80 truncate max-w-sm">{{ json_encode($activeConversation->memory) }}</span>
                        </div>
                    @endif

                    <!-- Messages Log -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3.5">
                        @forelse($messages as $msg)
                            <div class="flex flex-col {{ $msg->role === 'user' ? 'items-start' : 'items-end' }}">
                                <div class="flex items-center gap-1.5 mb-1 px-1">
                                    <span class="text-[10px] font-bold {{ $msg->role === 'user' ? 'text-muted-foreground' : 'text-primary' }}">
                                        {{ $msg->role === 'user' ? ($msg->actor ?: 'Pelanggan') : 'Gemini AI' }}
                                    </span>
                                    <span class="text-[9px] text-muted-foreground/60">
                                        {{ $msg->created_at ? $msg->created_at->format('H:i') : '' }}
                                    </span>
                                </div>
                                <div class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-xs {{ $msg->role === 'user' ? 'bg-muted text-foreground rounded-tl-sm' : 'bg-primary text-primary-foreground rounded-tr-sm shadow-xs' }}">
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->content }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="h-full flex items-center justify-center text-xs text-muted-foreground">
                                Belum ada riwayat pesan detail di database untuk sesi ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            @else
                <!-- Sandbox Test Simulator (Saat tidak ada sesi yang dipilih) -->
                <div class="bg-card border border-border rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-border/60 bg-muted/20">
                        <h3 class="text-sm font-bold text-foreground flex items-center gap-2">
                            <span>⚡ Interactive AI Sandbox</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">Direct Test</span>
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Uji coba bagaimana AI menjawab pertanyaan pelanggan atau grup tim secara langsung tanpa membuka WhatsApp.</p>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-muted-foreground block mb-1">Channel Simulasi</label>
                                <select wire:model.live="testChannel" class="w-full h-9 rounded-xl border border-border bg-background px-3 text-xs font-semibold text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option value="wa_customer">Customer WhatsApp (Pertanyaan Rental)</option>
                                    <option value="wa_group_report">Grup Report Internal Tim (Cek Omset, Ringkasan)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-muted-foreground block mb-1">Nama Pengirim</label>
                                <input type="text" wire:model="testSenderName" class="w-full h-9 rounded-xl border border-border bg-background px-3 text-xs font-semibold text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-muted-foreground block mb-1">Prompt / Pertanyaan Uji Coba</label>
                            <textarea wire:model="testInput" rows="3" placeholder="Contoh: Halo kak, mau sewa iPhone 13 besok 1 hari ready ga ya?"
                                class="w-full p-3 rounded-xl border border-border bg-background text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                            @error('testInput') <span class="text-[10px] text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="button" wire:click="runTestPrompt" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 h-9 rounded-xl bg-primary text-primary-foreground font-bold text-xs shadow hover:bg-primary/90 active:scale-95 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="runTestPrompt">Kirim ke Gemini AI →</span>
                                <span wire:loading wire:target="runTestPrompt">Sedang Berpikir...</span>
                            </button>
                        </div>

                        @if($testOutput)
                            <div class="mt-4 pt-4 border-t border-border/60">
                                <label class="text-[11px] font-bold text-muted-foreground block mb-1.5">Respon AI Gemini:</label>
                                <div class="p-3.5 rounded-xl bg-muted/60 border border-border text-xs text-foreground whitespace-pre-wrap leading-relaxed">
                                    {{ $testOutput }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info Card: Architectural Guide ala Ruang -->
                <div class="p-4 rounded-2xl bg-muted/20 border border-border text-xs text-muted-foreground space-y-2">
                    <p class="font-bold text-foreground flex items-center gap-1.5">
                        <span>ℹ️</span>
                        <span>Mekanisme Memori & Auto-Handoff</span>
                    </p>
                    <p>AI Rent Space dilengkapi pemantauan intent otomatis. Bila pertanyaan pelanggan mengarah pada kendala sensitif / di luar katalog, AI secara otomatis menyerahkan obrolan ke admin toko WhatsApp (Handoff) untuk memastikan kualitas layanan terbaik.</p>
                </div>
            @endif
        </div>
    </div>
</div>
