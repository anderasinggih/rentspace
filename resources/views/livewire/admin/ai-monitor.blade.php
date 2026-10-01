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

    <!-- 3D Isometric Virtual Office: Three.js WebGL Simulation (ala Ruang / Hermes) -->
    <div class="bg-card border border-border/80 rounded-2xl shadow-xs overflow-hidden">
        <!-- Room Minimalist Toolbar -->
        <div class="px-5 py-3 bg-muted/20 border-b border-border/60 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-sm">🏢</span>
                <span class="text-xs font-bold text-foreground">RentSpace 3D Studio & Office Lounge</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-semibold {{ $csStatus === 'working' ? 'bg-pink-500/15 text-pink-500 border border-pink-500/30' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20' }}">
                    Dewi: {{ $csStatus === 'working' ? '👩‍💻 Online di Meja' : '🛋️ Istirahat di Sofa' }}
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-semibold bg-teal-500/10 text-teal-400 border border-teal-500/20">
                    Singgih: 👨‍💻 Dispatcher Ready
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Andera: 📊 Finance Ready
                </span>
            </div>

            <!-- Quick Action Controls -->
            <div class="flex items-center gap-2">
                <button wire:click="bonkAgent('cs_bot', 'work')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-pink-500/20 text-pink-600 dark:text-pink-300 border border-pink-500/40 hover:bg-pink-500/30 active:scale-95 transition">
                    <span>👩‍💻</span>
                    <span>Tugaskan Dewi</span>
                </button>
                <button wire:click="bonkAgent('cs_bot', 'break')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-muted hover:bg-muted/80 text-muted-foreground border border-border active:scale-95 transition">
                    <span>🛋️</span>
                    <span>Dewi Santai</span>
                </button>
                <button wire:click="bonkAgent('cs_bot', 'sleep')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/40 hover:bg-purple-500/30 active:scale-95 transition"
                    title="Simulasi Token Habis / Low Energy - Dewi Tidur di Kamar">
                    <span>🪫</span>
                    <span>Tidur Zzz</span>
                </button>
            </div>
        </div>

        <!-- Camera Preset & Zoom Bar (Blender-like Viewport Controls) -->
        <div class="px-4 py-2 bg-zinc-950/90 border-b border-border/40 flex items-center justify-between flex-wrap gap-2 text-xs">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[11px] font-semibold text-zinc-400 mr-1 flex items-center gap-1">
                    🎥 View:
                </span>
                <button type="button" onclick="window._threeOfficeApp?.setView('iso')"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    Isometric
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('top')"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    Atas (Top)
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('desk')"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    Meja Kantor
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('dewi_pov')" title="POV Menatap Layar Monitor Dewi"
                    class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-pink-500/20 text-pink-300 border border-pink-500/40 hover:bg-pink-500/30 active:scale-95 transition">
                    💻 POV Layar Dewi
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('bedroom')" title="Lihat Kamar Tidur AI"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    🛏️ Kamar Tidur
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('pantry')"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    Dapur & Lounge
                </button>
                <button type="button" onclick="window._threeOfficeApp?.setView('front')"
                    class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition">
                    Depan (Front)
                </button>
            </div>

            <!-- Zoom & Pan Quick Tools -->
            <div class="flex items-center gap-1 text-[11px]">
                <button type="button" onclick="window._threeOfficeApp?.zoomIn()" title="Zoom In"
                    class="w-7 h-7 flex items-center justify-center rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition font-bold text-sm">
                    +
                </button>
                <button type="button" onclick="window._threeOfficeApp?.zoomOut()" title="Zoom Out"
                    class="w-7 h-7 flex items-center justify-center rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition font-bold text-sm">
                    −
                </button>
                <button type="button" onclick="window._threeOfficeApp?.toggleExpand()" title="Perbesar / Perkecil Jendela"
                    class="px-2.5 py-1 rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-white/10 active:scale-95 transition text-[11px] font-medium ml-1">
                    ⛶ Toggle Luas
                </button>
            </div>
        </div>

        @if($bonkMessage)
            <div class="px-5 py-2 bg-primary/10 border-b border-primary/20 text-xs text-primary font-medium flex items-center justify-between">
                <span>{{ $bonkMessage }}</span>
                <button wire:click="dismissBonk" class="text-muted-foreground hover:text-foreground text-xs ml-2">✕</button>
            </div>
        @endif

        <!-- 3D Canvas Viewport (Height 480px, Cozy & Cheerful Modern Studio) -->
        <script>
            window._threeOfficeApp = {
                scene: null,
                camera: null,
                renderer: null,
                dewiGroup: null,     // CS Customer: Cewek baju pink, rambut panjang cantik
                singgihGroup: null,  // Core Dispatcher: Pria baju tosca/teal
                anderaGroup: null,   // Finance & Report: Pria baju amber/warm
                screenCanvas: null,
                screenCtx: null,
                screenTexture: null,
                screenMeshes: [],
                clock: null,
                isDragging: false,
                prevMouse: { x: 0, y: 0 },
                rotY: 0.58,
                rotX: 0.38,
                currentCsStatus: 'break',
                animationFrameId: null,

                // State kontrol view dan kamera
                cameraRadius: 18.5,
                targetLookAt: { x: 0, y: 1.0, z: 0 },

                setView(viewName) {
                    this.isPovMode = (viewName === 'dewi_pov');
                    if (viewName === 'iso') {
                        this.rotY = 0.58;
                        this.rotX = 0.38;
                        this.cameraRadius = 18.5;
                        this.targetLookAt = { x: 0, y: 1.0, z: 0 };
                    } else if (viewName === 'top') {
                        this.rotY = 0.0;
                        this.rotX = 1.45;
                        this.cameraRadius = 21.0;
                        this.targetLookAt = { x: 0, y: 0.5, z: 0 };
                    } else if (viewName === 'desk') {
                        this.rotY = 0.30;
                        this.rotX = 0.28;
                        this.cameraRadius = 12.0;
                        this.targetLookAt = { x: -2.0, y: 1.1, z: 0.2 };
                    } else if (viewName === 'pantry') {
                        this.rotY = -0.55;
                        this.rotX = 0.30;
                        this.cameraRadius = 12.5;
                        this.targetLookAt = { x: 4.8, y: 1.1, z: 0.8 };
                    } else if (viewName === 'bedroom') {
                        // View Kamar Tidur AI & Charging Bed
                        this.rotY = 0.45;
                        this.rotX = 0.32;
                        this.cameraRadius = 9.5;
                        this.targetLookAt = { x: -9.8, y: 1.2, z: 1.3 };
                    } else if (viewName === 'bathroom') {
                        // View Kamar Mandi AI (Sebelah Kamar Tidur di pojok)
                        this.rotY = 0.50;
                        this.rotX = 0.35;
                        this.cameraRadius = 8.0;
                        this.targetLookAt = { x: -9.6, y: 1.1, z: -3.0 };
                    } else if (viewName === 'free') {
                        // FREE CAM: Bebas panning & orbit ke seluruh sudut
                        this.cameraRadius = 16.0;
                        this.targetLookAt = { x: 0, y: 1.0, z: 0 };
                    } else if (viewName === 'dewi_pov') {
                        // TRUE FIRST-PERSON POV: Kamera terpasang di depan muka Dewi menghadap ke depan meja!
                        this.isPovMode = true;
                        this.targetLookAt = { x: -3.2, y: 1.40, z: -0.5 }; // Menatap langsung ke arah monitor & keyboard di depan muka
                    } else if (viewName === 'front') {
                        this.rotY = 0.0;
                        this.rotX = 0.12;
                        this.cameraRadius = 17.5;
                        this.targetLookAt = { x: 0, y: 1.2, z: 0 };
                    }
                    this.updateCameraPos();
                },

                zoomIn() {
                    this.cameraRadius = Math.max(7.0, this.cameraRadius - 2.0);
                    this.updateCameraPos();
                },

                zoomOut() {
                    this.cameraRadius = Math.min(32.0, this.cameraRadius + 2.0);
                    this.updateCameraPos();
                },

                
                // Device pixel ratio-aware: HD tajam di retina, tetap hemat di perangkat lemah
                getRenderPixelRatio() {
                    const dpr = window.devicePixelRatio || 1;
                    let cap = (this.quality && this.quality.maxPixelRatio) || 2;
                    // Post-processing aktif = MSAA 4x + SMAA sudah menutup pinggir, jadi tidak perlu
                    // pixel ratio 2xffffffff yang bikin 4x jumlah piksel.
                    if (this.quality && this.quality.usePostProcessing) cap = Math.min(cap, 1.75);
                    return Math.min(dpr, cap);
                },

                quality: {
                    maxPixelRatio: 2,
                    msaaSamples: 2,
                    usePostProcessing: true,
                },

                // ---------------------------------------------------------
                //  Adaptive quality: kalau perangkat ternyata berat, turunkan
                //  bertahap (GTAO -> MSAA -> pixel ratio -> bloom) alih-alih
                //  membiarkan scene judder. Kalau GPU kuat, naikkan lagi.
                // ---------------------------------------------------------
                perf: {
                    level: 0,              // 0 = full, 1 = GTAO off, 2 = MSAA off, 3 = dipixel rendah, 4 = minimal
                    minLevel: 0,
                    samples: [],
                    lastSwitch: 0,
                    cooldown: 2500,
                },

                applyQualityLevel() {
                    const p = this.perf;
                    if (!this.composer) return;

                    const smaa = this.composer.passes.find((x) => x.constructor.name === 'SMAAPass');
                    const output = this.composer.passes[this.composer.passes.length - 1];

                    if (this.gtaoPass) this.gtaoPass.enabled = p.level < 1;
                    if (this.bloomPass) this.bloomPass.enabled = p.level < 4;

                    // MSAA dicek lewat jumlah sample di render target composer
                    const rt = this.composer.renderTarget1;
                    if (rt && rt.samples !== undefined) {
                        const wantSamples = p.level < 2 ? this.quality.msaaSamples : 0;
                        if (rt.samples !== wantSamples) {
                            rt.samples = wantSamples;
                            if (this.composer.renderTarget2) this.composer.renderTarget2.samples = wantSamples;
                            rt.dispose();
                            if (this.composer.renderTarget2) this.composer.renderTarget2.dispose();
                        }
                    }

                    if (smaa) smaa.enabled = p.level < 2;

                    const cap = p.level < 3 ? 1.75 : 1.2;
                    this.quality.maxPixelRatio = cap;
                    if (this.renderer) {
                        this.renderer.setPixelRatio(this.getRenderPixelRatio());
                        if (this.container) this.resizePostProcessing(this.container.clientWidth, this.container.clientHeight);
                    }
                },

                trackPerformance(fps) {
                    const p = this.perf;
                    p.samples.push(fps);
                    if (p.samples.length < 45) return;
                    const avg = p.samples.reduce((a, b) => a + b, 0) / p.samples.length;
                    p.samples = [];

                    const now = (typeof performance !== 'undefined') ? performance.now() : Date.now();
                    if (now - p.lastSwitch < p.cooldown) return;

                    if (avg < 26 && p.level < 4) {
                        p.level++;
                        p.lastSwitch = now;
                        this.applyQualityLevel();
                    } else if (avg > 55 && p.level > 0) {
                        p.level--;
                        p.lastSwitch = now;
                        this.applyQualityLevel();
                    }
                },

                // IBL / Environment Lighting via PMREM.
                // Dibangun sebagai "studio virtual": plafon hangat terang + panel jendela dingin + bounce lantai kayu.
                // Hasilnya: specular & ambient occlusion believable di semua material PBR.
                setupEnvironmentLighting() {
                    if (!this.renderer) return;
                    try {
                        const pmrem = new THREE.PMREMGenerator(this.renderer);
                        if (pmrem.compileEquirectangularShader) pmrem.compileEquirectangularShader();

                        const envScene = new THREE.Scene();

                        // Cangkang ruangan gelap hangat (menjadi warna ambient dasar, bukan hitam pekat)
                        const shell = new THREE.Mesh(
                            new THREE.BoxGeometry(20, 12, 20),
                            new THREE.MeshBasicMaterial({ side: THREE.BackSide })
                        );
                        shell.material.color.setRGB(0.055, 0.045, 0.038);
                        envScene.add(shell);

                        // Panel cahaya HDR (MeshBasicMaterial + setRGB > 1 = nilai radiometric HDR)
                        const addPanel = (hex, intensity, w, h, d, pos, rot) => {
                            const panel = new THREE.Mesh(
                                new THREE.BoxGeometry(w, h, d),
                                new THREE.MeshBasicMaterial()
                            );
                            panel.material.color.setHex(hex).multiplyScalar(intensity);
                            panel.position.set(pos[0], pos[1], pos[2]);
                            if (rot) panel.rotation.set(rot[0], rot[1], rot[2]);
                            envScene.add(panel);
                            return panel;
                        };

                        // Plafon: deretan lampu hangat (sumber utama bounce atas)
                        addPanel(0xfff1dc, 5.0, 12, 0.2, 3.2, [0, 5.4, -1.4]);
                        addPanel(0xffe3bc, 2.6, 3.2, 0.2, 9.0, [0, 5.4, 4.2]);

                        // Jendela besar sisi kiri: dingin, bounce biru kuat → kontras warna warm/cool
                        addPanel(0xcfe2ff, 3.6, 0.2, 5.0, 11.0, [-9.4, 3.0, 0.6]);

                        // Bounce lantai kayu hangat (menjaga bagian bawah objek tidak hitam)
                        addPanel(0x6b4526, 1.0, 18, 0.2, 18, [0, -5.4, 0]);

                        const envRT = pmrem.fromScene(envScene, 0.035);
                        this.scene.environment = envRT.texture;
                        if ('environmentIntensity' in this.scene) this.scene.environmentIntensity = 0.26;
                        if ('environmentRotation' in this.scene) this.scene.environmentRotation.y = Math.PI * 0.25;

                        this.envRT = envRT;

                        // Bersihkan scene sumber (GPU memory)
                        envScene.traverse((o) => {
                            if (o.isMesh) {
                                o.geometry.dispose();
                                if (Array.isArray(o.material)) o.material.forEach((m) => m.dispose());
                                else o.material.dispose();
                            }
                        });
                        pmrem.dispose();
                    } catch (err) {
                        console.warn('[AI Office] PMREM environment gagal, fallback ke flat light', err);
                    }
                },

                // Helper: tandai material sebagai "self-lit" supaya luminance linear-nya
                //didorong di atas bloom threshold. Dipakai untuk neon & layar, yang
                // memang BOLEH glowing karena itu sumber cahayanya sendiri.
                setSelfLit(material, mult = 2.0) {
                    if (material && material.color && material.color.setScalar) {
                        material.color.setScalar(mult);
                        material.toneMapped = true;
                    }
                    return material;
                },

                // Post-Processing pipeline: GTAO (contact shadow/grounding) → Bloom (neon & layar) → SMAA (HD) → Output
                initPostProcessing(width, height) {
                    this.composer = null;
                    if (!this.quality.usePostProcessing || !window.OFFICE_ADDONS) return;

                    const A = window.OFFICE_ADDONS;
                    try {
                        const renderTarget = new THREE.WebGLRenderTarget(width, height, {
                            type: THREE.HalfFloatType,
                            samples: this.quality.msaaSamples,
                        });
                        const composer = new A.EffectComposer(this.renderer, renderTarget);
                        composer.setPixelRatio(this.renderer.getPixelRatio());
                        composer.setSize(width, height);

                        composer.addPass(new A.RenderPass(this.scene, this.camera));

                        // GTAO — objek jadi "nempel di lantai" + occlusion di sudut ruangan (kunci rasanya real)
                        if (A.GTAOPass) {
                            const gtao = new A.GTAOPass(this.scene, this.camera, width, height);
                            gtao.output = A.GTAOPass.OUTPUT.Default;
                            gtao.blendIntensity = 0.85;
                            gtao.updateGtaoMaterial({
                                radius: 0.42,
                                distanceExponent: 1.6,
                                thickness: 0.9,
                                distanceFallOff: 1.0,
                                scale: 1.1,
                                samples: 8,
                                screenSpaceRadius: false,
                            });
                            gtao.updatePdMaterial({ lumaPhi: 8, depthPhi: 2.5, normalPhi: 4.5, radius: 5, samples: 12 });
                            composer.addPass(gtao);
                            this.gtaoPass = gtao;
                        }

                        // Bloom:threshold di NAIKkan karena pass ini jalan di buffer LINEAR HDR,
                        // bukan sudah tone-mapped (lihat WebGLPrograms: tone mapping hanya
                        // dipakai kalau render target === null, dan RenderPass render ke
                        // render target). Sebelumnya threshold 0.92 → permukaan putih yang
                        // TERCAHAYA (kasur, meja marmer, kulkas) ikut bloom dan terlihat
                        // "silau". Sekarang hanya material self-lit (neon, layar) yang lewat,
                        // dan material itu didorong di atas threshold lewat setSelfLit().
                        if (A.UnrealBloomPass) {
                            const bloom = new A.UnrealBloomPass(new THREE.Vector2(width, height), 0.28, 0.45, 1.35);
                            composer.addPass(bloom);
                            this.bloomPass = bloom;
                        }

                        composer.addPass(new A.SMAAPass());

                        // WAJIB terakhir: tone mapping + color space conversion
                        composer.addPass(new A.OutputPass());

                        this.composer = composer;
                    } catch (err) {
                        console.warn('[AI Office] Post-processing gagal, render langsung dipakai', err);
                        this.composer = null;
                    }
                },

                resizePostProcessing(width, height) {
                    if (!this.composer) return;
                    this.composer.setPixelRatio(this.renderer.getPixelRatio());
                    this.composer.setSize(width, height);
                    if (this.gtaoPass && this.gtaoPass.setSize) this.gtaoPass.setSize(width, height);
                    if (this.bloomPass && this.bloomPass.setSize) this.bloomPass.setSize(width, height);
                },

                renderFrame() {
                    if (this.composer) {
                        this.composer.render();
                    } else if (this.renderer && this.scene && this.camera) {
                        this.renderer.render(this.scene, this.camera);
                    }
                },

                onResize() {
                    if (!this.container || !this.camera || !this.renderer) return;
                    const w = this.container.clientWidth;
                    const h = this.container.clientHeight;
                    if (w === 0 || h === 0) return;
                    this.camera.aspect = w / h;
                    this.camera.updateProjectionMatrix();
                    this.renderer.setSize(w, h);
                    this.renderer.setPixelRatio(this.getRenderPixelRatio());
                    this.resizePostProcessing(w, h);
                },

                toggleFullscreen() {
                    const elem = document.getElementById('three-office-card') || this.container?.parentElement;
                    if (!document.fullscreenElement) {
                        if (elem?.requestFullscreen) {
                            elem.requestFullscreen();
                        } else if (elem?.webkitRequestFullscreen) {
                            elem.webkitRequestFullscreen();
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        }
                    }
                    setTimeout(() => {
                        this.onResize();
                    }, 250);
                },

                toggleExpand() {
                    this.toggleFullscreen();
                },

                // Posisi koordinat penting di ruangan (Duduk pas di kursi ergonomis tanpa tembus meja)
                spots: {
                    dewiDesk: { x: -3.2, y: 0.44, z: 1.45, rotY: Math.PI },    // Duduk di meja kerja saat ada chat
                    dewiLounge: { x: 4.4, y: 0.46, z: 1.4, rotY: 0.0 },        // Duduk santai di sofa sebelah kiri
                    dewiBed: { x: -10.5, y: 0.58, z: 1.3, rotY: Math.PI / 2 },  // Berbaring pas di kasur kamar yang mepet dinding
                    singgihDesk: { x: -1.2, y: 0.44, z: 1.45, rotY: Math.PI },  // Duduk di meja kerja saat ada task core
                    singgihLounge: { x: 5.0, y: 0.46, z: 1.4, rotY: 0.0 },      // Duduk santai di sofa tengah jejeran
                    anderaDesk: { x: -2.2, y: 0.44, z: -1.35, rotY: 0.0 },      // Duduk di kursi seberang meja (menghadap +Z)
                    anderaLounge: { x: 5.6, y: 0.46, z: 1.4, rotY: 0.0 },       // Duduk santai di sofa sebelah kanan jejeran bertiga
                },

                // Status real-time masing-masing bot
                currentSinggihStatus: 'break',
                currentAnderaStatus: 'break',

                // State transisi animasi jalan untuk masing-masing karakter AI
                dewiWalk: { isMoving: false, startX: 0, startZ: 0, targetX: 0, targetZ: 0, targetRotY: 0, progress: 1, walkDuration: 1.0, phase: 0 },
                singgihWalk: { isMoving: false, startX: 0, startZ: 0, targetX: 0, targetZ: 0, targetRotY: 0, progress: 1, walkDuration: 1.0, phase: 0 },
                anderaWalk: { isMoving: false, startX: 0, startZ: 0, targetX: 0, targetZ: 0, targetRotY: 0, progress: 1, walkDuration: 1.0, phase: 0 },

                // Skala fase langkah: berapa radian fase per unit jarak tempuh (kontrol kecepatan ayunan kaki)
                walkPhasePerUnit: 6.2,

                // Kedip mata realistis (blink interval acak + durasi singkat elegan)
                blinkState: { dewi: { next: 2, timer: 0 }, singgih: { next: 3, timer: 0 }, andera: { next: 2.5, timer: 0 }, budi: { next: 4, timer: 0 } },
                updateBlink(key, data, delta) {
                    if (!data || (!data.eyeL && !data.eyeR)) return;
                    const st = this.blinkState[key];
                    if (!st) return;
                    st.timer += delta;
                    if (st.timer >= st.next) {
                        st.timer = 0;
                        st.next = 1.5 + Math.random() * 4; // jeda antar kedip 1.5-5.5 detik
                    }
                    // Mata tertutup sekitar 0.25 detik (kedip manusia normal ~100-400ms)
                    const t = st.timer;
                    let openness = 1;
                    if (t < 0.08) {
                        openness = 1 - (t / 0.08) * 0.95; // tutup cepat
                    } else if (t < 0.25) {
                        openness = 0.05 + ((t - 0.08) / 0.17) * 0.95; // buka perlahan
                    }
                    if (data.eyeL) data.eyeL.scale.y = Math.max(0.05, openness);
                    if (data.eyeR) data.eyeR.scale.y = Math.max(0.05, openness);
                },

                singgihWaypoints: [],
                currentSinggihWpIdx: 0,
                anderaWaypoints: [],
                currentAnderaWpIdx: 0,

                // Live bubbles
                bubbles: {
                    dewi: null,
                    singgih: null,
                    andera: null
                },

                container: null,

                init(container, initialStatus) {
                    if (!container) return;
                    this.container = container;
                    this.currentCsStatus = initialStatus || 'break';

                    const bootStartedAt = Date.now();

                    const setup = () => {
                        if (typeof THREE === 'undefined') {
                            setTimeout(setup, 60);
                            return;
                        }
                        // Addon post-processing dimuat via Vite (module, dievaluasi setelah script klasik).
                        // Tunggu singkat, lalu lanjut tanpa post-processing supaya halaman tidak pernah blank.
                        if (!window.OFFICE_ADDONS && Date.now() - bootStartedAt < 2500) {
                            setTimeout(setup, 60);
                            return;
                        }

                        let width = container.clientWidth || container.offsetWidth;
                        let height = container.clientHeight || container.offsetHeight;

                        if (!width || !height) {
                            const rect = container.getBoundingClientRect();
                            width = rect.width || 900;
                            height = rect.height || 540;
                        }

                        if (width < 50 || height < 50) {
                            setTimeout(setup, 60);
                            return;
                        }

                        container.innerHTML = '';
                        if (this.animationFrameId) {
                            cancelAnimationFrame(this.animationFrameId);
                        }

                        this.clock = new THREE.Clock();
                        this.screenMeshes = [];

                        // 1. Scene & Warm Luxury Studio Aesthetics
                        this.scene = new THREE.Scene();
                        // Latar studio hangat elegan (deep charcoal warm espresso)
                        this.scene.background = new THREE.Color(0x141210);
                        // Fog LINEAR (bukan Exp2): Exp2 padat bikin seluruh ruang jadi gepeng & kontras hilang
                        this.scene.fog = new THREE.Fog(0x141210, 30, 70);

                        // 2. Camera Isometric Perspective
                        const aspect = width / height;
                        this.camera = new THREE.PerspectiveCamera(34, aspect, 0.1, 200);
                        this.updateCameraPos();

                        // 3. WebGL Renderer — HD, Color Management (r152+), AgX Tone Mapping
                        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance', stencil: false, depth: true });
                        this.renderer.setSize(width, height);
                        this.renderer.setPixelRatio(this.getRenderPixelRatio());
                        this.renderer.shadowMap.enabled = true;
                        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
                        // Ruangan statis → shadow map tidak perlu di-render ulang tiap frame.
                        // Di-refresh tiap N frame (lihat animate()), cukup untuk geraknya karakter
                        // dan jauh lebih murah daripada 4 shadow pass per frame.
                        this.renderer.shadowMap.autoUpdate = false;
                        this.renderer.shadowMap.needsUpdate = true;
                        this.shadowUpdateInterval = 3;
                        this.shadowFrame = 0;

                        // Color management modern: output conversion handled renderer (OutputPass ikut membaca ini)
                        if (THREE.SRGBColorSpace) this.renderer.outputColorSpace = THREE.SRGBColorSpace;

                        // Tone mapping kontras tinggi: AgX → shadow tetap pekat, highlight melembut halus (no clipping)
                        if (THREE.AgXToneMapping !== undefined) {
                            this.renderer.toneMapping = THREE.AgXToneMapping;
                            this.renderer.toneMappingExposure = 0.78;
                        } else if (THREE.NeutralToneMapping !== undefined) {
                            this.renderer.toneMapping = THREE.NeutralToneMapping;
                            this.renderer.toneMappingExposure = 0.95;
                        } else if (THREE.ACESFilmicToneMapping) {
                            this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
                            this.renderer.toneMappingExposure = 1.0;
                        }
                        // Keep examples: strong cache clearing setiap frame biar anti flicker pada scene besar
                        this.renderer.info.autoReset = true;
                        container.appendChild(this.renderer.domElement);

                        // 3b. IBL / Environment Map (PMREM) — INI kunci agar shading PBR terbaca "real"
                        //     Tanpa IBL, MeshStandardMaterial cuma dapat diffuse flat dan specular hilang.
                        this.setupEnvironmentLighting();

                        // 4. Pencahayaan: ambient nyaris nol (IBL yang menangani ambient), sisasummanya
                        //    dari key/fill/rim + practical lights dalam satuan FISIK (candela) supaya
                        //    benar-benar membentuk pool of light → kontras, bukan flat-lit.
                        const ambient = new THREE.AmbientLight(0xffedd5, 0.02);
                        this.scene.add(ambient);

                        // Hemisphere tipis: bounce langit dingin vs pantulan lantai kayu hangat
                        const hemiLight = new THREE.HemisphereLight(0xffe9cc, 0x2a1c12, 0.13);
                        this.scene.add(hemiLight);

                        // A. Key Light Utama (angin-angin plafond / skylight) → sumber shadow utama yang tegas
                        const sunLight = new THREE.DirectionalLight(0xfff2e0, 1.9);
                        sunLight.position.set(9, 15, 11);
                        sunLight.castShadow = true;
                        sunLight.shadow.mapSize.width = 2048;
                        sunLight.shadow.mapSize.height = 2048;
                        sunLight.shadow.bias = -0.00022;
                        sunLight.shadow.normalBias = 0.028;
                        // Frustum shadow cukup rapat untuk menutup seluruh ruangan 24x15
                        sunLight.shadow.camera.near = 6;
                        sunLight.shadow.camera.far = 44;
                        sunLight.shadow.camera.left = -13.5;
                        sunLight.shadow.camera.right = 13.5;
                        sunLight.shadow.camera.top = 11;
                        sunLight.shadow.camera.bottom = -11;
                        this.scene.add(sunLight);
                        if (sunLight.target) { sunLight.target.position.set(0, 0, 0); this.scene.add(sunLight.target); }

                        // A2. Key Sekunder dari sisi luar (jendela) → volume & layer bayangan kedua
                        // Sengaja TIDAK castShadow: lapisan bayangi kedua itu mahal, dan shadow utama
                        // sudah memberi kedalaman. Fungsinya di sini hanya bounce dingin + pemisah nada.
                        const windowKey = new THREE.DirectionalLight(0xdce9ff, 0.6);
                        windowKey.position.set(-12, 7, 9);
                        this.scene.add(windowKey);
                        if (windowKey.target) { windowKey.target.position.set(0, 1, 0); this.scene.add(windowKey.target); }

                        // B. Fill Light Lembut dari arah seberang (menghilangkan bayangan tebal yang flat)
                        const fillLight = new THREE.DirectionalLight(0xa7c7ff, 0.16);
                        fillLight.position.set(-10, 6, -8);
                        this.scene.add(fillLight);

                        // C. Rim Light Hangat dari belakang (memisahkan objek dari dinding, kesan kedalaman)
                        const rimLight = new THREE.DirectionalLight(0xffd9a0, 0.75);
                        rimLight.position.set(2, 7, -14);
                        this.scene.add(rimLight);

                        // D. Lampu Sorot di Meja Kerja (SpotLight, intensitas candela → foci nyorot nyata)
                        const deskSpotLight = new THREE.SpotLight(0xffdfa9, 16, 12, Math.PI / 3.2, 0.5, 2);
                        deskSpotLight.position.set(-2.2, 4.2, 0.1);
                        deskSpotLight.target.position.set(-2.2, 0.9, 0.1);
                        deskSpotLight.castShadow = true;
                        deskSpotLight.shadow.mapSize.width = 1024;
                        deskSpotLight.shadow.mapSize.height = 1024;
                        deskSpotLight.shadow.bias = -0.0006;
                        deskSpotLight.shadow.normalBias = 0.02;
                        deskSpotLight.shadow.camera.near = 0.6;
                        deskSpotLight.shadow.camera.far = 10;
                        this.scene.add(deskSpotLight);
                        this.scene.add(deskSpotLight.target);

                        const deskPendantLight = new THREE.PointLight(0xffe5bd, 7, 9, 2);
                        deskPendantLight.position.set(-2.2, 3.6, 0.1);
                        this.scene.add(deskPendantLight);

                        // E. Ruang Lounge Santai: Warm Golden Glow (Hangat Mewah)
                        const loungeWarmLight = new THREE.PointLight(0xffd485, 11, 14, 2);
                        loungeWarmLight.position.set(5.0, 3.6, 1.4);
                        this.scene.add(loungeWarmLight);

                        // F. Ruang Dapur / Pantry: Pencahayaan Putih Bersih Modern (Cool White 5000K)
                        const pantryCleanWhite = new THREE.PointLight(0xf1f5f9, 9, 12, 2);
                        pantryCleanWhite.position.set(5.2, 3.6, -3.2);
                        this.scene.add(pantryCleanWhite);

                        // G. Ruang Kamar Tidur: Warm Cozy Bedside Ambient (Lampu Hangat 2700K)
                        const bedroomCeilingLight = new THREE.PointLight(0xffe1a8, 7.5, 10, 2);
                        bedroomCeilingLight.position.set(-9.6, 3.8, 1.4);
                        this.scene.add(bedroomCeilingLight);

                        // H. Neon Box Backlight (putih minimalis elegan)
                        const neonBoxLight = new THREE.PointLight(0xffffff, 6, 8, 2);
                        neonBoxLight.position.set(0, 3.8, -4.0);
                        this.scene.add(neonBoxLight);

                        // 4b. Pipeline Post-Processing (GTAO + Bloom + SMAA) → grounding, kontras, dan HD
                        this.initPostProcessing(width, height);

                        // 5. Inisialisasi Dynamic Animated Screen Texture (Live Code/Chat Matrix)
                        this.initAnimatedScreenCanvas();

                        // 6. Build Multi-Room Studio & Characters
                        this.buildRoom();

                        // Set posisi awal ketiga karakter sesuai status aktual (Anti Loncat saat refresh)
                        if (this.dewiGroup) {
                            const initSpot = (this.currentCsStatus === 'working') ? this.spots.dewiDesk : (this.currentCsStatus === 'sleeping' ? this.spots.dewiBed : this.spots.dewiLounge);
                            this.dewiGroup.position.set(initSpot.x, initSpot.y, initSpot.z);
                            this.dewiGroup.rotation.y = initSpot.rotY;
                        }
                        if (this.singgihGroup) {
                            const initSinggihSpot = (this.currentSinggihStatus === 'working') ? this.spots.singgihDesk : this.spots.singgihLounge;
                            this.singgihGroup.position.set(initSinggihSpot.x, initSinggihSpot.y, initSinggihSpot.z);
                            this.singgihGroup.rotation.y = initSinggihSpot.rotY;
                        }
                        if (this.anderaGroup) {
                            const initAnderaSpot = (this.currentAnderaStatus === 'working') ? this.spots.anderaDesk : this.spots.anderaLounge;
                            this.anderaGroup.position.set(initAnderaSpot.x, initAnderaSpot.y, initAnderaSpot.z);
                            this.anderaGroup.rotation.y = initAnderaSpot.rotY;
                        }

                    this.renderFrame();

                        // 7. Mouse / Touch Controls & Zoom Wheel
                        const dom = this.renderer.domElement;
                        dom.addEventListener('wheel', (e) => {
                            e.preventDefault();
                            if (e.deltaY > 0) {
                                this.zoomOut();
                            } else {
                                this.zoomIn();
                            }
                        }, { passive: false });

                        let isPanning = false;
                        dom.addEventListener('contextmenu', (e) => e.preventDefault());
                        dom.addEventListener('mousedown', (e) => {
                            this.isDragging = true;
                            isPanning = (e.button === 2 || e.shiftKey);
                            this.prevMouse = { x: e.clientX, y: e.clientY };
                        });
                        window.addEventListener('mouseup', () => { 
                            this.isDragging = false; 
                            isPanning = false; 
                        });
                        window.addEventListener('mousemove', (e) => {
                            if (!this.isDragging) return;
                            const dx = e.clientX - this.prevMouse.x;
                            const dy = e.clientY - this.prevMouse.y;
                            
                            if (isPanning) {
                                // Pan camera (geser posisi target lihat bebas kemana saja)
                                const factor = (this.cameraRadius || 18) * 0.0018;
                                const sinY = Math.sin(this.rotY);
                                const cosY = Math.cos(this.rotY);
                                this.targetLookAt.x -= (dx * cosY + dy * sinY) * factor;
                                this.targetLookAt.z -= (-dx * sinY + dy * cosY) * factor;
                            } else {
                                // Orbit rotation
                                this.rotY -= dx * 0.005;
                                this.rotX = Math.max(0.08, Math.min(1.48, this.rotX + dy * 0.004));
                            }
                            this.prevMouse = { x: e.clientX, y: e.clientY };
                            this.updateCameraPos();
                        });
                        window.addEventListener('mouseup', () => { this.isDragging = false; });
                        window.addEventListener('mousemove', (e) => {
                            if (!this.isDragging) return;
                            const dx = e.clientX - this.prevMouse.x;
                            const dy = e.clientY - this.prevMouse.y;
                            this.rotY -= dx * 0.005;
                            this.rotX = Math.max(0.08, Math.min(1.48, this.rotX + dy * 0.004));
                            this.prevMouse = { x: e.clientX, y: e.clientY };
                            this.updateCameraPos();
                        });

                        dom.addEventListener('touchstart', (e) => {
                            if (e.touches.length === 1) {
                                this.isDragging = true;
                                this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                            }
                        }, { passive: true });
                        window.addEventListener('touchend', () => { this.isDragging = false; });
                        window.addEventListener('touchmove', (e) => {
                            if (!this.isDragging || e.touches.length !== 1) return;
                            const dx = e.touches[0].clientX - this.prevMouse.x;
                            const dy = e.touches[0].clientY - this.prevMouse.y;
                            this.rotY -= dx * 0.006;
                            this.rotX = Math.max(0.08, Math.min(1.48, this.rotX + dy * 0.005));
                            this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                            this.updateCameraPos();
                        }, { passive: true });

                        window.addEventListener('resize', () => this.onResize());

                        // Scene selesai dibangun: rekam waktu aktivitas supaya
                        // reconcileTick tidak langsung memicu perjalanan di frame pertama.
                        this.markActivity();
                        this.flushPendingStatus();
                        this.animate();
                    };

                    setup();
                },

                initAnimatedScreenCanvas() {
                    this.screenCanvas = document.createElement('canvas');
                    this.screenCanvas.width = 512;
                    this.screenCanvas.height = 280;
                    this.screenCtx = this.screenCanvas.getContext('2d');
                    this.screenTexture = new THREE.CanvasTexture(this.screenCanvas);

                    // Dedicated TV Canvas untuk Smart TV 65-inch di Lounge (Sinkron RentSpace TV Display)
                    this.tvCanvas = document.createElement('canvas');
                    this.tvCanvas.width = 640;
                    this.tvCanvas.height = 360;
                    this.tvCtx = this.tvCanvas.getContext('2d');
                    this.tvTexture = new THREE.CanvasTexture(this.tvCanvas);
                },

                // Lantai Parquet Kayu Walnut Procedural (Papan kayu nyata dengan serat alami + variasi warna)
                createWoodFloorTexture() {
                    const c = document.createElement('canvas');
                    c.width = 512;
                    c.height = 512;
                    const ctx = c.getContext('2d');

                    const boardH = 64; // 8 papan horizontal, setiap board berupa papan kayu panjang
                    const tones = ['#4a3022', '#563827', '#4d3223', '#5b3d2b', '#513331', '#5f412d'];
                    for (let b = 0; b < 8; b++) {
                        // Base warna papan dengan gradasi naik/turun halus (efek lembut kayu)
                        const tone = tones[b % tones.length];
                        const grad = ctx.createLinearGradient(0, b * boardH, 0, (b + 1) * boardH);
                        grad.addColorStop(0, this.shade(tone, 18));
                        grad.addColorStop(0.5, tone);
                        grad.addColorStop(1, this.shade(tone, -14));
                        ctx.fillStyle = grad;
                        ctx.fillRect(0, b * boardH, 512, boardH);

                        // Serat kayu: garis-garis tipis meliuk alami
                        ctx.strokeStyle = 'rgba(20,10,4,0.14)';
                        for (let g = 0; g < 10; g++) {
                            ctx.lineWidth = 1 + (Math.random() * 1.5);
                            ctx.beginPath();
                            let y = b * boardH + 6 + (g / 10) * (boardH - 12);
                            const amp = 3 + Math.random() * 4;
                            ctx.moveTo(0, y);
                            for (let x = 0; x <= 512; x += 16) {
                                y += Math.sin(x * 0.02 + g * 1.3) * 0.8 + (Math.random() - 0.5) * amp;
                                ctx.lineTo(x, y);
                            }
                            ctx.stroke();
                        }

                        // Simpul kayu (knot) kecil di beberapa papan
                        if (b % 2 === 0) {
                            const kx = 80 + Math.random() * 400;
                            const ky = b * boardH + boardH / 2;
                            ctx.fillStyle = 'rgba(28,12,4,0.28)';
                            ctx.beginPath();
                            ctx.ellipse(kx, ky, 5 + Math.random() * 4, 2 + Math.random() * 2, 0, 0, Math.PI * 2);
                            ctx.fill();
                        }
                    }

                    // Garis sambungan antar papan (deep grooves)
                    ctx.strokeStyle = 'rgba(12,6,2,0.5)';
                    ctx.lineWidth = 2;
                    for (let b = 1; b < 8; b++) {
                        ctx.beginPath();
                        ctx.moveTo(0, b * boardH);
                        ctx.lineTo(512, b * boardH);
                        ctx.stroke();
                    }
                    // Sambungan vertikal (staggered seams) alami papan kayu
                    for (let b = 0; b < 8; b++) {
                        const seamCount = 4 + Math.floor(Math.random() * 3);
                        ctx.strokeStyle = 'rgba(12,6,2,0.38)';
                        ctx.lineWidth = 1.5;
                        for (let s = 0; s < seamCount; s++) {
                            const sx = ((b % 2) * 96) + (s * 110) + (Math.random() * 18);
                            ctx.beginPath();
                            ctx.moveTo(sx, b * boardH);
                            ctx.lineTo(sx, (b + 1) * boardH);
                            ctx.stroke();
                        }
                    }

                    // Sheen / highlight halus di permukaan agar tidak datar
                    const gloss = ctx.createLinearGradient(0, 0, 512, 512);
                    gloss.addColorStop(0, 'rgba(255,240,220,0.05)');
                    gloss.addColorStop(0.5, 'rgba(255,240,220,0.0)');
                    gloss.addColorStop(1, 'rgba(255,230,200,0.06)');
                    ctx.fillStyle = gloss;
                    ctx.fillRect(0, 0, 512, 512);

                    const tex = new THREE.CanvasTexture(c);
                    tex.wrapS = THREE.RepeatWrapping;
                    tex.wrapT = THREE.RepeatWrapping;
                    tex.repeat.set(5, 3);
                    tex.anisotropy = this.renderer ? this.renderer.capabilities.getMaxAnisotropy() : 8;
                    if (THREE.SRGBColorSpace) tex.colorSpace = THREE.SRGBColorSpace;
                    return tex;
                },

                // Height field parquet (dipakai untuk normal map + roughness map).
                // Tanpa ini, lantai cuma punya warna → tidak ada highlight tepi papan, terlihat seperti stiker.
                createWoodFloorHeightMap(size = 1024) {
                    const c = document.createElement('canvas');
                    c.width = size;
                    c.height = size;
                    const ctx = c.getContext('2d');
                    const boardH = size / 8;

                    // Dasar: tengah abu-abu (tinggi rata)
                    ctx.fillStyle = '#808080';
                    ctx.fillRect(0, 0, size, size);

                    // Serat kayu: gelombang halus di dalam tiap papan
                    for (let b = 0; b < 8; b++) {
                        for (let i = 0; i < 46; i++) {
                            const y0 = b * boardH + Math.random() * boardH;
                            const amp = 0.6 + Math.random() * 2.4;
                            const val = 128 + (Math.random() - 0.5) * 26;
                            ctx.strokeStyle = `rgb(${val | 0},${val | 0},${val | 0})`;
                            ctx.lineWidth = 0.8 + Math.random() * 2.2;
                            ctx.beginPath();
                            let y = y0;
                            ctx.moveTo(0, y);
                            for (let x = 0; x <= size; x += 12) {
                                y += Math.sin(x * 0.013 + b * 2.1 + i) * amp * 0.22;
                                ctx.lineTo(x, y);
                            }
                            ctx.stroke();
                        }
                    }

                    // Chamfer (belah miring) di setiap sambungan papan -> resulting in edge highlights
                    const groove = (x0, y0, x1, y1, width) => {
                        const g = ctx.createLinearGradient(x0, y0, x1, y1);
                        g.addColorStop(0, 'rgba(0,0,0,0)');
                        g.addColorStop(0.5, 'rgba(0,0,0,0.85)');
                        g.addColorStop(1, 'rgba(0,0,0,0)');
                        ctx.strokeStyle = g;
                        ctx.lineWidth = width;
                        ctx.beginPath();
                        ctx.moveTo(x0, y0);
                        ctx.lineTo(x1, y1);
                        ctx.stroke();
                    };

                    for (let b = 1; b < 8; b++) {
                        const y = b * boardH;
                        groove(0, y - 3.5, size, y - 3.5, 7);
                        groove(0, y + 3.5, size, y + 3.5, 7);
                    }
                    for (let b = 0; b < 8; b++) {
                        const seams = 4 + ((b * 7) % 3);
                        for (let s = 1; s < seams; s++) {
                            const sx = ((b % 2) * 96) + (s * (size / seams)) + ((b * 37) % 19);
                            groove(sx - 3.5, b * boardH, sx - 3.5, (b + 1) * boardH, 7);
                            groove(sx + 3.5, b * boardH, sx + 3.5, (b + 1) * boardH, 7);
                        }
                    }

                    return c;
                },

                // Height map → normal map (Sobel), supaya sambungan papan benar-benar terlihat tonjok secara optik
                heightToNormalMap(heightCanvas, strength = 2.6) {
                    const size = heightCanvas.width;
                    const src = heightCanvas.getContext('2d', { willReadFrequently: true }).getImageData(0, 0, size, size).data;
                    const out = document.createElement('canvas');
                    out.width = size;
                    out.height = size;
                    const octx = out.getContext('2d');
                    const img = octx.createImageData(size, size);
                    const at = (x, y) => src[((y & (size - 1)) * size + (x & (size - 1))) * 4] / 255;

                    for (let y = 0; y < size; y++) {
                        for (let x = 0; x < size; x++) {
                            const tl = at(x - 1, y - 1), t = at(x, y - 1), tr = at(x + 1, y - 1);
                            const l = at(x - 1, y), r = at(x + 1, y);
                            const bl = at(x - 1, y + 1), b = at(x, y + 1), br = at(x + 1, y + 1);
                            const dx = (tr + 2 * r + br) - (tl + 2 * l + bl);
                            const dy = (bl + 2 * b + br) - (tl + 2 * t + tr);
                            let nx = -dx * strength;
                            let ny = -dy * strength;
                            const nz = 1;
                            const len = Math.hypot(nx, ny, nz) || 1;
                            nx /= len; ny /= len;
                            const i = (y * size + x) * 4;
                            img.data[i] = (nx * 0.5 + 0.5) * 255;
                            img.data[i + 1] = (ny * 0.5 + 0.5) * 255;
                            img.data[i + 2] = ((1 / len) * 0.5 + 0.5) * 255;
                            img.data[i + 3] = 255;
                        }
                    }
                    octx.putImageData(img, 0, 0);
                    return out;
                },

                // Roughness map dari height field: toolbar lebih kasar, sambungan lebih gelap (kering)
                heightToRoughnessMap(heightCanvas, baseRough = 0.34, range = 0.30) {
                    const size = heightCanvas.height;
                    const src = heightCanvas.getContext('2d', { willReadFrequently: true }).getImageData(0, 0, size, size).data;
                    const out = document.createElement('canvas');
                    out.width = size;
                    out.height = size;
                    const octx = out.getContext('2d');
                    const img = octx.createImageData(size, size);
                    for (let i = 0; i < size * size; i++) {
                        const h = src[i * 4] / 255;
                        const rough = Math.max(0.04, Math.min(1, baseRough + (0.5 - h) * range * 2));
                        const v = rough * 255;
                        img.data[i * 4] = v;
                        img.data[i * 4 + 1] = v;
                        img.data[i * 4 + 2] = v;
                        img.data[i * 4 + 3] = 255;
                    }
                    octx.putImageData(img, 0, 0);
                    return out;
                },

                makeFloorMaps() {
                    if (this.floorMaps) return this.floorMaps;
                    const height = this.createWoodFloorHeightMap(1024);
                    const maxAniso = this.renderer ? this.renderer.capabilities.getMaxAnisotropy() : 8;

                    const normalMap = new THREE.CanvasTexture(this.heightToNormalMap(height, 2.4));
                    normalMap.wrapS = normalMap.wrapT = THREE.RepeatWrapping;
                    normalMap.repeat.set(5, 3);
                    normalMap.anisotropy = maxAniso;

                    const roughnessMap = new THREE.CanvasTexture(this.heightToRoughnessMap(height, 0.30, 0.34));
                    roughnessMap.wrapS = roughnessMap.wrapT = THREE.RepeatWrapping;
                    roughnessMap.repeat.set(5, 3);
                    roughnessMap.anisotropy = maxAniso;

                    this.floorMaps = { normalMap, roughnessMap };
                    return this.floorMaps;
                },

                // Dinding plaster halus: normal + roughness lembut supaya tembok tidak terlihat seperti cat datar
                makeWallMaps() {
                    if (this.wallMaps) return this.wallMaps;
                    const size = 512;
                    const c = document.createElement('canvas');
                    c.width = size;
                    c.height = size;
                    const ctx = c.getContext('2d');
                    ctx.fillStyle = '#808080';
                    ctx.fillRect(0, 0, size, size);
                    // Noise butiran halus (plaster/kaca LDL)
                    for (let i = 0; i < 26000; i++) {
                        const x = Math.random() * size;
                        const y = Math.random() * size;
                        const v = 128 + (Math.random() - 0.5) * 46;
                        ctx.fillStyle = `rgba(${v | 0},${v | 0},${v | 0},0.55)`;
                        ctx.fillRect(x, y, 1.2, 1.2);
                    }
                    // Lembutan sapuan kuas besar
                    for (let i = 0; i < 90; i++) {
                        ctx.strokeStyle = `rgba(128,128,128,0.16)`;
                        ctx.lineWidth = 6 + Math.random() * 22;
                        ctx.beginPath();
                        ctx.moveTo(Math.random() * size, Math.random() * size);
                        ctx.bezierCurveTo(
                            Math.random() * size, Math.random() * size,
                            Math.random() * size, Math.random() * size,
                            Math.random() * size, Math.random() * size
                        );
                        ctx.stroke();
                    }

                    const maxAniso = this.renderer ? this.renderer.capabilities.getMaxAnisotropy() : 8;
                    const normalMap = new THREE.CanvasTexture(this.heightToNormalMap(c, 0.55));
                    normalMap.wrapS = normalMap.wrapT = THREE.RepeatWrapping;
                    normalMap.repeat.set(10, 3);
                    normalMap.anisotropy = maxAniso;

                    const roughnessMap = new THREE.CanvasTexture(this.heightToRoughnessMap(c, 0.78, 0.16));
                    roughnessMap.wrapS = roughnessMap.wrapT = THREE.RepeatWrapping;
                    roughnessMap.repeat.set(10, 3);
                    roughnessMap.anisotropy = maxAniso;

                    this.wallMaps = { normalMap, roughnessMap };
                    return this.wallMaps;
                },

                // Helper kecil: terang-gelapkan warna hex
                shade(hex, pct) {
                    const n = parseInt(hex.slice(1), 16);
                    const r = Math.max(0, Math.min(255, (n >> 16) + pct));
                    const g = Math.max(0, Math.min(255, ((n >> 8) & 0xff) + pct));
                    const b = Math.max(0, Math.min(255, (n & 0xff) + pct));
                    return `rgb(${r},${g},${b})`;
                },

                // Blob Shadow lembut di bawah karakter (soft contact shadow)
                createContactShadow(size = 0.65) {
                    const c = document.createElement('canvas');
                    c.width = 128;
                    c.height = 128;
                    const ctx = c.getContext('2d');
                    const g = ctx.createRadialGradient(64, 64, 4, 64, 64, 64);
                    g.addColorStop(0, 'rgba(0,0,0,0.55)');
                    g.addColorStop(0.6, 'rgba(0,0,0,0.28)');
                    g.addColorStop(1, 'rgba(0,0,0,0)');
                    ctx.fillStyle = g;
                    ctx.fillRect(0, 0, 128, 128);

                    const tex = new THREE.CanvasTexture(c);
                    const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false, opacity: 1 });
                    const sprite = new THREE.Sprite(mat);
                    sprite.position.y = 0.012;
                    sprite.scale.set(size, size * 0.34, 1);
                    sprite.renderOrder = 0;
                    this.scene.add(sprite);
                    return sprite;
                },

                isPovMode: false,

                updateCameraPos() {
                    if (this.isPovMode && this.dewiGroup) {
                        // Kamera FPS ditaruh pas di posisi mata Dewi!
                        const headY = (this.dewiGroup.position.y || 0.44) + 0.88;
                        this.camera.position.set(this.dewiGroup.position.x, headY, this.dewiGroup.position.z - 0.12);
                        // Menatap lurus ke meja kerja dan monitor di depan mata
                        this.camera.lookAt(this.dewiGroup.position.x, headY - 0.05, this.dewiGroup.position.z - 2.5);
                        return;
                    }

                    const radius = this.cameraRadius || 18.5;
                    const tx = this.targetLookAt?.x || 0;
                    const ty = this.targetLookAt?.y || 1.0;
                    const tz = this.targetLookAt?.z || 0;

                    this.camera.position.x = tx + radius * Math.sin(this.rotY) * Math.cos(this.rotX);
                    this.camera.position.y = ty + radius * Math.sin(this.rotX) + 1.2;
                    this.camera.position.z = tz + radius * Math.cos(this.rotY) * Math.cos(this.rotX);
                    this.camera.lookAt(tx, ty, tz);
                },

                // Trigger perpindahan jalan Dewi (Waypoint Navigation Anti-Tembus Dinding/Meja)
                dewiWaypoints: [],
                currentWaypointIdx: 0,

                updateSinggihPosition(status) {
                    if (this.currentSinggihStatus === status && this.singgihWalk.isMoving) return;
                    this.currentSinggihStatus = status;
                    if (!this.singgihGroup) return;

                    const curX = this.singgihGroup.position.x;
                    const curZ = this.singgihGroup.position.z;
                    this.singgihWaypoints = [];

                    let target = this.spots.singgihLounge;
                    if (status === 'working') {
                        this.setMood('singgih', '⚙️ Core Processing AI');
                        target = this.spots.singgihDesk;
                        this.singgihWaypoints.push({ x: curX, z: 2.4 });
                        this.singgihWaypoints.push({ x: target.x, z: 2.4 });
                        this.singgihWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    } else {
                        this.setMood('singgih', '🛋️ Duduk Santai di Sofa');
                        target = this.spots.singgihLounge;
                        this.singgihWaypoints.push({ x: curX, z: 2.4 });
                        this.singgihWaypoints.push({ x: target.x, z: 2.4 });
                        this.singgihWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    }

                    this.currentSinggihWpIdx = 0;
                    this.startNextSinggihWaypoint();
                },

                startNextSinggihWaypoint() {
                    if (this.currentSinggihWpIdx >= this.singgihWaypoints.length) {
                        this.singgihWalk.isMoving = false;
                        if (this.singgihGroup && this.singgihGroup.userData) {
                            const data = this.singgihGroup.userData;
                            if (data.legL) data.legL.rotation.x = 0;
                            if (data.legR) data.legR.rotation.x = 0;
                            if (data.armL) data.armL.rotation.x = 0;
                            if (data.armR) data.armR.rotation.x = 0;
                        }
                        return;
                    }
                    const wp = this.singgihWaypoints[this.currentSinggihWpIdx];
                    this.singgihWalk.isMoving = true;
                    this.singgihWalk.startX = this.singgihGroup.position.x;
                    this.singgihWalk.startZ = this.singgihGroup.position.z;
                    this.singgihWalk.targetX = wp.x;
                    this.singgihWalk.targetZ = wp.z;
                    this.singgihWalk.targetRotY = (wp.rotY !== undefined) ? wp.rotY : Math.atan2(wp.x - this.singgihWalk.startX, wp.z - this.singgihWalk.startZ);

                    const dx = wp.x - this.singgihWalk.startX;
                    const dz = wp.z - this.singgihWalk.startZ;
                    const dist = Math.sqrt(dx * dx + dz * dz);
                    if (dist < 0.05) {
                        this.currentSinggihWpIdx++;
                        this.startNextSinggihWaypoint();
                        return;
                    }
                    this.singgihWalk.walkDuration = Math.max(0.4, dist / 1.75);
                    this.singgihWalk.progress = 0;
                    this.singgihGroup.rotation.y = Math.atan2(dx, dz);
                },

                updateAnderaPosition(status) {
                    if (this.currentAnderaStatus === status && this.anderaWalk.isMoving) return;
                    this.currentAnderaStatus = status;
                    if (!this.anderaGroup) return;

                    const curX = this.anderaGroup.position.x;
                    const curZ = this.anderaGroup.position.z;
                    this.anderaWaypoints = [];

                    let target = this.spots.anderaLounge;
                    if (status === 'working') {
                        this.setMood('andera', '📊 Menyusun Laporan Keuangan');
                        target = this.spots.anderaDesk;
                        // Rute Anti-Tembus Meja:
                        // Dari sofa (x=5.6, z=1.4) -> mundur ke lorong bebas (z=2.4) -> jalan ke koridor samping meja (x=1.0, z=2.4)
                        // -> masuk ke lorong belakang meja (x=1.0, z=-2.1) -> ke belakang kursi Andera (x=-2.2, z=-2.1)
                        // -> masuk ke kursi meja (x=-2.2, z=-1.35) menghadap meja (+Z)
                        if (curZ > -1.0) {
                            this.anderaWaypoints.push({ x: curX, z: 2.4 });
                            this.anderaWaypoints.push({ x: 1.0, z: 2.4 });
                            this.anderaWaypoints.push({ x: 1.0, z: -2.1 });
                        }
                        this.anderaWaypoints.push({ x: target.x, z: -2.1 });
                        this.anderaWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    } else {
                        this.setMood('andera', '🛋️ Duduk Santai di Sofa');
                        target = this.spots.anderaLounge;
                        // Mundur dari kursi meja ke lorong belakang (z=-2.1) -> keluar lewat koridor samping (x=1.0, z=-2.1)
                        // -> masuk lorong depan bebas (x=1.0, z=2.4) -> jalan lurus ke depan sofa (x=5.6, z=2.4) -> duduk di sofa
                        this.anderaWaypoints.push({ x: curX, z: -2.1 });
                        this.anderaWaypoints.push({ x: 1.0, z: -2.1 });
                        this.anderaWaypoints.push({ x: 1.0, z: 2.4 });
                        this.anderaWaypoints.push({ x: target.x, z: 2.4 });
                        this.anderaWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    }

                    this.currentAnderaWpIdx = 0;
                    this.startNextAnderaWaypoint();
                },

                startNextAnderaWaypoint() {
                    if (this.currentAnderaWpIdx >= this.anderaWaypoints.length) {
                        this.anderaWalk.isMoving = false;
                        if (this.anderaGroup && this.anderaGroup.userData) {
                            const data = this.anderaGroup.userData;
                            if (data.legL) data.legL.rotation.x = 0;
                            if (data.legR) data.legR.rotation.x = 0;
                            if (data.armL) data.armL.rotation.x = 0;
                            if (data.armR) data.armR.rotation.x = 0;
                        }
                        return;
                    }
                    const wp = this.anderaWaypoints[this.currentAnderaWpIdx];
                    this.anderaWalk.isMoving = true;
                    this.anderaWalk.startX = this.anderaGroup.position.x;
                    this.anderaWalk.startZ = this.anderaGroup.position.z;
                    this.anderaWalk.targetX = wp.x;
                    this.anderaWalk.targetZ = wp.z;
                    this.anderaWalk.targetRotY = (wp.rotY !== undefined) ? wp.rotY : Math.atan2(wp.x - this.anderaWalk.startX, wp.z - this.anderaWalk.startZ);

                    const dx = wp.x - this.anderaWalk.startX;
                    const dz = wp.z - this.anderaWalk.startZ;
                    const dist = Math.sqrt(dx * dx + dz * dz);
                    if (dist < 0.05) {
                        this.currentAnderaWpIdx++;
                        this.startNextAnderaWaypoint();
                        return;
                    }
                    this.anderaWalk.walkDuration = Math.max(0.4, dist / 1.75);
                    this.anderaWalk.progress = 0;
                    this.anderaGroup.rotation.y = Math.atan2(dx, dz);
                },

                // ==================================================================
                //  REALTIME SYNC LAYER
                //  Semua pemicu status (WebSocket Echo, tick polling, tombol
                //  sandbox) funnel lewat requestAgentStatus() di sini, bukan
                //  langsung memanggil updateXPosition(). Gunanya: status yang
                //  sama TAPI karakter diam di tempat yang salah (mis. scene
                //  baru selesai init, atau reconcile sebelumnya gagal) tetap
                //  memicu perjalanan baru. Guard lama hanya membandingkan
                //  status, jadi karakter bisa "nyangkut" di sofa selamanya.
                // ==================================================================
                agentConfig: {
                    dewi: {
                        group: 'dewiGroup', walk: 'dewiWalk', status: 'currentCsStatus', move: 'updateCsPosition',
                        desk: 'dewiDesk', lounge: 'dewiLounge', bed: 'dewiBed',
                    },
                    singgih: {
                        group: 'singgihGroup', walk: 'singgihWalk', status: 'currentSinggihStatus', move: 'updateSinggihPosition',
                        desk: 'singgihDesk', lounge: 'singgihLounge',
                    },
                    andera: {
                        group: 'anderaGroup', walk: 'anderaWalk', status: 'currentAnderaStatus', move: 'updateAnderaPosition',
                        desk: 'anderaDesk', lounge: 'anderaLounge',
                    },
                },

                targetSpotFor(character, status) {
                    const cfg = this.agentConfig[character];
                    if (!cfg) return null;
                    const key = status === 'working' ? 'desk' : ((status === 'sleeping' && cfg.bed) ? 'bed' : 'lounge');
                    return this.spots[cfg[key]] || null;
                },

                isAtSpot(group, spot, tol = 0.25) {
                    if (!group || !spot) return false;
                    return Math.abs(group.position.x - spot.x) < tol && Math.abs(group.position.z - spot.z) < tol;
                },

                lastActivityAt: 0,
                pendingStatus: {},

                markActivity() {
                    this.lastActivityAt = (typeof performance !== 'undefined') ? performance.now() : Date.now();
                },

                requestAgentStatus(character, status) {
                    const cfg = this.agentConfig[character];
                    if (!cfg) return false;
                    const group = this[cfg.group];

                    // Scene belum siap -> antrikan dulu,_flushPendingStatus() akan
                    // memainkannya begitu 3D scene selesai dibangun.
                    if (!group) {
                        this.pendingStatus[character] = status;
                        return false;
                    }

                    const walking = this[cfg.walk].isMoving;
                    const sameStatus = this[cfg.status] === status;
                    const inPlace = this.isAtSpot(group, this.targetSpotFor(character, status));

                    // Sudah di tempat, atau masih dalam perjalanan ke target yang sama.
                    if (sameStatus && (walking || inPlace)) {
                        this.markActivity();
                        return false;
                    }

                    this[cfg.move](status);
                    this.markActivity();
                    return true;
                },

                // Dipanggil tepat setelah scene selesai dibangun: mainkan event yang
                // datang sebelum 3D ready, lalu selaraskan semua karakter.
                flushPendingStatus() {
                    const queued = this.pendingStatus;
                    this.pendingStatus = {};

                    if (Object.keys(queued).length) {
                        Object.keys(queued).forEach((key) => this.requestAgentStatus(key, queued[key]));
                        return;
                    }

                    Object.keys(this.agentConfig).forEach((key) => {
                        const cfg = this.agentConfig[key];
                        this.requestAgentStatus(key, this[cfg.status]);
                    });
                },

                // Jaring pengaman terakhir: kalau WebSocket sempat putus atau event
                // telat datang, karakter tetap CARTUM. Idle >2 detik tanpa sampai ke
                // tujuan -> re-path dengan status terakhir yang diketahui.
                reconcileTick(nowMs) {
                    this.lastActivityAt = nowMs;
                    if (!this.dewiGroup) return;
                    Object.keys(this.agentConfig).forEach((key) => {
                        const cfg = this.agentConfig[key];
                        const group = this[cfg.group];
                        if (!group || this[cfg.walk].isMoving) return;
                        const spot = this.targetSpotFor(key, this[cfg.status]);
                        if (!spot || this.isAtSpot(group, spot)) return;
                        this[cfg.move](this[cfg.status]);
                    });
                },

                updateCsPosition(status) {
                    if (this.currentCsStatus === status && this.dewiWalk.isMoving) return;
                    this.currentCsStatus = status;

                    if (!this.dewiGroup) return;

                    // Reset rotasi tidur saat bangun jalan
                    this.dewiGroup.rotation.x = 0;
                    this.dewiGroup.rotation.z = 0;

                    let target = this.spots.dewiLounge;
                    if (status === 'working') {
                        this.setMood('dewi', '⌨️ Sedang Membalas Chat...');
                        target = this.spots.dewiDesk;
                    } else if (status === 'sleeping') {
                        this.setMood('dewi', '🪫 Low Energy · Tidur Zzz...');
                        target = this.spots.dewiBed;
                    } else {
                        const breakMoods = ['🎧 Lagi Dengerin Musik', '☕ Istirahat Santai', '🥤 Minum & Recharge', '🛋️ Duduk Santai Senang'];
                        const randomMood = breakMoods[Math.floor(Math.random() * breakMoods.length)];
                        this.setMood('dewi', randomMood);
                        target = this.spots.dewiLounge;
                    }

                    // Tentukan Rute Waypoints agar tidak menabrak meja atau dinding
                    const curX = this.dewiGroup.position.x;
                    const curZ = this.dewiGroup.position.z;
                    this.dewiWaypoints = [];

                    if (status === 'sleeping') {
                        // Dari meja/sofa ke kamar tidur AI:
                        this.dewiWaypoints.push({ x: curX, z: 2.4 });
                        this.dewiWaypoints.push({ x: -7.0, z: 2.2 }); // Depan pintu luar kamar
                        this.dewiWaypoints.push({ x: -7.8, z: 2.2 }); // Masuk melewati pintu
                        this.dewiWaypoints.push({ x: -9.2, z: 2.2 }); // Di dalam kamar
                        this.dewiWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY }); // Berbaring di kasur
                    } else if (status === 'working') {
                        // Dari kasur kamar tidur ATAU sofa menuju meja kerja saat chat masuk!
                        if (curX < -7.2) {
                            // Keluar kamar tidur:
                            this.dewiWaypoints.push({ x: -9.2, z: 2.2 });
                            this.dewiWaypoints.push({ x: -7.0, z: 2.2 });
                            this.dewiWaypoints.push({ x: target.x, z: 2.4 });
                        } else if (curX > 2.0) {
                            // Dari sofa lounge: lewat lorong bebas partisi (z=2.4)
                            this.dewiWaypoints.push({ x: curX, z: 2.4 });
                            this.dewiWaypoints.push({ x: target.x, z: 2.4 });
                        } else {
                            this.dewiWaypoints.push({ x: curX, z: 2.4 });
                            this.dewiWaypoints.push({ x: target.x, z: 2.4 });
                        }
                        this.dewiWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    } else {
                        // Menuju sofa lounge istirahat santai bersama:
                        if (curX < -7.2) {
                            // Keluar kamar tidur:
                            this.dewiWaypoints.push({ x: -9.2, z: 2.2 });
                            this.dewiWaypoints.push({ x: -7.0, z: 2.2 });
                        }
                        this.dewiWaypoints.push({ x: curX, z: 2.4 });
                        this.dewiWaypoints.push({ x: target.x, z: 2.4 });
                        this.dewiWaypoints.push({ x: target.x, z: target.z, rotY: target.rotY });
                    }

                    this.currentWaypointIdx = 0;
                    this.startNextWaypoint();
                },

                startNextWaypoint() {
                    if (this.currentWaypointIdx >= this.dewiWaypoints.length) {
                        this.dewiWalk.isMoving = false;
                        // Reset kaki dan tangan saat selesai sampai tujuan agar tidak jalan di tempat!
                        if (this.dewiGroup && this.dewiGroup.userData) {
                            const data = this.dewiGroup.userData;
                            if (data.legL) data.legL.rotation.x = 0;
                            if (data.legR) data.legR.rotation.x = 0;
                            if (data.armL) data.armL.rotation.x = 0;
                            if (data.armR) data.armR.rotation.x = 0;
                        }
                        return;
                    }

                    const wp = this.dewiWaypoints[this.currentWaypointIdx];
                    this.dewiWalk.isMoving = true;
                    this.dewiWalk.startX = this.dewiGroup.position.x;
                    this.dewiWalk.startZ = this.dewiGroup.position.z;
                    this.dewiWalk.targetX = wp.x;
                    this.dewiWalk.targetZ = wp.z;
                    this.dewiWalk.targetRotY = (wp.rotY !== undefined) ? wp.rotY : Math.atan2(wp.x - this.dewiWalk.startX, wp.z - this.dewiWalk.startZ);

                    // Hitung jarak Euclidean untuk kecepatan jalan yang konstan & manusiawi (Natural Human Walking Speed: ~1.8 unit/sec)
                    const dx = wp.x - this.dewiWalk.startX;
                    const dz = wp.z - this.dewiWalk.startZ;
                    const dist = Math.sqrt(dx * dx + dz * dz);

                    if (dist < 0.05) {
                        // Jarak sangat dekat, langsung ke waypoint berikutnya
                        this.currentWaypointIdx++;
                        this.startNextWaypoint();
                        return;
                    }

                    this.dewiWalk.walkDuration = Math.max(0.4, dist / 1.75); // 1.75 units per second (kecepatan santai natural)
                    this.dewiWalk.progress = 0;

                    const angle = Math.atan2(dx, dz);
                    this.dewiGroup.rotation.y = angle;
                },

                // Update teks balon percakapan live (Elegan, tidak bentrok dengan nametag, & auto-hide setelah 8 detik agar tidak nyangkut)
                bubbleTimers: {},

                updateLiveBubble(agent, text) {
                    if (!this.bubbles[agent]) return;
                    const b = this.bubbles[agent];

                    // Clear previous timer
                    if (this.bubbleTimers[agent]) {
                        clearTimeout(this.bubbleTimers[agent]);
                        this.bubbleTimers[agent] = null;
                    }

                    if (!text) {
                        b.mesh.visible = false;
                        return;
                    }
                    const ctx = b.canvas.getContext('2d');
                    ctx.clearRect(0, 0, b.canvas.width, b.canvas.height);

                    // Gambar bubble chat elegan minimalis (Glassmorphism Dark Charcoal dengan accent hairline)
                    const w = b.canvas.width - 24;
                    const h = b.canvas.height - 40;

                    // Fill background lembut
                    ctx.fillStyle = 'rgba(15, 17, 23, 0.94)';
                    ctx.beginPath();
                    ctx.roundRect(12, 12, w, h, 20);
                    ctx.fill();

                    // Hairline border elegan
                    ctx.strokeStyle = agent === 'dewi' ? 'rgba(244, 114, 182, 0.85)' : (agent === 'singgih' ? 'rgba(45, 212, 191, 0.85)' : 'rgba(251, 191, 36, 0.85)');
                    ctx.lineWidth = 3.5;
                    ctx.stroke();

                    // Arrow pointer bawah minimalis
                    ctx.fillStyle = 'rgba(15, 17, 23, 0.94)';
                    ctx.beginPath();
                    ctx.moveTo(w / 2 - 12, 12 + h);
                    ctx.lineTo(w / 2, 12 + h + 16);
                    ctx.lineTo(w / 2 + 12, 12 + h);
                    ctx.closePath();
                    ctx.fill();

                    // Text typography bersih & tajam
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '600 24px "Inter", "Segoe UI", sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    const cleanText = text.length > 32 ? text.substring(0, 30) + '...' : text;
                    ctx.fillText(cleanText, b.canvas.width / 2, (h / 2) + 12);

                    b.texture.needsUpdate = true;
                    b.mesh.visible = true;

                    if (agent === 'dewi') {
                        this.setMood('dewi', '💬 Membalas Pesan...');
                        this.activeCustomerChat = text;
                    }

                    // Auto-hide setelah 8 detik agar balon tidak pernah nyangkut di atas kepala
                    this.bubbleTimers[agent] = setTimeout(() => {
                        b.mesh.visible = false;
                        if (agent === 'dewi' && this.currentCsStatus === 'working') {
                            this.setMood('dewi', '⚡ Fokus Standby Chat');
                        }
                    }, 8000);
                },

                // Membuat Label Nama 3D Melayang di atas kepala karakter (Elegan & Ada Mood/Ekspresi Realistis)
                nameTagCanvases: {},
                nameTagTextures: {},

                createNameTag(agentKey, name, role, badgeColor, initialMood = '✨ Aktif & Siap') {
                    const canvas = document.createElement('canvas');
                    canvas.width = 420;
                    canvas.height = 135;
                    const texture = new THREE.CanvasTexture(canvas);

                    this.nameTagCanvases[agentKey] = { canvas, name, role, badgeColor, mood: initialMood };
                    this.nameTagTextures[agentKey] = texture;
                    this.renderNameTag(agentKey);

                    const mat = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
                    const sprite = new THREE.Sprite(mat);
                    sprite.scale.set(1.45, 0.46, 1);
                    sprite.position.y = 1.45;
                    return sprite;
                },

                renderNameTag(agentKey) {
                    const item = this.nameTagCanvases[agentKey];
                    const texture = this.nameTagTextures[agentKey];
                    if (!item || !texture) return;

                    const ctx = item.canvas.getContext('2d');
                    ctx.clearRect(0, 0, item.canvas.width, item.canvas.height);

                    // Background pill badge elegan
                    ctx.fillStyle = 'rgba(18, 20, 26, 0.94)';
                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.20)';
                    ctx.lineWidth = 3;
                    ctx.beginPath();
                    ctx.roundRect(10, 10, item.canvas.width - 20, item.canvas.height - 20, 26);
                    ctx.fill();
                    ctx.stroke();

                    // Indicator dot
                    ctx.fillStyle = item.badgeColor;
                    ctx.beginPath();
                    ctx.arc(38, 52, 9, 0, Math.PI * 2);
                    ctx.fill();

                    // Text Name & Role
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 32px "Inter", sans-serif';
                    ctx.textAlign = 'left';
                    ctx.fillText(item.name, 62, 48);

                    ctx.fillStyle = item.badgeColor;
                    ctx.font = '600 20px "Inter", sans-serif';
                    ctx.fillText(item.role, 62, 78);

                    // Mood / Emotion Tag Pill (Bawah)
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.12)';
                    ctx.beginPath();
                    ctx.roundRect(60, 90, item.canvas.width - 85, 30, 12);
                    ctx.fill();

                    ctx.fillStyle = '#f1f5f9';
                    ctx.font = '500 17px "Inter", sans-serif';
                    ctx.fillText(item.mood, 72, 111);

                    texture.needsUpdate = true;
                },

                setMood(agentKey, moodText) {
                    if (this.nameTagCanvases[agentKey]) {
                        this.nameTagCanvases[agentKey].mood = moodText;
                        this.renderNameTag(agentKey);
                    }
                },

                // Membuat Speech Bubble Sprite (Ditempatkan di atas Name Tag agar tidak tumpang tindih)
                createSpeechBubble() {
                    const canvas = document.createElement('canvas');
                    canvas.width = 460;
                    canvas.height = 140;
                    const texture = new THREE.CanvasTexture(canvas);
                    const mat = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
                    const sprite = new THREE.Sprite(mat);
                    sprite.scale.set(1.9, 0.58, 1);
                    sprite.position.y = 2.15; // Berada tepat di atas nametag
                    sprite.visible = false;
                    return { mesh: sprite, canvas, texture };
                },

                // Karakter Dewi (Cewek Cantik: Rambut Panjang Cokelat-Caramel, Baju Pink Manis, Proporsional Lengkap)
                buildDewiCharacter() {
                    const char = new THREE.Group();

                    // Head & Neck
                    const headGeo = new THREE.BoxGeometry(0.36, 0.36, 0.34);
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfed7aa, roughness: 0.5 });
                    const head = new THREE.Mesh(headGeo, skinMat);
                    head.position.y = 0.88;
                    head.castShadow = true;
                    char.add(head);

                    const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.08, 0.12, 10), skinMat);
                    neck.position.y = 0.68;
                    char.add(neck);

                    // Rambut Panjang Cantik Dewi (Hazelnut Brown)
                    const hairMat = new THREE.MeshStandardMaterial({ color: 0x542c14, roughness: 0.75 });
                    const hairTop = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.18, 0.38), hairMat);
                    hairTop.position.set(0, 1.04, 0);
                    char.add(hairTop);

                    // Poni depan lucu
                    const bangs = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.12, 0.08), hairMat);
                    bangs.position.set(0, 0.98, 0.18);
                    char.add(bangs);

                    // Helai rambut panjang samping & belakang menjuntai
                    const hairBack = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.62, 0.14), hairMat);
                    hairBack.position.set(0, 0.76, -0.16);
                    char.add(hairBack);

                    const hairL = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.52, 0.22), hairMat);
                    hairL.position.set(-0.20, 0.80, 0.05);
                    char.add(hairL);

                    const hairR = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.52, 0.22), hairMat);
                    hairR.position.set(0.20, 0.80, 0.05);
                    char.add(hairR);

                    // Eyes (Lucu & Ramah)
                    const eyeMat = new THREE.MeshBasicMaterial({ color: 0x1e293b });
                    const eyeL = new THREE.Mesh(new THREE.BoxGeometry(0.045, 0.05, 0.02), eyeMat);
                    eyeL.position.set(-0.09, 0.89, 0.175);
                    const eyeR = eyeL.clone();
                    eyeR.position.x = 0.09;
                    char.add(eyeL);
                    char.add(eyeR);

                    // Pipi Blush Pink Manis
                    const blushMat = new THREE.MeshBasicMaterial({ color: 0xf472b6 });
                    const blushL = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.025, 0.01), blushMat);
                    blushL.position.set(-0.11, 0.82, 0.176);
                    const blushR = blushL.clone();
                    blushR.position.x = 0.11;
                    char.add(blushL);
                    char.add(blushR);

                    // Torso: Baju Pink Cantik Elegan
                    const shirtMat = new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.6 });
                    const torso = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.44, 0.26), shirtMat);
                    torso.position.y = 0.45;
                    torso.castShadow = true;
                    char.add(torso);

                    // Arms (Lengan + Telapak Tangan)
                    const armGeo = new THREE.BoxGeometry(0.09, 0.36, 0.11);
                    const armL = new THREE.Mesh(armGeo, skinMat);
                    armL.position.set(-0.25, 0.43, 0.04);
                    const armR = new THREE.Mesh(armGeo, skinMat);
                    armR.position.set(0.25, 0.43, 0.04);
                    char.add(armL);
                    char.add(armR);

                    // Legs / Kaki Proporsional + Sepatu Sneakers Putih
                    const pantsMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.8 });
                    const shoeMat = new THREE.MeshStandardMaterial({ color: 0xe4e9ef, roughness: 0.55 });

                    const legL = new THREE.Group();
                    const legMeshL = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.36, 0.14), pantsMat);
                    legMeshL.position.y = 0.18;
                    const shoeL = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.10, 0.19), shoeMat);
                    shoeL.position.set(0, 0.05, 0.03);
                    legL.add(legMeshL); legL.add(shoeL);
                    legL.position.set(-0.10, 0, 0);
                    char.add(legL);

                    const legR = new THREE.Group();
                    const legMeshR = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.36, 0.14), pantsMat);
                    legMeshR.position.y = 0.18;
                    const shoeR = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.10, 0.19), shoeMat);
                    shoeR.position.set(0, 0.05, 0.03);
                    legR.add(legMeshR); legR.add(shoeR);
                    legR.position.set(0.10, 0, 0);
                    char.add(legR);

                    // Name Tag "Dewi (CS Customer)"
                    const nameTag = this.createNameTag('dewi', 'Dewi', 'CS Customer Chat', '#f472b6', '☕ Istirahat Santai');
                    char.add(nameTag);

                    // Speech Bubble
                    this.bubbles.dewi = this.createSpeechBubble();
                    char.add(this.bubbles.dewi.mesh);

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.88, eyeL, eyeR };
                    return char;
                },

                // Karakter Pria (Singgih & Andera) - Proporsional Full Body
                buildMaleCharacter(name, role, shirtColor, hairColor, badgeColor, bubbleKey) {
                    const char = new THREE.Group();

                    // Head & Neck
                    const headGeo = new THREE.BoxGeometry(0.38, 0.38, 0.35);
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.65 });
                    const head = new THREE.Mesh(headGeo, skinMat);
                    head.position.y = 0.88;
                    head.castShadow = true;
                    char.add(head);

                    const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.09, 0.12, 10), skinMat);
                    neck.position.y = 0.68;
                    char.add(neck);

                    // Hair
                    const hairMat = new THREE.MeshStandardMaterial({ color: hairColor, roughness: 0.7 });
                    const hair = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.16, 0.38), hairMat);
                    hair.position.set(0, 1.04, -0.01);
                    char.add(hair);

                    // Eyes
                    const eyeMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });
                    const eyeL = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, 0.02), eyeMat);
                    eyeL.position.set(-0.09, 0.89, 0.18);
                    const eyeR = eyeL.clone();
                    eyeR.position.x = 0.09;
                    char.add(eyeL);
                    char.add(eyeR);

                    // Torso
                    const shirtMat = new THREE.MeshStandardMaterial({ color: shirtColor, roughness: 0.65 });
                    const torso = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.44, 0.28), shirtMat);
                    torso.position.y = 0.45;
                    torso.castShadow = true;
                    char.add(torso);

                    // Arms
                    const armGeo = new THREE.BoxGeometry(0.10, 0.36, 0.12);
                    const armL = new THREE.Mesh(armGeo, skinMat);
                    armL.position.set(-0.28, 0.43, 0.05);
                    const armR = new THREE.Mesh(armGeo, skinMat);
                    armR.position.set(0.28, 0.43, 0.05);
                    char.add(armL);
                    char.add(armR);

                    // Legs + Shoes
                    const pantsMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.8 });
                    const shoeMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.5 });

                    const legL = new THREE.Group();
                    const legMeshL = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.36, 0.14), pantsMat);
                    legMeshL.position.y = 0.18;
                    const shoeL = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.10, 0.20), shoeMat);
                    shoeL.position.set(0, 0.05, 0.03);
                    legL.add(legMeshL); legL.add(shoeL);
                    legL.position.set(-0.11, 0, 0);
                    char.add(legL);

                    const legR = new THREE.Group();
                    const legMeshR = new THREE.Mesh(new THREE.BoxGeometry(0.13, 0.36, 0.14), pantsMat);
                    legMeshR.position.y = 0.18;
                    const shoeR = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.10, 0.20), shoeMat);
                    shoeR.position.set(0, 0.05, 0.03);
                    legR.add(legMeshR); legR.add(shoeR);
                    legR.position.set(0.11, 0, 0);
                    char.add(legR);

                    // Name Tag
                    const nameTag = this.createNameTag(bubbleKey, name, role, badgeColor, '⚡ Standby & Fokus');
                    char.add(nameTag);

                    // Speech Bubble
                    this.bubbles[bubbleKey] = this.createSpeechBubble();
                    char.add(this.bubbles[bubbleKey].mesh);

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.88, eyeL, eyeR };
                    return char;
                },

                // Meja Kerja Gabungan Gen Z (Collaborative Island Workbench: Hadap-Hadapan & Bersatu)
                buildCollaborativeWorkbench(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Top Table Panjang Bersatu (Warm Natural Oak Teak Finish)
                    const topGeo = new THREE.BoxGeometry(5.4, 0.11, 2.7);
                    const topMat = new THREE.MeshStandardMaterial({ color: 0x5a3f2b, roughness: 0.45, metalness: 0.05 });
                    const top = new THREE.Mesh(topGeo, topMat);
                    top.position.y = 0.95;
                    top.castShadow = true;
                    top.receiveShadow = true;
                    group.add(top);

                    // Pembatas Akustik Minimalis di Tengah Meja (Felt Divider Desk Organizer)
                    const dividerGeo = new THREE.BoxGeometry(5.0, 0.28, 0.06);
                    const dividerMat = new THREE.MeshStandardMaterial({ color: 0x2b3833, roughness: 0.85 });
                    const divider = new THREE.Mesh(dividerGeo, dividerMat);
                    divider.position.set(0, 1.12, 0);
                    group.add(divider);

                    // Meja Legs (Matte Black Industrial Metal Frame)
                    const legMat = new THREE.MeshStandardMaterial({ color: 0x18181b, metalness: 0.85, roughness: 0.25 });
                    const legGeo = new THREE.BoxGeometry(0.08, 0.95, 0.08);
                    [[-2.55, -1.22], [2.55, -1.22], [-2.55, 1.22], [2.55, 1.22], [0, -1.22], [0, 1.22]].forEach(([lx, lz]) => {
                        const leg = new THREE.Mesh(legGeo, legMat);
                        leg.position.set(lx, 0.475, lz);
                        leg.castShadow = true;
                        group.add(leg);
                    });

                    // Kabel Tray & Cable Grommets
                    const tray = new THREE.Mesh(new THREE.BoxGeometry(4.8, 0.06, 0.22), legMat);
                    tray.position.set(0, 0.88, 0);
                    group.add(tray);

                    // Setup Masing-Masing Tempat Duduk: Monitor menghadap tepat ke wajah pengguna!
                    const deskSetups = [
                        { dx: -1.0, dz: 0.65, rot: 0, theme: 0xf472b6, label: 'Dewi' },     // Dewi di sisi Z positif menghadap ke -Z
                        { dx: 1.0, dz: 0.65, rot: 0, theme: 0x2dd4bf, label: 'Singgih' },   // Singgih di sisi Z positif menghadap ke -Z
                        { dx: 0.0, dz: -0.65, rot: Math.PI, theme: 0xfbbf24, label: 'Andera' } // Andera di sisi Z negatif menghadap ke +Z
                    ];

                    deskSetups.forEach(setup => {
                        const sub = new THREE.Group();
                        sub.position.set(setup.dx, 0.95, setup.dz);
                        sub.rotation.y = setup.rot;

                        // Desk Mat
                        const padMat = new THREE.MeshStandardMaterial({ color: 0x1c1917, roughness: 0.85 });
                        const pad = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.015, 0.68), padMat);
                        pad.position.set(0, 0.06, 0.1);
                        sub.add(pad);

                        // Monitor Frame Ultra-Wide
                        const screenFrameMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.2 });
                        const screenFrame = new THREE.Mesh(new THREE.BoxGeometry(1.05, 0.60, 0.04), screenFrameMat);
                        screenFrame.position.set(0, 0.48, -0.22);
                        screenFrame.castShadow = true;
                        sub.add(screenFrame);

                        // Monitor Display Screen: HARUS MENGHADAP KE PENGGUNA (Z positif lokal)
                        const displayGeo = new THREE.PlaneGeometry(1.00, 0.54);
                        const displayMat = this.setSelfLit(new THREE.MeshBasicMaterial({ map: this.screenTexture }), 2.0);
                        const display = new THREE.Mesh(displayGeo, displayMat);
                        display.position.set(0, 0.48, -0.198); // Di depan frame menghadap ke arah pengguna
                        display.rotation.y = 0; // TIDAK DIBALIK, menghadap lurus ke wajah orang yang duduk!
                        sub.add(display);
                        this.screenMeshes.push(display);

                        // Monitor Stand
                        const stand = new THREE.Mesh(new THREE.CylinderGeometry(0.022, 0.022, 0.38, 8), screenFrameMat);
                        stand.position.set(0, 0.20, -0.22);
                        sub.add(stand);

                        // Keyboard & Mouse
                        const kbMat = new THREE.MeshStandardMaterial({ color: 0x27272a, roughness: 0.7 });
                        const kb = new THREE.Mesh(new THREE.BoxGeometry(0.64, 0.025, 0.20), kbMat);
                        kb.position.set(0, 0.07, 0.1);
                        sub.add(kb);

                        const mouse = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.03, 0.12), kbMat);
                        mouse.position.set(0.46, 0.07, 0.1);
                        sub.add(mouse);

                        // Mug Kopi Estetik
                        const mugMat = new THREE.MeshStandardMaterial({ color: 0xfef08a, roughness: 0.3 });
                        const mug = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.045, 0.11, 12), mugMat);
                        mug.position.set(-0.54, 0.11, 0.12);
                        sub.add(mug);

                        group.add(sub);
                    });

                    // 3x Kursi Kantor Ergonomis Modern (Mundur pas agar tidak tembus meja)
                    // Sandaran kursi berada di belakang pengguna!
                    const makeChair = (cx, cz, crot) => {
                        const chair = new THREE.Group();
                        chair.position.set(cx, 0, cz);
                        chair.rotation.y = crot;

                        const chairMat = new THREE.MeshStandardMaterial({ color: 0x27272a, roughness: 0.75 });
                        const seat = new THREE.Mesh(new THREE.BoxGeometry(0.58, 0.08, 0.58), chairMat);
                        seat.position.set(0, 0.54, 0);
                        seat.castShadow = true;
                        chair.add(seat);

                        // Sandaran di posisi Z positif lokal (belakang punggung saat menghadap -Z)
                        const back = new THREE.Mesh(new THREE.BoxGeometry(0.54, 0.64, 0.07), chairMat);
                        back.position.set(0, 0.88, 0.26);
                        back.castShadow = true;
                        chair.add(back);

                        const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.032, 0.032, 0.52, 8), legMat);
                        stem.position.set(0, 0.26, 0);
                        chair.add(stem);

                        const base = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.26, 0.03, 5), legMat);
                        base.position.set(0, 0.03, 0);
                        chair.add(base);

                        return chair;
                    };

                    group.add(makeChair(-1.0, 1.45, 0));            // Kursi Dewi (Mundur 0.4m pas, sandaran di belakang)
                    group.add(makeChair(1.0, 1.45, 0));             // Kursi Singgih (Mundur 0.4m pas, sandaran di belakang)
                    group.add(makeChair(0.0, -1.45, Math.PI));      // Kursi Andera (Hadap seberang +Z, sandaran di belakang)

                    this.scene.add(group);
                },

                // Dapur / Pantry Modern Lengkap (Kulkas Double Door, Kitchen Island, Sink, Microwave)
                buildPantryKitchen(x, y, z) {
                    const pantry = new THREE.Group();
                    pantry.position.set(x, y, z);

                    // Kitchen Countertop Cabinet (Warm Olive Charcoal & Marble Top)
                    const counterGeo = new THREE.BoxGeometry(2.8, 0.95, 1.1);
                    const counterMat = new THREE.MeshStandardMaterial({ color: 0x262e2b, roughness: 0.7 });
                    const counter = new THREE.Mesh(counterGeo, counterMat);
                    counter.position.set(0, 0.475, 0);
                    counter.castShadow = true;
                    pantry.add(counter);

                    // Countertop Marble Putih Elegan
                    const marbleMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.32, metalness: 0.05 });
                    const topMarble = new THREE.Mesh(new THREE.BoxGeometry(2.9, 0.06, 1.15), marbleMat);
                    topMarble.position.set(0, 0.975, 0);
                    topMarble.castShadow = true;
                    pantry.add(topMarble);

                    // Kitchen Sink & Keran Air Minimalis
                    const sinkMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.8, roughness: 0.3 });
                    const sink = new THREE.Mesh(new THREE.BoxGeometry(0.65, 0.02, 0.48), sinkMat);
                    sink.position.set(0.65, 1.01, 0);
                    pantry.add(sink);

                    const faucet = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.28, 8), sinkMat);
                    faucet.position.set(0.65, 1.16, -0.18);
                    pantry.add(faucet);

                    // Microwave Oven Modern di Meja
                    const microMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.3 });
                    const micro = new THREE.Mesh(new THREE.BoxGeometry(0.58, 0.34, 0.42), microMat);
                    micro.position.set(-0.75, 1.18, 0);
                    micro.castShadow = true;
                    pantry.add(micro);

                    const microDoor = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.26, 0.02), new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.1 }));
                    microDoor.position.set(-0.78, 1.18, 0.215);
                    pantry.add(microDoor);

                    // Kulkas Dua Pintu (Double Door French Refrigerator Matte Silver)
                    const fridgeMat = new THREE.MeshStandardMaterial({ color: 0xc6cad1, metalness: 0.45, roughness: 0.42 });
                    const fridge = new THREE.Mesh(new THREE.BoxGeometry(1.2, 2.5, 1.1), fridgeMat);
                    fridge.position.set(2.2, 1.25, 0);
                    fridge.castShadow = true;
                    pantry.add(fridge);

                    // Gagang pintu kulkas
                    const handleMat = new THREE.MeshStandardMaterial({ color: 0x18181b, metalness: 0.9, roughness: 0.2 });
                    const handleL = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.8, 8), handleMat);
                    handleL.position.set(2.15, 1.35, 0.57);
                    pantry.add(handleL);

                    const handleR = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.8, 8), handleMat);
                    handleR.position.set(2.25, 1.35, 0.57);
                    pantry.add(handleR);

                    // Dispenser Galon Air di samping kulkas
                    const gallonBase = new THREE.Mesh(new THREE.BoxGeometry(0.45, 1.0, 0.45), new THREE.MeshStandardMaterial({ color: 0xdde3ea, roughness: 0.45 }));
                    gallonBase.position.set(-2.0, 0.5, 0);
                    pantry.add(gallonBase);

                    const gallon = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 0.50, 14), new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.75, roughness: 0.1 }));
                    gallon.position.set(-2.0, 1.25, 0);
                    pantry.add(gallon);

                    this.scene.add(pantry);
                },

                // Ruang Lounge Santai dengan Smart TV 65-inch Wall Mount
                buildLoungeWithTV(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Sofa Modern Cozy L-Shape
                    const sofaMat = new THREE.MeshStandardMaterial({ color: 0x3b5360, roughness: 0.85 }); // Warm Muted Slate Blue
                    const seatBase = new THREE.Mesh(new THREE.BoxGeometry(3.0, 0.45, 1.3), sofaMat);
                    seatBase.position.set(0, 0.35, 0);
                    seatBase.castShadow = true;
                    group.add(seatBase);

                    const seatBack = new THREE.Mesh(new THREE.BoxGeometry(3.0, 0.78, 0.28), sofaMat);
                    seatBack.position.set(0, 0.84, -0.48);
                    seatBack.castShadow = true;
                    group.add(seatBack);

                    const armL = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.62, 1.3), sofaMat);
                    armL.position.set(-1.5, 0.56, 0);
                    group.add(armL);

                    const armR = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.62, 1.3), sofaMat);
                    armR.position.set(1.5, 0.56, 0);
                    group.add(armR);

                    // Bantal Sofa Pastel Manis
                    const pillow1 = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.42, 0.16), new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.9 }));
                    pillow1.position.set(-0.95, 0.68, -0.32);
                    pillow1.rotation.z = 0.12;
                    group.add(pillow1);

                    const pillow2 = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.42, 0.16), new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.9 }));
                    pillow2.position.set(0.95, 0.68, -0.32);
                    pillow2.rotation.z = -0.12;
                    group.add(pillow2);

                    // Coffee Table Kayu Hangat
                    const tableMat = new THREE.MeshStandardMaterial({ color: 0x543622, roughness: 0.6 });
                    const table = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.34, 0.80), tableMat);
                    table.position.set(0, 0.20, 1.3);
                    table.castShadow = true;
                    group.add(table);

                    // Majalah & Gelas di Coffee Table
                    const mag = new THREE.Mesh(new THREE.BoxGeometry(0.32, 0.02, 0.42), new THREE.MeshStandardMaterial({ color: 0xe2e8f0 }));
                    mag.position.set(-0.35, 0.38, 1.3);
                    group.add(mag);

                    // Karpet Bulu Estetik
                    const rug = new THREE.Mesh(new THREE.BoxGeometry(3.5, 0.02, 2.5), new THREE.MeshStandardMaterial({ color: 0x272e2b, roughness: 0.95 }));
                    rug.position.set(0, 0.01, 0.65);
                    group.add(rug);

                    // Lemari TV Credenza Rendah di Dinding Depan Sofa
                    const credenzaMat = new THREE.MeshStandardMaterial({ color: 0x1f2421, roughness: 0.6 });
                    const credenza = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.48, 0.42), credenzaMat);
                    credenza.position.set(0, 0.24, 2.8);
                    credenza.castShadow = true;
                    group.add(credenza);

                    // Smart TV 65-Inch Wall Mount (Layar Hidup Menampilkan Dashboard RentSpace)
                    const tvFrame = new THREE.Mesh(new THREE.BoxGeometry(2.2, 1.25, 0.06), new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.2 }));
                    tvFrame.position.set(0, 1.85, 2.95);
                    tvFrame.castShadow = true;
                    group.add(tvFrame);

                    const tvScreen = new THREE.Mesh(new THREE.PlaneGeometry(2.12, 1.17), this.setSelfLit(new THREE.MeshBasicMaterial({ map: this.tvTexture }), 2.0));
                    tvScreen.position.set(0, 1.85, 2.915);
                    tvScreen.rotation.y = Math.PI;
                    group.add(tvScreen);

                    this.scene.add(group);
                },

                // Tulisan Neon 3D Huruf Timbul Minimalis Putih di Tembok (Tanpa Background Kotak Hitam!)
                buildMinimalNeonBox() {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1024;
                    canvas.height = 256;
                    const ctx = canvas.getContext('2d');

                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Tulisan "RENTSPACE" Huruf Timbul Putih Bersih dengan Glow Lembut (Background transparan murni)
                    ctx.textAlign = 'center';
                    ctx.letterSpacing = '8px';
                    // Glow lapisan pertama (blur lebar lembut)
                    ctx.shadowColor = 'rgba(255,255,255,0.55)';
                    ctx.shadowBlur = 42;
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '900 96px "Inter", "Outfit", sans-serif';
                    ctx.fillText('RENTSPACE', canvas.width / 2, 115);

                    // Glow lapisan kedua (core tajam)
                    ctx.shadowBlur = 12;
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText('RENTSPACE', canvas.width / 2, 115);
                    ctx.shadowBlur = 0;
                    ctx.shadowColor = 'transparent';

                    // Subtitle elegan
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
                    ctx.font = '600 24px "Inter", "Outfit", sans-serif';
                    ctx.letterSpacing = '6px';
                    ctx.fillText('HEADQUARTER & CUSTOMER SERVICE', canvas.width / 2, 168);

                    const texture = new THREE.CanvasTexture(canvas);

                    // Panel transparan menempel tepat di dinding (-4.58)
                    const faceGeo = new THREE.PlaneGeometry(6.4, 1.6);
                    const faceMat = this.setSelfLit(new THREE.MeshBasicMaterial({ map: texture, transparent: true }), 2.4);
                    const faceMesh = new THREE.Mesh(faceGeo, faceMat);
                    faceMesh.position.set(-2.2, 3.6, -4.58);
                    this.scene.add(faceMesh);

                    // Backlight putih lembut persis di belakang huruf
                    const wallBacklight = new THREE.PointLight(0xffffff, 4, 6, 2);
                    wallBacklight.position.set(-2.2, 3.6, -4.4);
                    this.scene.add(wallBacklight);
                },

                // Rak Lemari Arsip & Storage Ruangan
                buildBookshelf(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    const woodMat = new THREE.MeshStandardMaterial({ color: 0x3b332b, roughness: 0.65 });
                    const shelf = new THREE.Mesh(new THREE.BoxGeometry(1.6, 2.8, 0.55), woodMat);
                    shelf.position.y = 1.4;
                    shelf.castShadow = true;
                    group.add(shelf);

                    // Buku-buku rapi elegan
                    const colors = [0xe2e8f0, 0x94a3b8, 0x64748b, 0x475569, 0xd97706];
                    for (let row = 0; row < 3; row++) {
                        for (let col = 0; col < 6; col++) {
                            const bMat = new THREE.MeshStandardMaterial({ color: colors[(row + col) % colors.length] });
                            const book = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.40, 0.32), bMat);
                            book.position.set(-0.55 + col * 0.18, 0.65 + row * 0.78, 0.10);
                            group.add(book);
                        }
                    }

                    this.scene.add(group);
                },

                buildPlant(x, y, z) {
                    const plantGroup = new THREE.Group();
                    plantGroup.position.set(x, y, z);

                    const potMat = new THREE.MeshStandardMaterial({ color: 0xe5e7eb, roughness: 0.4 });
                    const pot = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.22, 0.55, 16), potMat);
                    pot.position.y = 0.275;
                    pot.castShadow = true;
                    plantGroup.add(pot);

                    const leafMat = new THREE.MeshStandardMaterial({ color: 0x1b4332, roughness: 0.6 });
                    for (let i = 0; i < 7; i++) {
                        const leaf = new THREE.Mesh(new THREE.SphereGeometry(0.28, 8, 8), leafMat);
                        leaf.scale.set(0.65, 1.8, 0.65);
                        leaf.position.set(Math.sin(i * 1.1) * 0.22, 0.65 + i * 0.11, Math.cos(i * 1.1) * 0.22);
                        leaf.rotation.x = Math.sin(i) * 0.3;
                        plantGroup.add(leaf);
                    }

                    this.scene.add(plantGroup);
                },

                // Server Rack Studio untuk AI Engine
                buildServerRack(x, y, z) {
                    const rackMat = new THREE.MeshStandardMaterial({ color: 0x111317, roughness: 0.4, metalness: 0.7 });
                    const rack = new THREE.Mesh(new THREE.BoxGeometry(1.2, 3.4, 0.95), rackMat);
                    rack.position.set(x, 1.7, z);
                    rack.castShadow = true;
                    this.scene.add(rack);

                    const ledMat1 = new THREE.MeshBasicMaterial({ color: 0x10b981 });
                    const ledMat2 = new THREE.MeshBasicMaterial({ color: 0x38bdf8 });
                    for (let i = 0; i < 6; i++) {
                        const led1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, 0.02), ledMat1);
                        led1.position.set(x - 0.35, 0.75 + (i * 0.48), z + 0.49);
                        this.scene.add(led1);

                        const led2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, 0.02), ledMat2);
                        led2.position.set(x + 0.35, 0.75 + (i * 0.48), z + 0.49);
                        this.scene.add(led2);
                    }
                },

                                // 8. Ruang Kamar Tidur AI Luas & Tertutup Full (Pintu di Dinding Dekat Meja Kantor, Kasur Mepet Tembok)
                bedroomDoorPivot: null,
                targetDoorAngle: 0, // 0 = Tertutup rapat, -Math.PI / 2 = Terbuka lebar

                
                // 7B. Ruang Kamar Mandi AI Modern (Sebelah Kamar Tidur di Pojok Belakang: x = -9.6, z = -3.2)
                buildBathroom(x, y, z) {
                    const bathGroup = new THREE.Group();
                    bathGroup.position.set(x, y, z);

                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x2b2724, roughness: 0.9 });
                    const tileMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.3 }); // Ubin abu-abu elegan modern

                    // Lantai Keramik Kamar Mandi (Pola ubin basah/glossy)
                    const floor = new THREE.Mesh(
                        new THREE.BoxGeometry(4.4, 0.03, 3.2),
                        new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2, metalness: 0.1 })
                    );
                    floor.position.set(0, 0.015, 0);
                    bathGroup.add(floor);

                    // Dinding Penyekat Depan Kamar Mandi (Memisahkan dengan Kamar Tidur): panjang 4.4m, tinggi 5.2m
                    const partitionWall = new THREE.Mesh(new THREE.BoxGeometry(4.4, 5.2, 0.35), wallMat);
                    partitionWall.position.set(0, 2.6, 1.6);
                    partitionWall.receiveShadow = true;
                    bathGroup.add(partitionWall);

                    // Pintu Kamar Mandi Kaca Buram Frosted Minimalis (di x = 1.0, z = 1.6)
                    const glassMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, transparent: true, opacity: 0.45, roughness: 0.2 });
                    const bathDoor = new THREE.Mesh(new THREE.BoxGeometry(1.0, 3.4, 0.08), glassMat);
                    bathDoor.position.set(1.0, 1.7, 1.6);
                    bathGroup.add(bathDoor);

                    // Kusen Pintu Kamar Mandi
                    const frameMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.4 });
                    const frameL = new THREE.Mesh(new THREE.BoxGeometry(0.08, 3.4, 0.12), frameMat);
                    frameL.position.set(0.5, 1.7, 1.6);
                    bathGroup.add(frameL);
                    const frameR = new THREE.Mesh(new THREE.BoxGeometry(0.08, 3.4, 0.12), frameMat);
                    frameR.position.set(1.5, 1.7, 1.6);
                    bathGroup.add(frameR);

                    // Dinding Samping Kanan (Pemisah dengan lorong kantor): panjang 3.2m, tebal 0.4m
                    const rightWall = new THREE.Mesh(new THREE.BoxGeometry(0.4, 5.2, 3.2), wallMat);
                    rightWall.position.set(2.2, 2.6, 0);
                    rightWall.receiveShadow = true;
                    bathGroup.add(rightWall);

                    // 1. Shower Box Kaca Mewah (di sudut kiri: x = -1.2, z = -0.6)
                    const showerGlass = new THREE.Mesh(new THREE.BoxGeometry(1.6, 3.2, 1.4), new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.2, roughness: 0.1 }));
                    showerGlass.position.set(-1.2, 1.6, -0.6);
                    bathGroup.add(showerGlass);

                    // Tiang & Kepala Shower Stainless Steel
                    const showerPole = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 2.4, 8), new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.9 }));
                    showerPole.position.set(-1.2, 2.0, -1.2);
                    bathGroup.add(showerPole);

                    const showerHead = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 0.03, 16), new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.95 }));
                    showerHead.position.set(-1.2, 3.1, -0.9);
                    showerHead.rotation.x = 0.2;
                    bathGroup.add(showerHead);

                    // 2. Wastafel Modern & Cermin Lampu LED
                    const vanity = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.85, 0.65), new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.5 }));
                    vanity.position.set(0.8, 0.425, -0.95);
                    vanity.castShadow = true;
                    bathGroup.add(vanity);

                    // Bak Cuci Piring Keramik Putih
                    const sink = new THREE.Mesh(new THREE.BoxGeometry(0.8, 0.15, 0.48), new THREE.MeshStandardMaterial({ color: 0xdde3ea, roughness: 0.34 }));
                    sink.position.set(0.8, 0.92, -0.95);
                    bathGroup.add(sink);

                    // Keran Air
                    const faucet = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.25, 8), new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.9 }));
                    faucet.position.set(0.8, 1.1, -1.1);
                    bathGroup.add(faucet);

                    // Cermin LED Bulat Cantik
                    const mirror = new THREE.Mesh(new THREE.CylinderGeometry(0.45, 0.45, 0.02, 32), new THREE.MeshStandardMaterial({ color: 0x93c5fd, metalness: 0.8, roughness: 0.1 }));
                    mirror.position.set(0.8, 2.1, -1.25);
                    mirror.rotation.x = Math.PI / 2;
                    bathGroup.add(mirror);

                    // Backlight LED Cermin
                    const mirrorLight = new THREE.PointLight(0xbae6fd, 2.5, 4, 2);
                    mirrorLight.position.set(0.8, 2.1, -1.15);
                    bathGroup.add(mirrorLight);

                    // 3. Toilet Duduk Smart Modern
                    const toiletBase = new THREE.Mesh(new THREE.BoxGeometry(0.48, 0.42, 0.68), new THREE.MeshStandardMaterial({ color: 0xdde3ea, roughness: 0.34 }));
                    toiletBase.position.set(0.8, 0.21, 0.5);
                    toiletBase.castShadow = true;
                    bathGroup.add(toiletBase);

                    const toiletTank = new THREE.Mesh(new THREE.BoxGeometry(0.48, 0.50, 0.26), new THREE.MeshStandardMaterial({ color: 0xdde3ea, roughness: 0.34 }));
                    toiletTank.position.set(0.8, 0.65, 0.15);
                    toiletTank.castShadow = true;
                    bathGroup.add(toiletTank);

                    // Lampu Plafon Kamar Mandi Hangat
                    const bathLight = new THREE.PointLight(0xe0f2fe, 5, 8, 2);
                    bathLight.position.set(0, 3.8, 0);
                    bathGroup.add(bathLight);

                    this.scene.add(bathGroup);
                },

                buildBedroom(x, y, z) {
                    const bedGroup = new THREE.Group();
                    bedGroup.position.set(x, y, z);

                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x2b2724, roughness: 0.9 });
                    
                    // A. Dinding Belakang Kamar (Full Tertutup Rapat Tinggi 5.2m, Panjang 4.4m, Tebal 0.4m)
                    const backWall = new THREE.Mesh(new THREE.BoxGeometry(4.4, 5.2, 0.4), wallMat);
                    backWall.position.set(0.0, 2.6, -3.3);
                    backWall.receiveShadow = true;
                    bedGroup.add(backWall);

                    // B. Dinding Depan Kamar (Full Tertutup Rapat Tinggi 5.2m, Panjang 4.4m, Tebal 0.4m - Tidak Bolong!)
                    const frontWall = new THREE.Mesh(new THREE.BoxGeometry(4.4, 5.2, 0.4), wallMat);
                    frontWall.position.set(0.0, 2.6, 3.3);
                    frontWall.receiveShadow = true;
                    bedGroup.add(frontWall);

                    // C. Dinding Penyekat Samping Kanan (MENGHADAP LANGSUNG KE MEJA KANTOR!)
                    // Total panjang = 6.6m (z dari -3.3 sampai +3.3).
                    // Posisi pintu dekat meja kantor: berada di area z = 0.8 s/d 2.2 (center Z = 1.5, lebar bukaan = 1.4m).
                    // Dinding kanan bagian belakang: panjang 4.1m (z dari -3.3 sampai 0.8), center Z = -1.25
                    const sideWallBack = new THREE.Mesh(new THREE.BoxGeometry(0.4, 5.2, 4.1), wallMat);
                    sideWallBack.position.set(2.2, 2.6, -1.25);
                    sideWallBack.receiveShadow = true;
                    bedGroup.add(sideWallBack);

                    // Dinding kanan bagian depan: panjang 1.1m (z dari 2.2 sampai 3.3), center Z = 2.75
                    const sideWallFront = new THREE.Mesh(new THREE.BoxGeometry(0.4, 5.2, 1.1), wallMat);
                    sideWallFront.position.set(2.2, 2.6, 2.75);
                    sideWallFront.receiveShadow = true;
                    bedGroup.add(sideWallFront);

                    // Ambang Atas Pintu (Header di dinding kanan): menutup dari y=3.5 sampai 5.2 (tinggi 1.7m, center Y=4.35, panjang 1.4m, center Z=1.5)
                    const sideWallHeader = new THREE.Mesh(new THREE.BoxGeometry(0.4, 1.7, 1.4), wallMat);
                    sideWallHeader.position.set(2.2, 4.35, 1.5);
                    sideWallHeader.receiveShadow = true;
                    bedGroup.add(sideWallHeader);

                    // D. Kusen Pintu Modern di Dinding Samping Kanan (x = 2.2, bukaan Z = 0.8 s/d 2.2)
                    const doorFrameMat = new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.35 });
                    
                    // Kusen atas
                    const frameTop = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.10, 1.44), doorFrameMat);
                    frameTop.position.set(2.2, 3.45, 1.5);
                    bedGroup.add(frameTop);

                    // Tiang kusen depan (z = 2.15)
                    const frameFront = new THREE.Mesh(new THREE.BoxGeometry(0.42, 3.5, 0.10), doorFrameMat);
                    frameFront.position.set(2.2, 1.75, 2.15);
                    bedGroup.add(frameFront);

                    // Tiang kusen belakang (z = 0.85)
                    const frameBack = new THREE.Mesh(new THREE.BoxGeometry(0.42, 3.5, 0.10), doorFrameMat);
                    frameBack.position.set(2.2, 1.75, 0.85);
                    bedGroup.add(frameBack);

                    // E. Daun Pintu Kayu Elegan dengan PIVOT ENGSEL (Engsel di tiang kusen depan z = 2.15)
                    // Berayun membuka ke dalam kamar menuju -X saat orang lewat!
                    const pivot = new THREE.Group();
                    pivot.position.set(2.2, 0, 2.15); // Engsel di tiang depan

                    const doorLeaf = new THREE.Mesh(
                        new THREE.BoxGeometry(0.08, 3.4, 1.22), 
                        new THREE.MeshStandardMaterial({ color: 0x4a3427, roughness: 0.55 })
                    );
                    doorLeaf.position.set(0, 1.70, -0.61); // Offset setengah lebar ke arah Z negatif
                    doorLeaf.castShadow = true;
                    pivot.add(doorLeaf);

                    // Gagang Pintu Stainless Steel Minimalis
                    const handle = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.24, 8), new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.9 }));
                    handle.position.set(0.06, 1.65, -1.10);
                    handle.rotation.z = Math.PI / 2;
                    pivot.add(handle);

                    bedGroup.add(pivot);
                    this.bedroomDoorPivot = pivot;

                    // F. Lantai Parket Kayu Hangat Khusus Kamar Tidur
                    const bedFloor = new THREE.Mesh(
                        new THREE.BoxGeometry(4.4, 0.025, 6.6), 
                        new THREE.MeshStandardMaterial({ color: 0x241d18, roughness: 0.65 })
                    );
                    bedFloor.position.set(0, 0.015, 0);
                    bedGroup.add(bedFloor);

                    // G. Karpet Mewah Kamar Tidur (Di bawah dan samping kasur)
                    const rug = new THREE.Mesh(
                        new THREE.BoxGeometry(3.0, 0.03, 3.4), 
                        new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.95 })
                    );
                    rug.position.set(-0.7, 0.03, -0.1);
                    bedGroup.add(rug);

                    // H. Ranjang Kasur King Size MEPET TEMBOK KIRI & BELAKANG (Tidak di Tengah Kamar!)
                    // Mepet dinding kiri (x = -0.90) dan dinding belakang (z = -1.60)
                    const frameMat = new THREE.MeshStandardMaterial({ color: 0x3d271d, roughness: 0.7 });
                    const bedFrame = new THREE.Mesh(new THREE.BoxGeometry(2.3, 0.35, 2.8), frameMat);
                    bedFrame.position.set(-0.90, 0.18, -0.10);
                    bedFrame.castShadow = true;
                    bedGroup.add(bedFrame);

                    // Sandaran Kepala Ranjang (Headboard Kayu Mewah menempel di dinding belakang)
                    const headboard = new THREE.Mesh(new THREE.BoxGeometry(2.35, 1.2, 0.18), frameMat);
                    headboard.position.set(-0.90, 0.85, -1.55);
                    headboard.castShadow = true;
                    bedGroup.add(headboard);

                    // Kasur Springbed Empuk Putih Mepet Tembok
                    const mattressMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.62 });
                    const mattress = new THREE.Mesh(new THREE.BoxGeometry(2.1, 0.28, 2.6), mattressMat);
                    mattress.position.set(-0.90, 0.45, -0.10);
                    mattress.castShadow = true;
                    bedGroup.add(mattress);

                    // Selimut Hangat Pink Pastel
                    const blanketMat = new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.8 });
                    const blanket = new THREE.Mesh(new THREE.BoxGeometry(2.12, 0.12, 1.7), blanketMat);
                    blanket.position.set(-0.90, 0.52, 0.35);
                    blanket.castShadow = true;
                    bedGroup.add(blanket);

                    // 2x Bantal Empuk di Kepala Ranjang
                    const pillowMat = new THREE.MeshStandardMaterial({ color: 0xe6ebf1, roughness: 0.72 });
                    const pillow1 = new THREE.Mesh(new THREE.BoxGeometry(0.68, 0.16, 0.48), pillowMat);
                    pillow1.position.set(-1.40, 0.62, -1.05);
                    bedGroup.add(pillow1);

                    const pillow2 = new THREE.Mesh(new THREE.BoxGeometry(0.68, 0.16, 0.48), pillowMat);
                    pillow2.position.set(-0.40, 0.62, -1.05);
                    bedGroup.add(pillow2);

                    // Meja Nakas & Lampu Tidur Warm (Di sisi terbuka ranjang x = 0.65)
                    const nakas = new THREE.Mesh(new THREE.BoxGeometry(0.50, 0.55, 0.50), frameMat);
                    nakas.position.set(0.65, 0.275, -1.15);
                    nakas.castShadow = true;
                    bedGroup.add(nakas);

                    const lampBase = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.12, 0.26, 12), new THREE.MeshStandardMaterial({ color: 0xd4d4d8 }));
                    lampBase.position.set(0.65, 0.68, -1.15);
                    bedGroup.add(lampBase);

                    const lampShade = new THREE.Mesh(new THREE.CylinderGeometry(0.14, 0.20, 0.24, 14), new THREE.MeshStandardMaterial({ color: 0xfef08a, emissive: 0xfef08a, emissiveIntensity: 0.4 }));
                    lampShade.position.set(0.65, 0.93, -1.15);
                    bedGroup.add(lampShade);

                    const nightLight = new THREE.PointLight(0xffe8ba, 4, 8, 2);
                    nightLight.position.set(0.65, 1.1, -1.15);
                    bedGroup.add(nightLight);

                    this.scene.add(bedGroup);
                },

                // 9. Frame Galeri Foto & Poster di Dinding
                buildGalleryWall() {
                    const group = new THREE.Group();

                    const createFrame = (fx, fy, fz, w, h, title, sub, color) => {
                        const canvas = document.createElement('canvas');
                        canvas.width = 384; canvas.height = 480;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = color; ctx.fillRect(0, 0, 384, 480);
                        ctx.fillStyle = 'rgba(0,0,0,0.25)'; ctx.fillRect(20, 20, 344, 440);
                        ctx.fillStyle = '#ffffff'; ctx.font = 'bold 32px sans-serif'; ctx.textAlign = 'center';
                        ctx.fillText(title, 192, 220);
                        ctx.font = '20px sans-serif'; ctx.fillStyle = 'rgba(255,255,255,0.85)';
                        ctx.fillText(sub, 192, 265);
                        const tex = new THREE.CanvasTexture(canvas);

                        const frameMesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.05), new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.4 }));
                        frameMesh.position.set(fx, fy, fz);
                        group.add(frameMesh);

                        const pic = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.1, h - 0.1), new THREE.MeshBasicMaterial({ map: tex }));
                        pic.position.set(fx, fy, fz + 0.028);
                        group.add(pic);
                    };

                    // Poster Katalog iPhone RentSpace di dinding kantor (Frame foto hijau sudah dihapus!)
                    createFrame(-6.0, 3.4, -4.58, 1.4, 1.8, 'iPHONE 16 PRO', 'READY TO RENT', '#1e293b');
                    createFrame(8.2, 3.4, -4.58, 1.3, 1.7, 'COFFEE & CODE', 'AI LAB PURWOKERTO', '#854d0e');

                    this.scene.add(group);
                },

                // 10. AC Modern Dinding (Air Conditioner Inverter dengan Lampu Indikator Hijau)
                buildAirConditioner(x, y, z) {
                    const acGroup = new THREE.Group();
                    acGroup.position.set(x, y, z);

                    const acMat = new THREE.MeshStandardMaterial({ color: 0xdbe1e8, roughness: 0.4 });
                    const body = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.52, 0.35), acMat);
                    body.castShadow = true;
                    acGroup.add(body);

                    // Flap kisi AC bawah
                    const flap = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.06, 0.08), new THREE.MeshStandardMaterial({ color: 0xe2e8f0 }));
                    flap.position.set(0, -0.22, 0.12);
                    flap.rotation.x = 0.35; // Terbuka menghembus udara
                    acGroup.add(flap);

                    // LED Display Hijau Suhu Dingin (18°C)
                    const led = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.08, 0.02), new THREE.MeshBasicMaterial({ color: 0x10b981 }));
                    led.position.set(0.65, 0.05, 0.18);
                    acGroup.add(led);

                    this.scene.add(acGroup);
                },

                // 11. Jam Dinding Realistis (Berjalan Real-Time Sesuai Waktu Asli)
                buildRealClock(x, y, z) {
                    this.clockCanvas = document.createElement('canvas');
                    this.clockCanvas.width = 256;
                    this.clockCanvas.height = 256;
                    this.clockCtx = this.clockCanvas.getContext('2d');
                    this.clockTexture = new THREE.CanvasTexture(this.clockCanvas);

                    const clockFrame = new THREE.Mesh(new THREE.CylinderGeometry(0.48, 0.48, 0.06, 32), new THREE.MeshStandardMaterial({ color: 0x18181b, metalness: 0.8, roughness: 0.2 }));
                    clockFrame.position.set(x, y, z);
                    clockFrame.rotation.x = Math.PI / 2;
                    this.scene.add(clockFrame);

                    const clockFace = new THREE.Mesh(new THREE.CircleGeometry(0.44, 32), this.setSelfLit(new THREE.MeshBasicMaterial({ map: this.clockTexture }), 1.6));
                    clockFace.position.set(x, y, z + 0.035);
                    this.scene.add(clockFace);
                },

                updateClockCanvas() {
                    if (!this.clockCtx || !this.clockTexture) return;
                    const ctx = this.clockCtx;
                    ctx.clearRect(0, 0, 256, 256);

                    // Dial Putih Bersih
                    ctx.fillStyle = '#f8fafc';
                    ctx.beginPath(); ctx.arc(128, 128, 120, 0, Math.PI * 2); ctx.fill();
                    ctx.strokeStyle = '#0f172a'; ctx.lineWidth = 5; ctx.stroke();

                    // Titik jam
                    for (let i = 0; i < 12; i++) {
                        const ang = (i * Math.PI) / 6;
                        const rx = 128 + Math.sin(ang) * 95;
                        const ry = 128 - Math.cos(ang) * 95;
                        ctx.fillStyle = '#334155';
                        ctx.beginPath(); ctx.arc(rx, ry, i % 3 === 0 ? 5 : 2.5, 0, Math.PI * 2); ctx.fill();
                    }

                    const now = new Date();
                    const sec = now.getSeconds() + now.getMilliseconds() / 1000;
                    const min = now.getMinutes() + sec / 60;
                    const hr = (now.getHours() % 12) + min / 60;

                    // Jarum Jam
                    ctx.strokeStyle = '#0f172a'; ctx.lineWidth = 6; ctx.lineCap = 'round';
                    ctx.beginPath(); ctx.moveTo(128, 128);
                    ctx.lineTo(128 + Math.sin(hr * Math.PI / 6) * 55, 128 - Math.cos(hr * Math.PI / 6) * 55);
                    ctx.stroke();

                    // Jarum Menit
                    ctx.strokeStyle = '#334155'; ctx.lineWidth = 4;
                    ctx.beginPath(); ctx.moveTo(128, 128);
                    ctx.lineTo(128 + Math.sin(min * Math.PI / 30) * 82, 128 - Math.cos(min * Math.PI / 30) * 82);
                    ctx.stroke();

                    // Jarum Detik (Merah Presisi)
                    ctx.strokeStyle = '#ef4444'; ctx.lineWidth = 2;
                    ctx.beginPath(); ctx.moveTo(128, 128);
                    ctx.lineTo(128 + Math.sin(sec * Math.PI / 30) * 95, 128 - Math.cos(sec * Math.PI / 30) * 95);
                    ctx.stroke();

                    // Center cap
                    ctx.fillStyle = '#ef4444';
                    ctx.beginPath(); ctx.arc(128, 128, 5, 0, Math.PI * 2); ctx.fill();

                    this.clockTexture.needsUpdate = true;
                },

                // 12. Jendela Dunia Luar (Outdoor World Window & Skyline Gedung + Langit Pagi/Malam)
                buildOutdoorWindow() {
                    const winGroup = new THREE.Group();

                    // Frame jendela kaca besar di dinding kanan
                    const frameMat = new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.3, metalness: 0.8 });
                    const winFrame = new THREE.Mesh(new THREE.BoxGeometry(0.3, 4.4, 8.5), frameMat);
                    winFrame.position.set(11.8, 2.5, 1.2);
                    winGroup.add(winFrame);

                    // Kaca Jendela Transparan Bening
                    const glassMat = new THREE.MeshStandardMaterial({ color: 0xdbeafe, transparent: true, opacity: 0.28, roughness: 0.05, metalness: 0.9 });
                    const glass = new THREE.Mesh(new THREE.BoxGeometry(0.08, 4.2, 8.3), glassMat);
                    glass.position.set(11.8, 2.5, 1.2);
                    winGroup.add(glass);

                    // Pemandangan Luar Jendela: Backdrop Langit & Siluet Gedung Kota Modern
                    const skyCanvas = document.createElement('canvas');
                    skyCanvas.width = 512; skyCanvas.height = 512;
                    this.skyCtx = skyCanvas.getContext('2d');
                    this.skyTexture = new THREE.CanvasTexture(skyCanvas);
                    this.updateSkyCanvas();

                    const skyBackdrop = new THREE.Mesh(new THREE.PlaneGeometry(16, 10), new THREE.MeshBasicMaterial({ map: this.skyTexture }));
                    skyBackdrop.position.set(15.2, 4.0, 1.2);
                    skyBackdrop.rotation.y = -Math.PI / 2;
                    winGroup.add(skyBackdrop);

                    this.scene.add(winGroup);
                },

                updateSkyCanvas() {
                    if (!this.skyCtx || !this.skyTexture) return;
                    const ctx = this.skyCtx;
                    const hour = new Date().getHours();

                    // Gradasi langit berdasarkan jam nyata
                    const grad = ctx.createLinearGradient(0, 0, 0, 512);
                    if (hour >= 6 && hour < 15) {
                        // Siang Segar
                        grad.addColorStop(0, '#38bdf8'); grad.addColorStop(0.7, '#bae6fd'); grad.addColorStop(1, '#f0f9ff');
                    } else if (hour >= 15 && hour < 18) {
                        // Sore Golden Hour
                        grad.addColorStop(0, '#f97316'); grad.addColorStop(0.6, '#fbbf24'); grad.addColorStop(1, '#fef08a');
                    } else {
                        // Malam Gemerlap
                        grad.addColorStop(0, '#020617'); grad.addColorStop(0.7, '#0f172a'); grad.addColorStop(1, '#1e293b');
                    }
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, 0, 512, 512);

                    // Siluet Gedung-Gedung Kota
                    const bColor = (hour >= 6 && hour < 18) ? '#64748b' : '#090d16';
                    ctx.fillStyle = bColor;
                    const buildings = [
                        [20, 240, 70, 272], [100, 180, 85, 332], [200, 140, 95, 372],
                        [310, 210, 75, 302], [400, 160, 90, 352]
                    ];
                    buildings.forEach(([bx, by, bw, bh]) => {
                        ctx.fillRect(bx, by, bw, bh);
                        // Lampu jendela gedung
                        ctx.fillStyle = (hour >= 18 || hour < 6) ? '#fef08a' : '#94a3b8';
                        for (let r = by + 20; r < 490; r += 26) {
                            for (let c = bx + 12; c < bx + bw - 12; c += 22) {
                                ctx.fillRect(c, r, 9, 13);
                            }
                        }
                        ctx.fillStyle = bColor;
                    });

                    this.skyTexture.needsUpdate = true;
                },

                // 13. Pintu Utama Kantor
                buildOfficeDoors() {
                    const doorGroup = new THREE.Group();
                    const frameMat = new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.4 });
                    const frame = new THREE.Mesh(new THREE.BoxGeometry(2.4, 4.4, 0.45), frameMat);
                    frame.position.set(-11.6, 2.2, 8.5);
                    doorGroup.add(frame);

                    // Daun Pintu Kaca Modern dengan Gagang Stainless
                    const doorLeaf = new THREE.Mesh(new THREE.BoxGeometry(2.0, 3.8, 0.08), new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2, metalness: 0.6 }));
                    doorLeaf.position.set(-11.6, 2.0, 8.5);
                    doorGroup.add(doorLeaf);

                    this.scene.add(doorGroup);
                },

                // 14. NPC Tambahan: "Pak Budi" (Cleaning Service / Office Assistant yang keliling rapi)
                budiGroup: null,
                budiWalk: { progress: 0, speed: 0.28 },

                buildCleaningService() {
                    const char = new THREE.Group();
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.6 });
                    const head = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.36, 0.34), skinMat);
                    head.position.y = 0.88;
                    char.add(head);

                    // Topi Cleaning Service Biru Tua
                    const cap = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.12, 0.44), new THREE.MeshStandardMaterial({ color: 0x1e3a8a }));
                    cap.position.set(0, 1.05, 0.04);
                    char.add(cap);

                    // Seragam Kemeja Kerja Biru Muda
                    const shirt = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.44, 0.26), new THREE.MeshStandardMaterial({ color: 0x38bdf8 }));
                    shirt.position.y = 0.45;
                    char.add(shirt);

                    // Tangan Kiri
                    const armGeo = new THREE.BoxGeometry(0.09, 0.36, 0.11);
                    const armL = new THREE.Mesh(armGeo, skinMat); 
                    armL.position.set(-0.26, 0.43, 0.08); 
                    char.add(armL);

                    // Tangan Kanan memegang Gagang Pel (Tongkat pel masuk sebagai child armR!)
                    const armR = new THREE.Group();
                    const armRMesh = new THREE.Mesh(armGeo, skinMat);
                    armRMesh.position.y = -0.18;
                    armR.add(armRMesh);
                    armR.position.set(0.26, 0.61, 0.08);

                    // Sapu Ijuk Rumah Asli (Tongkat Kayu + Kepala Ijuk Bulu Mekar yang JELAS KELIHATAN!)
                    const broomPole = new THREE.Mesh(
                        new THREE.CylinderGeometry(0.022, 0.022, 1.35, 8), 
                        new THREE.MeshStandardMaterial({ color: 0x92400e, roughness: 0.6 }) // Kayu gagang sapu
                    );
                    broomPole.position.set(0.05, -0.25, 0.20);
                    broomPole.rotation.x = 0.45;
                    armR.add(broomPole);

                    // Kepala Klem Penjepit Sapu (Merah Minimalis)
                    const broomCap = new THREE.Mesh(
                        new THREE.BoxGeometry(0.24, 0.08, 0.10), 
                        new THREE.MeshStandardMaterial({ color: 0xb91c1c, roughness: 0.4 })
                    );
                    broomCap.position.set(0.05, -0.75, 0.45);
                    armR.add(broomCap);

                    // Bulu Ijuk Sapu Jerami Mekar Tebal (Cokelat/Kuning Serat Alami - Terlihat Jelas!)
                    const broomBristles = new THREE.Mesh(
                        new THREE.CylinderGeometry(0.14, 0.32, 0.32, 8), 
                        new THREE.MeshStandardMaterial({ color: 0xd97706, roughness: 0.95 })
                    );
                    broomBristles.scale.set(1.0, 1.0, 0.45); // Gepeng pipih seperti sapu ijuk
                    broomBristles.position.set(0.05, -0.92, 0.52);
                    broomBristles.rotation.x = 0.20;
                    armR.add(broomBristles);
                    char.add(armR);

                    // Celana & Sepatu
                    const legL = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.36, 0.14), new THREE.MeshStandardMaterial({ color: 0x1e293b }));
                    legL.position.set(-0.10, 0.18, 0); char.add(legL);
                    const legR = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.36, 0.14), new THREE.MeshStandardMaterial({ color: 0x1e293b }));
                    legR.position.set(0.10, 0.18, 0); char.add(legR);

                    const nameTag = this.createNameTag('budi', 'Pak Budi', 'Office Assistant', '#38bdf8', '🧹 Bersih-bersih Kantor');
                    char.add(nameTag);

                    char.userData = { armL, armR, legL, legR, head };
                    this.budiGroup = char;
                    this.scene.add(char);
                },

                buildRoom() {
                    // 1. Lantai Parquet Kayu Walnut Hangat Mewah (procedural color + normal + roughness + clearcoat)
                    const floorMaps = this.makeFloorMaps();
                    const floorGeo = new THREE.PlaneGeometry(24, 15);
                    const floorMat = new THREE.MeshPhysicalMaterial({
                        map: this.createWoodFloorTexture(),
                        normalMap: floorMaps.normalMap,
                        normalScale: new THREE.Vector2(0.85, 0.85),
                        roughnessMap: floorMaps.roughnessMap,
                        roughness: 1.0,
                        metalness: 0.0,
                        // clearcoat = lapisan lacquer tipis → pantulan panjang yang lembut, ciri kayu jadi
                        clearcoat: 0.42,
                        clearcoatRoughness: 0.28,
                        envMapIntensity: 1.15,
                    });
                    const floor = new THREE.Mesh(floorGeo, floorMat);
                    floor.rotation.x = -Math.PI / 2;
                    floor.receiveShadow = true;
                    this.scene.add(floor);

                    // Baseboard / Lantai Sock Kayu di sepanjang dinding (detail arsitektural)
                    const baseMat = new THREE.MeshStandardMaterial({ color: 0x3a2b1e, roughness: 0.6 });
                    const baseProf = new THREE.BoxGeometry(0.08, 0.30, 0.08);
                    const addBase = (bx, by, bz, sx, sz) => {
                        const bb = new THREE.Mesh(baseProf, baseMat);
                        bb.scale.set(sx, 1, sz);
                        bb.position.set(bx, by, bz);
                        bb.receiveShadow = true;
                        this.scene.add(bb);
                    };
                    addBase(0, 0.15, -4.66, 24, 1);      // Baseboard dinding belakang
                    addBase(-11.66, 0.15, 2.5, 1, 15);   // Baseboard dinding kiri
                    addBase(11.66, 0.15, 2.5, 1, 15);    // Baseboard dinding kanan

                    // Grid garis lantai subtle (diganti accent plinth agar tidak noise)
                    const grid = new THREE.GridHelper(24, 24, 0x47382d, 0x382c23);
                    grid.position.y = 0.012;
                    grid.material.opacity = 0.25;
                    grid.material.transparent = true;
                    this.scene.add(grid);

                    // 2. Dinding Utama & Partisi Multi-Ruangan (Plaster gelap + mikro normal/roughness)
                    const wallMaps = this.makeWallMaps();
                    const wallMat = new THREE.MeshStandardMaterial({
                        color: 0x332e2a,
                        roughness: 1.0,
                        roughnessMap: wallMaps.roughnessMap,
                        metalness: 0.0,
                        normalMap: wallMaps.normalMap,
                        normalScale: new THREE.Vector2(0.35, 0.35),
                        envMapIntensity: 0.7,
                    });

                    // Dinding Belakang (Full Wall)
                    const backWall = new THREE.Mesh(new THREE.BoxGeometry(24, 5.2, 0.4), wallMat);
                    backWall.position.set(0, 2.6, -4.8);
                    backWall.receiveShadow = true;
                    this.scene.add(backWall);

                    // Dinding Samping Kiri (Studio Kantor & Kamar Tidur)
                    const leftWall = new THREE.Mesh(new THREE.BoxGeometry(0.4, 5.2, 15), wallMat);
                    leftWall.position.set(-11.8, 2.6, 2.5);
                    leftWall.receiveShadow = true;
                    this.scene.add(leftWall);

                    // Dinding Samping Kanan Penuh (Lounge & Dapur)
                    const rightWall = new THREE.Mesh(new THREE.BoxGeometry(0.4, 5.2, 15), wallMat);
                    rightWall.position.set(11.8, 2.6, 2.5);
                    rightWall.receiveShadow = true;
                    this.scene.add(rightWall);

                    // Dinding Partisi Pembatas: Memisahkan Kantor Utama dengan Area Pantry/Dapur & Lounge
                    const glassPartitionMat = new THREE.MeshStandardMaterial({ color: 0x1f2937, transparent: true, opacity: 0.35, roughness: 0.1 });
                    const partition = new THREE.Mesh(new THREE.BoxGeometry(0.12, 3.8, 6.2), glassPartitionMat);
                    partition.position.set(2.0, 1.9, -1.7);
                    this.scene.add(partition);

                    // Frame kayu partisi estetik
                    const partitionFrameMat = new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.5 });
                    const pFrameTop = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.12, 6.2), partitionFrameMat);
                    pFrameTop.position.set(2.0, 3.8, -1.7);
                    this.scene.add(pFrameTop);

                    // 3. Neon Box Minimalis Putih: "RENTSPACE" Tanpa Emoticon
                    this.buildMinimalNeonBox();

                    // 4. Meja Kerja Bersama Gen Z (Hadap-Hadapan & Menyatu)
                    this.buildCollaborativeWorkbench(-2.2, 0, 0.1);

                    // 5. Ruang Dapur / Pantry Modern (Kulkas, Sink, Microwave)
                    this.buildPantryKitchen(5.2, 0, -3.8);

                    // 6. Ruang Lounge Santai & Smart TV 65-Inch
                    this.buildLoungeWithTV(5.0, 0, 1.4);

                    // 7. Ruang Kamar Tidur AI (Rest Bedroom saat Token Habis / Low Energy - Geser Rapi ke Kiri)
                    this.buildBedroom(-9.6, 0, 1.4);
                    this.buildBathroom(-9.6, 0, -3.2);

                    // 8. Dekorasi Dinding: Frame Galeri Poster, Jam Nyata, AC Dinding & Pintu
                    this.buildGalleryWall();
                    this.buildRealClock(-2.2, 4.8, -4.56);
                    this.buildAirConditioner(2.5, 4.4, -4.56);
                    // Window luar dihapus agar dinding rapi bersih
                    this.buildOfficeDoors();

                    // 9. Perabot Pendukung (Server Rack, Lemari Arsip, Tanaman Hias)
                    // Ditempatkan rapi di dinding kantor utama tanpa menembus dinding pembatas kamar (x=-7.4)
                    this.buildServerRack(-6.0, 0, -4.2);
                    this.buildBookshelf(-4.6, 0, -4.2);
                    this.buildPlant(-7.0, 0, -4.1);
                    this.buildPlant(1.6, 0, 2.6);
                    this.buildPlant(9.5, 0, -4.1);

                    // 10. NPC Pak Budi (Cleaning Service / Office Assistant)
                    this.buildCleaningService();

                    // 11. Spawn Karakter 3D (Dewi, Singgih, Andera)
                    // DEWI (CS Customer: Baju Pink Cantik, Rambut Panjang Cokelat Caramel)
                    this.dewiGroup = this.buildDewiCharacter();
                    this.scene.add(this.dewiGroup);

                    // SINGGIH (Core Dispatcher)
                    this.singgihGroup = this.buildMaleCharacter('Singgih', 'Core Dispatcher', 0x0d9488, 0x1e1b4b, '#2dd4bf', 'singgih');
                    const initSinggihSpot = (this.currentSinggihStatus === 'working') ? this.spots.singgihDesk : this.spots.singgihLounge;
                    this.singgihGroup.position.set(initSinggihSpot.x, initSinggihSpot.y, initSinggihSpot.z);
                    this.singgihGroup.rotation.y = initSinggihSpot.rotY;
                    this.scene.add(this.singgihGroup);

                    // ANDERA (Report & Finance)
                    this.anderaGroup = this.buildMaleCharacter('Andera', 'Report & Finance', 0xd97706, 0x451a03, '#fbbf24', 'andera');
                    const initAnderaSpot = (this.currentAnderaStatus === 'working') ? this.spots.anderaDesk : this.spots.anderaLounge;
                    this.anderaGroup.position.set(initAnderaSpot.x, initAnderaSpot.y, initAnderaSpot.z);
                    this.anderaGroup.rotation.y = initAnderaSpot.rotY;
                    this.scene.add(this.anderaGroup);

                    // Blob Shadow lembut di bawah setiap karakter (menambah realisme kontak dengan lantai)
                    this.dewiBlob = this.createContactShadow(0.9);
                    this.singgihBlob = this.createContactShadow(0.95);
                    this.anderaBlob = this.createContactShadow(0.95);
                    if (this.budiGroup) this.budiBlob = this.createContactShadow(0.8);

                    // Blob mengikuti posisi karakter (hanya X & Z / Y tetap di lantai)
                    this.dewiBlob.position.set(this.dewiGroup.position.x, 0.012, this.dewiGroup.position.z);
                    this.singgihBlob.position.set(this.singgihGroup.position.x, 0.012, this.singgihGroup.position.z);
                    this.anderaBlob.position.set(this.anderaGroup.position.x, 0.012, this.anderaGroup.position.z);
                    if (this.budiBlob) this.budiBlob.position.set(this.budiGroup.position.x, 0.012, this.budiGroup.position.z);
                },

                animate() {
                    this.animationFrameId = requestAnimationFrame(() => this.animate());

                    // Sampling FPS untuk adaptive quality (1 sampel per frame,aba-aba 45 sampel = ~1 detik)
                    if (!this.perf.lastSampleAt) this.perf.lastSampleAt = performance.now();
                    const nowMs = performance.now();
                    if (nowMs - this.perf.lastSampleAt >= 1000) {
                        const dt = nowMs - this.perf.lastSampleAt;
                        this.perf.lastSampleAt = nowMs;
                        this.trackPerformance(this.quality.frames ? Math.round((this.quality.frames * 1000) / dt) : 60);
                        this.quality.frames = 0;
                    }
                    this.quality.frames = (this.quality.frames || 0) + 1;

                    if (!this.renderer || !this.scene || !this.camera) return;

                    // Reconcile: koreksi karakter yang "nyangkut" karena WebSocket
                    // terputus / event telat. Dijalankan max 1x per 2 detik.
                    if (this.dewiGroup && nowMs - (this.lastActivityAt || 0) > 2000) {
                        this.reconcileTick(nowMs);
                    }

                    const time = this.clock ? this.clock.getElapsedTime() : 0;
                    // Delta waktu nyata dari clock (frame-rate independent, mulus di 60/120Hz)
                    let delta = 0.016;
                    if (this.clock) {
                        delta = Math.min(this.clock.getDelta(), 0.05); // clamp anti loncatan saat tab background
                    }

                    // Update Jam Dinding Real-Time
                    this.updateClockCanvas();

                    // Animasi Pintu Kamar Tidur Berayun Buka/Tutup Otomatis saat Ada Orang Masuk!
                    if (this.bedroomDoorPivot && this.dewiGroup) {
                        // Cek jarak Dewi ke pintu kamar di dinding kanan (koordinat pintu global: x = -7.4, z = 2.9)
                        const dx = this.dewiGroup.position.x - (-7.4);
                        const dz = this.dewiGroup.position.z - (2.9);
                        const distToDoor = Math.sqrt(dx * dx + dz * dz);

                        if (this.currentCsStatus === 'sleeping' && distToDoor < 2.8 && distToDoor > 0.7) {
                            // Dewi sedang mendekat mau masuk kamar: BUKA PINTU KE DALAM!
                            this.targetDoorAngle = Math.PI / 2;
                        } else if (this.currentCsStatus === 'sleeping' && distToDoor <= 0.7) {
                            // Dewi sudah berbaring di dalam kasur: TUTUP PINTU
                            this.targetDoorAngle = 0;
                        } else if (this.currentCsStatus === 'working' && distToDoor < 2.5) {
                            // Dewi keluar kamar menuju meja kantor: BUKA PINTU
                            this.targetDoorAngle = Math.PI / 2;
                        } else {
                            // Kondisi normal: Pintu tertutup rapi
                            this.targetDoorAngle = 0;
                        }

                        // Lerp halus ayunan pintu
                        this.bedroomDoorPivot.rotation.y += (this.targetDoorAngle - this.bedroomDoorPivot.rotation.y) * 0.08;
                    }

                    // Animasi Pak Budi (Patroli di LORONG DEPAN BEBAS RINTANGAN - tidak nabrak meja/kursi!)
                    if (this.budiGroup) {
                        this.budiWalk.progress += delta * this.budiWalk.speed;
                        const patrolT = (Math.sin(this.budiWalk.progress) + 1) / 2; // bolak-balik 0 ke 1
                        const prevBudiX = this.budiGroup.position.x;
                        this.budiGroup.position.x = -4.8 + patrolT * 7.5; // Lorong depan luas antara x=-4.8 dan x=2.7
                        this.budiGroup.position.z = 3.6; // Di depan meja (Z positif aman tanpa rintangan meja)

                        // Putar badan mulus mengikuti arah gerak (tidak membalik instan)
                        const budiDir = Math.cos(this.budiWalk.progress) > 0 ? Math.PI / 2 : -Math.PI / 2;
                        let budiTurn = budiDir - this.budiGroup.rotation.y;
                        while (budiTurn > Math.PI) budiTurn -= Math.PI * 2;
                        while (budiTurn < -Math.PI) budiTurn += Math.PI * 2;
                        this.budiGroup.rotation.y += budiTurn * Math.min(1, delta * 4);

                        // Langkah kaki natural berbasis jarak tempuh (bukan denyut tetap)
                        const budiMoved = Math.abs(this.budiGroup.position.x - prevBudiX);
                        this.budiWalk.phase = (this.budiWalk.phase || 0) + budiMoved * this.walkPhasePerUnit;
                        const budiSwing = Math.sin(this.budiWalk.phase) * 0.35;
                        if (this.budiGroup.userData.legL) this.budiGroup.userData.legL.rotation.x = budiSwing;
                        if (this.budiGroup.userData.legR) this.budiGroup.userData.legR.rotation.x = -budiSwing;
                        if (this.budiGroup.userData.armL) this.budiGroup.userData.armL.rotation.x = -budiSwing * 0.6;
                        this.budiGroup.position.y = 0.02 + Math.abs(Math.sin(this.budiWalk.phase)) * 0.03;

                        // Gerakan mengayun pel lantai natural
                        const sweep = Math.sin(time * 3) * 0.16;
                        if (this.budiGroup.userData.armR) this.budiGroup.userData.armR.rotation.x = 0.2 + sweep;
                    }

                    // A. Update Layar Komputer Live (Simulasi coding terminal & live chat bubble)
                    if (this.screenCtx && this.screenTexture) {
                        const ctx = this.screenCtx;
                        ctx.fillStyle = '#090d16';
                        ctx.fillRect(0, 0, 512, 280);

                        // Top bar OS
                        ctx.fillStyle = '#111827';
                        ctx.fillRect(0, 0, 512, 36);
                        ctx.fillStyle = '#ef4444'; ctx.beginPath(); ctx.arc(20, 18, 5, 0, Math.PI * 2); ctx.fill();
                        ctx.fillStyle = '#f59e0b'; ctx.beginPath(); ctx.arc(36, 18, 5, 0, Math.PI * 2); ctx.fill();
                        ctx.fillStyle = '#10b981'; ctx.beginPath(); ctx.arc(52, 18, 5, 0, Math.PI * 2); ctx.fill();

                        ctx.fillStyle = '#cbd5e1';
                        ctx.font = 'bold 13px monospace';
                        ctx.fillText('RentSpace AI Engine · Live POV Monitor', 72, 22);

                        // Simulated Chat Streams or Active WhatsApp Customer Live Message
                        const wave = Math.sin(time * 6);
                        if (this.activeCustomerChat) {
                            // Layar menampilkan chat customer aktual & AI processing
                            ctx.fillStyle = '#1e293b';
                            ctx.fillRect(15, 48, 482, 70);
                            ctx.strokeStyle = '#f472b6'; ctx.lineWidth = 1.5;
                            ctx.strokeRect(15, 48, 482, 70);

                            ctx.fillStyle = '#f472b6';
                            ctx.font = 'bold 12px sans-serif';
                            ctx.fillText('[CUSTOMER WA]:', 25, 68);

                            ctx.fillStyle = '#ffffff';
                            ctx.font = '14px sans-serif';
                            const preview = this.activeCustomerChat.length > 48 ? this.activeCustomerChat.substring(0, 45) + '...' : this.activeCustomerChat;
                            ctx.fillText(preview, 25, 96);

                            // AI Generating response box
                            ctx.fillStyle = '#0f172a';
                            ctx.fillRect(15, 128, 482, 100);
                            ctx.strokeStyle = '#38bdf8'; ctx.lineWidth = 1;
                            ctx.strokeRect(15, 128, 482, 100);

                            ctx.fillStyle = '#38bdf8';
                            ctx.font = 'bold 12px sans-serif';
                            ctx.fillText('[GEMINI AI · DEWI CS]: MEMPROSES JAWABAN...', 25, 150);

                            // Typing cursor bar
                            const barW = 180 + Math.sin(time * 8) * 120;
                            ctx.fillStyle = '#10b981';
                            ctx.fillRect(25, 168, Math.max(80, barW), 12);
                            ctx.fillStyle = '#38bdf8';
                            ctx.fillRect(25, 192, 140, 10);
                        } else {
                            // Layar status streaming data normal
                            for (let i = 0; i < 6; i++) {
                                const barW = 120 + Math.sin(time * 3 + i * 1.5) * 80;
                                const isGreen = (i % 2 === 0);
                                ctx.fillStyle = isGreen ? '#10b981' : '#38bdf8';
                                ctx.fillRect(25, 55 + i * 28, Math.max(60, barW), 13);

                                ctx.fillStyle = '#475569';
                                ctx.fillRect(25 + barW + 15, 55 + i * 28, 80, 13);
                            }
                        }

                        // Status pill running
                        ctx.fillStyle = wave > 0 ? '#10b981' : '#059669';
                        ctx.fillRect(350, 242, 145, 26);
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 12px sans-serif';
                        ctx.fillText('● AI CORE ONLINE', 362, 259);

                        this.screenTexture.needsUpdate = true;
                    }

                    // A2. Update Smart TV 65-Inch di Lounge (RentSpace TV Live Display Simulator)
                    if (this.tvCtx && this.tvTexture) {
                        const tc = this.tvCtx;
                        // Video / Music Backdrop dinamis
                        const hue = (time * 12) % 360;
                        tc.fillStyle = '#060810';
                        tc.fillRect(0, 0, 640, 360);

                        // Gradient ambient lighting layaknya video music playing
                        const tvGrad = tc.createRadialGradient(320, 180, 40, 320, 180, 300);
                        tvGrad.addColorStop(0, `hsla(${hue}, 65%, 28%, 0.85)`);
                        tvGrad.addColorStop(0.6, `hsla(${(hue + 45) % 360}, 50%, 15%, 0.6)`);
                        tvGrad.addColorStop(1, '#05070e');
                        tc.fillStyle = tvGrad;
                        tc.fillRect(0, 0, 640, 360);

                        // Top bar HUD TV
                        tc.fillStyle = 'rgba(255,255,255,0.12)';
                        tc.fillRect(20, 18, 600, 48);
                        tc.fillStyle = '#ffffff';
                        tc.font = 'bold 20px sans-serif';
                        tc.fillText('📺 RentSpace TV · Lounge Station', 36, 49);

                        const now = new Date();
                        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                        tc.font = 'bold 18px monospace';
                        tc.fillStyle = '#38bdf8';
                        tc.fillText(timeStr, 560, 49);

                        // Visualizer equalizer bar (gelombang audio aktif)
                        for (let b = 0; b < 24; b++) {
                            const barH = 30 + Math.sin(time * 5 + b * 0.45) * 25 + Math.cos(time * 3 + b * 0.8) * 15;
                            tc.fillStyle = `hsl(${(hue + b * 8) % 360}, 85%, 65%)`;
                            tc.fillRect(50 + b * 22, 230 - barH, 14, barH);
                        }

                        // Now Playing Banner bawah
                        tc.fillStyle = 'rgba(9, 11, 20, 0.78)';
                        tc.fillRect(20, 260, 600, 80);
                        tc.strokeStyle = 'rgba(255, 255, 255, 0.15)';
                        tc.lineWidth = 1;
                        tc.strokeRect(20, 260, 600, 80);

                        // Icon Play & Music Title
                        tc.fillStyle = '#ec4899';
                        tc.font = 'bold 24px sans-serif';
                        tc.fillText('♪', 40, 308);

                        tc.fillStyle = '#ffffff';
                        tc.font = 'bold 18px sans-serif';
                        tc.fillText('Head In The Clouds · Chill Ambient Mix', 68, 296);

                        tc.fillStyle = '#94a3b8';
                        tc.font = '13px sans-serif';
                        tc.fillText('Live stream audio · 88rising & Pop 2026 Collection', 68, 324);

                        // Pill Live
                        tc.fillStyle = '#10b981';
                        tc.fillRect(530, 282, 70, 24);
                        tc.fillStyle = '#ffffff';
                        tc.font = 'bold 11px sans-serif';
                        tc.fillText('● ON AIR', 542, 298);

                        this.tvTexture.needsUpdate = true;
                    }

                    // B. Animasi Berjalan Dewi (NPC Walking Mechanics - Sinkron & Momentum Realistis)
                    // Kedip mata natural untuk semua karakter (selalu aktif)
                    if (this.dewiGroup) this.updateBlink('dewi', this.dewiGroup.userData, delta);
                    if (this.singgihGroup) this.updateBlink('singgih', this.singgihGroup.userData, delta);
                    if (this.anderaGroup) this.updateBlink('andera', this.anderaGroup.userData, delta);
                    if (this.budiGroup) this.updateBlink('budi', this.budiGroup.userData, delta);
                    if (this.dewiWalk.isMoving && this.dewiGroup) {
                        const dur = this.dewiWalk.walkDuration || 1.0;
                        this.dewiWalk.progress += delta / dur;
                        const t = Math.min(1, this.dewiWalk.progress);

                        // Ease-In-Out (percepatan & perlambatan natural: mulai pelan, akselerasi, lalu pelan lagi)
                        const eased = t * t * (3 - 2 * t);

                        const prevX = this.dewiGroup.position.x;
                        const prevZ = this.dewiGroup.position.z;

                        // Interpolasi posisi dengan kurva momentum
                        this.dewiGroup.position.x = this.dewiWalk.startX + (this.dewiWalk.targetX - this.dewiWalk.startX) * eased;
                        this.dewiGroup.position.z = this.dewiWalk.startZ + (this.dewiWalk.targetZ - this.dewiWalk.startZ) * eased;

                        // Jarak tempuh frame ini -> menggerakkan fase langkah (stride) secara proporsional
                        const moved = Math.hypot(this.dewiGroup.position.x - prevX, this.dewiGroup.position.z - prevZ);
                        this.dewiWalk.phase += moved * this.walkPhasePerUnit;

                        // Amplitude ayunan kaki proporsional kecepatan sesaat (mulai/berhenti melunak, tidak nyentak)
                        const amp = Math.max(0, Math.min(1, 6 * t * (1 - t) * 3)) * 0.42;

                        // Langkah kaki berlawanan (berjalan nyata) + ayunan lengan kontra-lateral
                        const legSwing = Math.sin(this.dewiWalk.phase) * amp;
                        if (this.dewiGroup.userData.legL) this.dewiGroup.userData.legL.rotation.x = legSwing;
                        if (this.dewiGroup.userData.legR) this.dewiGroup.userData.legR.rotation.x = -legSwing;
                        if (this.dewiGroup.userData.armL) this.dewiGroup.userData.armL.rotation.x = -legSwing * 0.7;
                        if (this.dewiGroup.userData.armR) this.dewiGroup.userData.armR.rotation.x = legSwing * 0.7;

                        // Bobbing badan sinkron langkah (naik saat kaki menapak gaya reaksi)
                        this.dewiGroup.position.y = 0.48 + Math.abs(Math.sin(this.dewiWalk.phase)) * 0.04 + Math.sin(this.dewiWalk.phase) * 0.008;

                        // Kemiringan maju halus saat berjalan (momentum natural)
                        this.dewiGroup.rotation.x = -0.03;
                        this.dewiGroup.rotation.z = 0;

                        // Putar badan mulus mengikuti arah tujuan (bukan loncat instan)
                        let turnDiff = this.dewiWalk.targetRotY - this.dewiGroup.rotation.y;
                        while (turnDiff > Math.PI) turnDiff -= Math.PI * 2;
                        while (turnDiff < -Math.PI) turnDiff += Math.PI * 2;
                        this.dewiGroup.rotation.y += turnDiff * Math.min(1, delta * 5);

                        if (t >= 1) {
                            this.dewiGroup.position.x = this.dewiWalk.targetX;
                            this.dewiGroup.position.z = this.dewiWalk.targetZ;
                            this.dewiGroup.rotation.y = this.dewiWalk.targetRotY;
                            this.dewiGroup.rotation.x = 0;
                            this.currentWaypointIdx++;
                            this.startNextWaypoint();
                        }
                    } else if (this.dewiGroup) {
                        // Posisi diam (Ngetik di meja, tidur nyenyak di kasur, ATAU santai di sofa)
                        const data = this.dewiGroup.userData;
                        
                        if (this.currentCsStatus === 'working') {
                            this.dewiGroup.rotation.x = 0;
                            this.dewiGroup.rotation.z = 0;
                            this.dewiGroup.position.y = this.spots.dewiDesk.y;
                            // Dewi mengetik aktif di keyboard & paha masuk rapi ke bawah meja
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.58 + Math.sin(time * 14) * 0.18;
                                data.armR.rotation.x = 0.58 + Math.cos(time * 14) * 0.18;
                                data.armL.rotation.z = 0;
                                data.armR.rotation.z = 0;
                            }
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = 1.25; // Melipat maju ke arah depan (masuk ke bawah kolong meja)
                                data.legR.rotation.x = 1.25;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 3.5) * 0.012;
                            }
                        } else if (this.currentCsStatus === 'sleeping') {
                            // Dewi tidur TENGKUREP NYENYAK di kasur kamar tidur AI (Tanpa gerak kaki/jalan di tempat!)
                            this.dewiGroup.position.set(this.spots.dewiBed.x, this.spots.dewiBed.y, this.spots.dewiBed.z);
                            this.dewiGroup.rotation.x = -Math.PI / 2; // Tengkurep telentang/tengkurep di kasur
                            this.dewiGroup.rotation.y = this.spots.dewiBed.rotY;
                            this.dewiGroup.rotation.z = 0.10;        // Miring santai natural

                            // Kaki DIAM tenang selonjor di kasur (TIDAK BOLEH MENGAYUN/JALAN DI TEMPAT!)
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = 0.05;
                                data.legR.rotation.x = -0.05;
                                data.legL.rotation.z = 0.08;
                                data.legR.rotation.z = -0.08;
                            }

                            // Tangan rileks merangkul bantal
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = -2.3;
                                data.armR.rotation.x = -2.1;
                                data.armL.rotation.z = -0.35;
                                data.armR.rotation.z = 0.35;
                            }

                            // Kepala menoleh ke samping bantal dengan nafas perlahan
                            if (data.head) {
                                data.head.rotation.y = 0.85;
                                data.head.position.y = data.baseHeadY + Math.sin(time * 1.5) * 0.008; // Irama nafas tidur lembut
                            }
                        } else {
                            // Dewi bersantai di sofa (tangan santai, kaki selonjor nyaman di sofa)
                            this.dewiGroup.rotation.x = 0;
                            this.dewiGroup.rotation.z = 0;
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.20 + Math.sin(time * 2) * 0.04;
                                data.armR.rotation.x = 0.20 - Math.sin(time * 2) * 0.04;
                            }
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = 1.25;
                                data.legR.rotation.x = 1.25;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 2) * 0.010;
                            }
                        }
                    }

                    // C. Animasi Berjalan Singgih
                    if (this.singgihWalk.isMoving && this.singgihGroup) {
                        const dur = this.singgihWalk.walkDuration || 1.0;
                        this.singgihWalk.progress += delta / dur;
                        const t = Math.min(1, this.singgihWalk.progress);
                        const eased = t * t * (3 - 2 * t);
                        const prevX = this.singgihGroup.position.x;
                        const prevZ = this.singgihGroup.position.z;
                        this.singgihGroup.position.x = this.singgihWalk.startX + (this.singgihWalk.targetX - this.singgihWalk.startX) * eased;
                        this.singgihGroup.position.z = this.singgihWalk.startZ + (this.singgihWalk.targetZ - this.singgihWalk.startZ) * eased;
                        const moved = Math.hypot(this.singgihGroup.position.x - prevX, this.singgihGroup.position.z - prevZ);
                        this.singgihWalk.phase += moved * this.walkPhasePerUnit;
                        const amp = Math.max(0, Math.min(1, 6 * t * (1 - t) * 3)) * 0.42;
                        const legSwing = Math.sin(this.singgihWalk.phase) * amp;
                        if (this.singgihGroup.userData.legL) this.singgihGroup.userData.legL.rotation.x = legSwing;
                        if (this.singgihGroup.userData.legR) this.singgihGroup.userData.legR.rotation.x = -legSwing;
                        if (this.singgihGroup.userData.armL) this.singgihGroup.userData.armL.rotation.x = -legSwing * 0.7;
                        if (this.singgihGroup.userData.armR) this.singgihGroup.userData.armR.rotation.x = legSwing * 0.7;
                        this.singgihGroup.position.y = 0.48 + Math.abs(Math.sin(this.singgihWalk.phase)) * 0.04 + Math.sin(this.singgihWalk.phase) * 0.008;
                        this.singgihGroup.rotation.x = -0.03;
                        this.singgihGroup.rotation.z = 0;
                        let turnDiff = this.singgihWalk.targetRotY - this.singgihGroup.rotation.y;
                        while (turnDiff > Math.PI) turnDiff -= Math.PI * 2;
                        while (turnDiff < -Math.PI) turnDiff += Math.PI * 2;
                        this.singgihGroup.rotation.y += turnDiff * Math.min(1, delta * 5);

                        if (t >= 1) {
                            this.singgihGroup.position.x = this.singgihWalk.targetX;
                            this.singgihGroup.position.z = this.singgihWalk.targetZ;
                            this.singgihGroup.rotation.y = this.singgihWalk.targetRotY;
                            this.singgihGroup.rotation.x = 0;
                            this.currentSinggihWpIdx++;
                            this.startNextSinggihWaypoint();
                        }
                    } else if (this.singgihGroup) {
                        const data = this.singgihGroup.userData;
                        if (this.currentSinggihStatus === 'working') {
                            this.singgihGroup.position.y = this.spots.singgihDesk.y;
                            if (data && data.armL && data.armR) {
                                data.armL.rotation.x = 0.52 + Math.cos(time * 11) * 0.14;
                                data.armR.rotation.x = 0.52 + Math.sin(time * 11) * 0.14;
                            }
                            if (data && data.legL && data.legR) {
                                data.legL.rotation.x = 1.25;
                                data.legR.rotation.x = 1.25;
                            }
                        } else {
                            this.singgihGroup.position.y = this.spots.singgihLounge.y;
                            this.singgihGroup.rotation.x = 0;
                            this.singgihGroup.rotation.z = 0;
                            if (data && data.armL && data.armR) {
                                data.armL.rotation.x = 0.20 + Math.cos(time * 2) * 0.03;
                                data.armR.rotation.x = 0.20 - Math.cos(time * 2) * 0.03;
                            }
                            if (data && data.legL && data.legR) {
                                data.legL.rotation.x = 1.25;
                                data.legR.rotation.x = 1.25;
                            }
                        }
                    }

                    // D. Animasi Berjalan Andera
                    if (this.anderaWalk.isMoving && this.anderaGroup) {
                        const dur = this.anderaWalk.walkDuration || 1.0;
                        this.anderaWalk.progress += delta / dur;
                        const t = Math.min(1, this.anderaWalk.progress);
                        const eased = t * t * (3 - 2 * t);
                        const prevX = this.anderaGroup.position.x;
                        const prevZ = this.anderaGroup.position.z;
                        this.anderaGroup.position.x = this.anderaWalk.startX + (this.anderaWalk.targetX - this.anderaWalk.startX) * eased;
                        this.anderaGroup.position.z = this.anderaWalk.startZ + (this.anderaWalk.targetZ - this.anderaWalk.startZ) * eased;
                        const moved = Math.hypot(this.anderaGroup.position.x - prevX, this.anderaGroup.position.z - prevZ);
                        this.anderaWalk.phase += moved * this.walkPhasePerUnit;
                        const amp = Math.max(0, Math.min(1, 6 * t * (1 - t) * 3)) * 0.42;
                        const legSwing = Math.sin(this.anderaWalk.phase) * amp;
                        if (this.anderaGroup.userData.legL) this.anderaGroup.userData.legL.rotation.x = legSwing;
                        if (this.anderaGroup.userData.legR) this.anderaGroup.userData.legR.rotation.x = -legSwing;
                        if (this.anderaGroup.userData.armL) this.anderaGroup.userData.armL.rotation.x = -legSwing * 0.7;
                        if (this.anderaGroup.userData.armR) this.anderaGroup.userData.armR.rotation.x = legSwing * 0.7;
                        this.anderaGroup.position.y = 0.48 + Math.abs(Math.sin(this.anderaWalk.phase)) * 0.04 + Math.sin(this.anderaWalk.phase) * 0.008;
                        this.anderaGroup.rotation.x = -0.03;
                        this.anderaGroup.rotation.z = 0;
                        let turnDiff = this.anderaWalk.targetRotY - this.anderaGroup.rotation.y;
                        while (turnDiff > Math.PI) turnDiff -= Math.PI * 2;
                        while (turnDiff < -Math.PI) turnDiff += Math.PI * 2;
                        this.anderaGroup.rotation.y += turnDiff * Math.min(1, delta * 5);

                        if (t >= 1) {
                            this.anderaGroup.position.x = this.anderaWalk.targetX;
                            this.anderaGroup.position.z = this.anderaWalk.targetZ;
                            this.anderaGroup.rotation.y = this.anderaWalk.targetRotY;
                            this.anderaGroup.rotation.x = 0;
                            this.currentAnderaWpIdx++;
                            this.startNextAnderaWaypoint();
                        }
                    } else if (this.anderaGroup) {
                        const data = this.anderaGroup.userData;
                        if (this.currentAnderaStatus === 'working') {
                            this.anderaGroup.position.y = this.spots.anderaDesk.y;
                            if (data && data.armL && data.armR) {
                                data.armL.rotation.x = 0.50 + Math.sin(time * 8) * 0.14;
                                data.armR.rotation.x = 0.50 + Math.cos(time * 8) * 0.14;
                            }
                            if (data && data.legL && data.legR) {
                                data.legL.rotation.x = 1.25;
                                data.legR.rotation.x = 1.25;
                            }
                        } else {
                            this.anderaGroup.position.y = this.spots.anderaLounge.y;
                            this.anderaGroup.rotation.x = 0;
                            this.anderaGroup.rotation.z = 0;
                            if (data && data.armL && data.armR) {
                                data.armL.rotation.x = 0.20 + Math.sin(time * 2.2) * 0.04;
                                data.armR.rotation.x = 0.20 - Math.sin(time * 2.2) * 0.04;
                            }
                            if (data && data.legL && data.legR) {
                                data.legL.rotation.x = 1.25;
                                data.legR.rotation.x = 1.25;
                            }
                        }
                    }

                    // E. Blob Shadow mengikuti karakter (docking ke posisi X/Z, Y tetap di lantai)
                    if (this.dewiBlob && this.dewiGroup) this.dewiBlob.position.set(this.dewiGroup.position.x, 0.012, this.dewiGroup.position.z);
                    if (this.singgihBlob && this.singgihGroup) this.singgihBlob.position.set(this.singgihGroup.position.x, 0.012, this.singgihGroup.position.z);
                    if (this.anderaBlob && this.anderaGroup) this.anderaBlob.position.set(this.anderaGroup.position.x, 0.012, this.anderaGroup.position.z);
                    if (this.budiBlob && this.budiGroup) this.budiBlob.position.set(this.budiGroup.position.x, 0.012, this.budiGroup.position.z);

                    // Refresh shadow map tiap N frame (lihat init) — jauh lebih murah,
                    // dan secara visual tidak perceptible untuk gerak karakter.
                    this.shadowFrame = (this.shadowFrame || 0) + 1;
                    if (this.renderer && this.renderer.shadowMap && this.shadowFrame % this.shadowUpdateInterval === 0) {
                        this.renderer.shadowMap.needsUpdate = true;
                    }

                    this.renderFrame();
                }
            };

        // ==================================================================
        //  PUSAT REAL-TIME 3D OFFICE  (dipakai di luar Alpine & Livewire)
        //
        //  Semua status masuk lewat _rentSpaceOfficeSync(), semua event
        //  WebSocket lewat _rentSpaceOfficeHandler(). Satu channel global,
        //  satu callback, satu lifecycle -> tidak ada listener yatim.
        // ==================================================================
        window._rentSpaceOfficeChannel = 'ai-office';

        window._rentSpaceOfficeHandler = function (raw) {
            const app = window._threeOfficeApp;
            if (!app) return;

            // Bertahan untuk payload datar (public channel) maupun yang
            // yang dibungkus { data: {...} } (Echo private/presence).
            let payload = raw;
            let guard = 0;
            while (payload && typeof payload === 'object' && !payload.character && payload.data && guard++ < 3) {
                payload = payload.data;
            }
            if (!payload || typeof payload !== 'object' || !payload.character) return;

            const status = payload.action === 'idle' ? 'break' : (payload.action || 'working');
            console.log('[3D Office] activity:', payload.character, status, payload.message || '');

            app.requestAgentStatus(payload.character, status);
            if (payload.message) app.updateLiveBubble(payload.character, payload.message);
        };

        window._rentSpaceOfficeBind = function () {
            if (window._rentSpaceOfficeBound) return true;

            const echo = window.Echo;
            if (!echo) {
                // app.js (module) dievaluasi setelah script klasik. Kalau Alpine
                // menang balapan, coba lagi — jangan sampai listener hilang.
                setTimeout(window._rentSpaceOfficeBind, 300);
                return false;
            }

            try {
                // Buang subscription lama supaya tidak ada handler ganda.
                if (echo.channels && echo.channels[window._rentSpaceOfficeChannel]) {
                    echo.leaveChannel(window._rentSpaceOfficeChannel);
                }
                echo.channel(window._rentSpaceOfficeChannel)
                    .listen('.activity', window._rentSpaceOfficeHandler);
                window._rentSpaceOfficeBound = true;
                console.log('[3D Office] subscribe OK -> ai-office:.activity');
            } catch (err) {
                console.warn('[3D Office] subscribe gagal, retry...', err);
                setTimeout(window._rentSpaceOfficeBind, 1500);
            }

            return window._rentSpaceOfficeBound;
        };

        window._rentSpaceOfficeSync = function (detail) {
            const app = window._threeOfficeApp;
            if (!app || !detail) return;

            const office = window._rentSpaceOfficeHandler;
            office({ character: 'dewi', action: (detail.customerBubble || detail.csStatus === 'working') ? 'working' : (detail.csStatus || 'break'), message: detail.customerBubble });
            office({ character: 'singgih', action: detail.coreStatus || 'break' });
            office({ character: 'andera', action: (detail.reportBubble || detail.reportStatus === 'working') ? 'working' : (detail.reportStatus || 'break'), message: detail.reportBubble });
        };

        // ---------------------------------------------------------------
        //  Pasang sekali per halaman: Alpine init() bisa dievaluasi ulang, tapi
        //  listener WebSocket tidak boleh pernah terpasang dua kali.
        // ---------------------------------------------------------------
        if (window.Echo) {
            window._rentSpaceOfficeBind();
        } else {
            document.addEventListener('livewire:init', () => window._rentSpaceOfficeBind(), { once: true });
            setTimeout(window._rentSpaceOfficeBind, 300);
        }
        </script>

        <div id="three-office-card" class="relative w-full transition-all duration-300 bg-[#161513] overflow-hidden" 
             :class="isExpanded ? 'h-[760px]' : 'h-[540px] sm:h-[580px]'"
x-data="{
                 isExpanded: false,
                 init() {
                     window._threeOfficeAlpine = this;

                     this.$nextTick(() => {
                         const app = window._threeOfficeApp;
                         app.currentCsStatus = @js($csStatus);
                         app.currentSinggihStatus = @js($coreStatus);
                         app.currentAnderaStatus = @js($reportStatus);

                         app.init(this.$refs.canvasContainer, @js($csStatus));
                         @if(!empty($latestCustomerText))
                             app.updateLiveBubble('dewi', @js($latestCustomerText));
                         @endif
                         @if(!empty($latestReportText))
                             app.updateLiveBubble('andera', @js($latestReportText));
                         @endif
                     });

                     // ---------------------------------------------------------------
                     //  REAL-TIME LISTENER (Laravel Echo / Pusher, channel ai-office)
                     //
                     //  WAJIB di-bind di window, BUKAN di dalam init() Alpine.
                     //  Element ini ada di dalam wire:ignore, jadi Alpine bisa
                     //  re-evaluasi x-data::init(); listener lama yang dibuat
                     //  Alpine akan menggantung di channel Pusher yang sudah tidak
                     //  dipakai, dan bound ke this.$refs yang sudah mati.
                     // ---------------------------------------------------------------
                     window._rentSpaceOfficeBind();

                     // Status awal dipaksa lewat funnel yang sama supaya karakter
                     // yang belum sesuai posisi langsung berjalan (bukan diam).
                     window._rentSpaceOfficeSync({
                         csStatus: @js($csStatus),
                         coreStatus: @js($coreStatus),
                         reportStatus: @js($reportStatus),
                         customerBubble: @js($latestCustomerText),
                         reportBubble: @js($latestReportText),
                     });
                 },
                 syncStatus(detail) {
                     window._rentSpaceOfficeSync(detail || {});
                 }
             }"
             @ai-status-sync.window="syncStatus($event.detail)"
             wire:ignore>
            
            <div x-ref="canvasContainer" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

            <!-- Modern Floating HUD Overlay (Informative & Cute) -->
            <div class="absolute bottom-3 left-4 right-4 pointer-events-none flex items-center justify-between flex-wrap gap-2 text-[11px] font-mono text-zinc-300 bg-zinc-950/80 px-3.5 py-2 rounded-xl border border-white/10 backdrop-blur-md">
                <div class="flex items-center gap-2 sm:gap-4 flex-wrap">
                    <span class="flex items-center gap-1.5 text-pink-400 font-bold">
                        <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                        <span>Dewi (CS Customer)</span>
                    </span>
                    <span class="text-zinc-600 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5 text-teal-400 font-bold">
                        <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                        <span>Singgih (Core AI)</span>
                    </span>
                    <span class="text-zinc-600 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5 text-amber-400 font-bold">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Andera (Finance & Report)</span>
                    </span>
                </div>
                <div class="text-zinc-400 hidden md:block">
                    🖱️ Geser mouse / usap layar untuk putar sudut 3D
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
                            <button type="button" 
                                @click="
                                    const agent = testChannel === 'wa_group_report' ? 'singgih' : 'dewi';
                                    window._threeOfficeApp?.requestAgentStatus(agent, 'working');
                                    if (testChannel === 'wa_group_report') {
                                        window._threeOfficeApp?.requestAgentStatus('andera', 'working');
                                    }
                                    if (testInput) {
                                        window._threeOfficeApp?.updateLiveBubble(agent, testInput.substring(0, 32));
                                    }
                                "
                                wire:click="runTestPrompt" wire:loading.attr="disabled"
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
