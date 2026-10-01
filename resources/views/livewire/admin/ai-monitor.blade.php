<div wire:poll.5s class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 pb-28 sm:pb-12">
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
                        this.targetLookAt = { x: -9.5, y: 1.0, z: 2.5 };
                    } else if (viewName === 'dewi_pov') {
                        // POV Menatap Langsung ke Layar Monitor Kerja Dewi
                        this.rotY = 0.05; // Menghadap lurus ke arah monitor dari belakang Dewi
                        this.rotX = 0.12;
                        this.cameraRadius = 4.2;
                        this.targetLookAt = { x: -3.2, y: 1.45, z: 0.70 };
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

                toggleExpand() {
                    if (window._threeOfficeAlpine) {
                        window._threeOfficeAlpine.isExpanded = !window._threeOfficeAlpine.isExpanded;
                        setTimeout(() => {
                            if (this.renderer && this.camera && this.container) {
                                const w = this.container.clientWidth;
                                const h = this.container.clientHeight;
                                this.camera.aspect = w / h;
                                this.camera.updateProjectionMatrix();
                                this.renderer.setSize(w, h);
                            }
                        }, 320);
                    }
                },

                // Posisi koordinat penting di ruangan (Duduk pas di kursi ergonomis tanpa tembus meja)
                spots: {
                    dewiDesk: { x: -3.2, y: 0.44, z: 1.45, rotY: Math.PI },   // Duduk pas di kursi menghadap monitor ke arah -Z
                    dewiLounge: { x: 5.0, y: 0.42, z: 1.4, rotY: 0.0 },       // Duduk santai di sofa
                    dewiBed: { x: -9.7, y: 0.58, z: 2.5, rotY: Math.PI / 2 }, // Berbaring di kasur kamar tidur AI
                    singgihDesk: { x: -1.2, y: 0.44, z: 1.45, rotY: Math.PI }, // Duduk pas di kursi menghadap monitor ke arah -Z
                    anderaDesk: { x: -2.2, y: 0.44, z: -1.25, rotY: 0.0 }     // Duduk pas di seberang menghadap monitor ke arah +Z
                },

                // State transisi animasi jalan (NPC walking)
                dewiWalk: {
                    isMoving: false,
                    startX: 0,
                    startZ: 0,
                    targetX: 0,
                    targetZ: 0,
                    targetRotY: 0,
                    progress: 1,
                    speed: 0.55 // durasi perpindahan detik
                },

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

                    const setup = () => {
                        if (typeof THREE === 'undefined') {
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
                        this.scene.background = new THREE.Color(0x1a1816);
                        this.scene.fog = new THREE.FogExp2(0x1a1816, 0.018);

                        // 2. Camera Isometric Perspective
                        const aspect = width / height;
                        this.camera = new THREE.PerspectiveCamera(36, aspect, 0.1, 100);
                        this.updateCameraPos();

                        // 3. WebGL Renderer with ACESFilmicToneMapping (Cinematic Warmth & PBR)
                        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
                        this.renderer.setSize(width, height);
                        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
                        this.renderer.shadowMap.enabled = true;
                        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
                        
                        // Tone mapping realistis studio arsitektural
                        if (THREE.ACESFilmicToneMapping) {
                            this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
                            this.renderer.toneMappingExposure = 1.08;
                        }
                        if (THREE.sRGBEncoding) {
                            this.renderer.outputEncoding = THREE.sRGBEncoding;
                        }
                        container.appendChild(this.renderer.domElement);

                        // 4. Pencahayaan Realistis: Warm Luxury Interior (2700K - 3000K Lighting)
                        // A. Ambient Light hangat lembut (tidak bikin flat)
                        const ambient = new THREE.AmbientLight(0xffeedb, 0.55);
                        this.scene.add(ambient);

                        // B. Hemisphere Light (Langit-langit warm cream, pantulan lantai kayu walnut)
                        const hemiLight = new THREE.HemisphereLight(0xffedd5, 0x452a1a, 0.45);
                        this.scene.add(hemiLight);

                        // C. Main Warm Key Light (Spot/Directional matahari jendela studio)
                        const sunLight = new THREE.DirectionalLight(0xfff3e0, 1.25);
                        sunLight.position.set(11, 18, 13);
                        sunLight.castShadow = true;
                        sunLight.shadow.mapSize.width = 1024;
                        sunLight.shadow.mapSize.height = 1024;
                        sunLight.shadow.camera.near = 0.5;
                        sunLight.shadow.camera.far = 45;
                        sunLight.shadow.bias = -0.0006;
                        this.scene.add(sunLight);

                        // D. Ceiling Downlights (Lampu sorot gantung warm golden di atas meja bersama)
                        const deskPendantLight = new THREE.PointLight(0xffe2b3, 1.35, 14, 1.8);
                        deskPendantLight.position.set(-2.2, 4.2, 0.1);
                        this.scene.add(deskPendantLight);

                        // E. Warm Lounge Sconce (Pencahayaan santai ruang sofa)
                        const loungeWarmLight = new THREE.PointLight(0xffd699, 1.2, 13, 1.8);
                        loungeWarmLight.position.set(4.8, 3.8, 1.2);
                        this.scene.add(loungeWarmLight);

                        // F. Pantry & Kitchen Accent Light (Warm amber minimalis)
                        const pantryLight = new THREE.PointLight(0xffe8cc, 1.1, 12, 1.8);
                        pantryLight.position.set(4.8, 3.8, -2.8);
                        this.scene.add(pantryLight);

                        // G. Neon Box Subtle White Backlight (Sesuai request: putih minimalis elegan)
                        const neonBoxLight = new THREE.PointLight(0xffffff, 0.8, 8, 2);
                        neonBoxLight.position.set(0, 3.8, -4.0);
                        this.scene.add(neonBoxLight);

                        // 5. Inisialisasi Dynamic Animated Screen Texture (Live Code/Chat Matrix)
                        this.initAnimatedScreenCanvas();

                        // 6. Build Multi-Room Studio & Characters
                        this.buildRoom();

                        // Posisi awal Dewi
                        if (this.dewiGroup) {
                            const initSpot = (this.currentCsStatus === 'working') ? this.spots.dewiDesk : this.spots.dewiLounge;
                            this.dewiGroup.position.set(initSpot.x, initSpot.y, initSpot.z);
                            this.dewiGroup.rotation.y = initSpot.rotY;
                        }

                        this.renderer.render(this.scene, this.camera);

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

                        dom.addEventListener('mousedown', (e) => {
                            this.isDragging = true;
                            this.prevMouse = { x: e.clientX, y: e.clientY };
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

                        window.addEventListener('resize', () => {
                            if (!container || !this.camera || !this.renderer) return;
                            const w = container.clientWidth;
                            const h = container.clientHeight;
                            if (w === 0 || h === 0) return;
                            this.camera.aspect = w / h;
                            this.camera.updateProjectionMatrix();
                            this.renderer.setSize(w, h);
                        });

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
                },

                updateCameraPos() {
                    const radius = this.cameraRadius || 18.5;
                    const tx = this.targetLookAt?.x || 0;
                    const ty = this.targetLookAt?.y || 1.0;
                    const tz = this.targetLookAt?.z || 0;

                    this.camera.position.x = tx + radius * Math.sin(this.rotY) * Math.cos(this.rotX);
                    this.camera.position.y = ty + radius * Math.sin(this.rotX) + 1.2;
                    this.camera.position.z = tz + radius * Math.cos(this.rotY) * Math.cos(this.rotX);
                    this.camera.lookAt(tx, ty, tz);
                },

                // Trigger perpindahan jalan Dewi (NPC Walking) antara Meja dan Sofa
                updateCsPosition(status) {
                    if (this.currentCsStatus === status && !this.dewiWalk.isMoving) return;
                    this.currentCsStatus = status;

                    if (!this.dewiGroup) return;

                    let target = this.spots.dewiLounge;
                    if (status === 'working') {
                        this.setMood('dewi', '⌨️ Sedang Membalas Chat...');
                        target = this.spots.dewiDesk;
                    } else if (status === 'sleeping') {
                        this.setMood('dewi', '🪫 Low Energy · Tidur Zzz...');
                        target = this.spots.dewiBed;
                    } else {
                        // Variasi mood santai saat istirahat (musikan / ngopi santai)
                        const breakMoods = ['🎧 Lagi Dengerin Musik', '☕ Istirahat Santai', '🥤 Minum & Recharge', '🛋️ Duduk Santai Senang'];
                        const randomMood = breakMoods[Math.floor(Math.random() * breakMoods.length)];
                        this.setMood('dewi', randomMood);
                        target = this.spots.dewiLounge;
                    }
                    
                    this.dewiWalk.isMoving = true;
                    this.dewiWalk.startX = this.dewiGroup.position.x;
                    this.dewiWalk.startZ = this.dewiGroup.position.z;
                    this.dewiWalk.targetX = target.x;
                    this.dewiWalk.targetZ = target.z;
                    this.dewiWalk.targetRotY = target.rotY;
                    this.dewiWalk.progress = 0;

                    // Buat Dewi menghadap ke arah tujuan jalan
                    const angle = Math.atan2(target.x - this.dewiWalk.startX, target.z - this.dewiWalk.startZ);
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
                    const shoeMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.4 });

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

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.88 };
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

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.88 };
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
                        const displayMat = new THREE.MeshBasicMaterial({ map: this.screenTexture });
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
                    const marbleMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.2, metalness: 0.1 });
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
                    const fridgeMat = new THREE.MeshStandardMaterial({ color: 0xd4d4d8, metalness: 0.6, roughness: 0.35 });
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
                    const gallonBase = new THREE.Mesh(new THREE.BoxGeometry(0.45, 1.0, 0.45), new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.3 }));
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

                    const tvScreen = new THREE.Mesh(new THREE.PlaneGeometry(2.12, 1.17), new THREE.MeshBasicMaterial({ map: this.screenTexture }));
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

                    // Tulisan "RENTSPACE" Huruf Timbul Putih Bersih (Background transparan murni)
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '900 96px "Inter", "Outfit", sans-serif';
                    ctx.textAlign = 'center';
                    ctx.letterSpacing = '8px';
                    ctx.fillText('RENTSPACE', canvas.width / 2, 115);

                    // Subtitle elegan
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
                    ctx.font = '600 24px "Inter", "Outfit", sans-serif';
                    ctx.letterSpacing = '6px';
                    ctx.fillText('HEADQUARTER & CUSTOMER SERVICE', canvas.width / 2, 168);

                    const texture = new THREE.CanvasTexture(canvas);

                    // Panel transparan menempel tepat di dinding (-4.58)
                    const faceGeo = new THREE.PlaneGeometry(6.4, 1.6);
                    const faceMat = new THREE.MeshBasicMaterial({ map: texture, transparent: true });
                    const faceMesh = new THREE.Mesh(faceGeo, faceMat);
                    faceMesh.position.set(-2.2, 3.6, -4.58);
                    this.scene.add(faceMesh);

                    // Backlight putih lembut persis di belakang huruf
                    const wallBacklight = new THREE.PointLight(0xffffff, 1.2, 6, 2);
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

                // Tanaman Hias Monsterra Pot
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

                                // 8. Ruang Kamar Tidur Tertutup AI (Private Rest Bedroom & Charging Sanctuary)
                buildBedroom(x, y, z) {
                    const bedGroup = new THREE.Group();
                    bedGroup.position.set(x, y, z);

                    // Dinding Partisi Kamar Tidur: Memisahkan kamar dari kantor utama
                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x2b2724, roughness: 0.9 });
                    
                    // Dinding Depan Kamar (dengan bukaan pintu masuk)
                    const frontWallA = new THREE.Mesh(new THREE.BoxGeometry(2.4, 4.8, 0.3), wallMat);
                    frontWallA.position.set(-1.6, 2.4, 2.2);
                    bedGroup.add(frontWallA);

                    const doorHeader = new THREE.Mesh(new THREE.BoxGeometry(1.6, 1.2, 0.3), wallMat);
                    doorHeader.position.set(0.4, 4.2, 2.2);
                    bedGroup.add(doorHeader);

                    // Kusen & Pintu Kayu Kamar Terbuka
                    const doorFrame = new THREE.Mesh(new THREE.BoxGeometry(1.6, 3.6, 0.34), new THREE.MeshStandardMaterial({ color: 0x18181b }));
                    doorFrame.position.set(0.4, 1.8, 2.2);
                    bedGroup.add(doorFrame);

                    const doorLeaf = new THREE.Mesh(new THREE.BoxGeometry(1.4, 3.4, 0.08), new THREE.MeshStandardMaterial({ color: 0x4a3427, roughness: 0.6 }));
                    doorLeaf.position.set(0.4, 1.8, 2.2);
                    doorLeaf.rotation.y = -0.95; // Pintu setengah terbuka estetik
                    bedGroup.add(doorLeaf);

                    // Dinding Kanan Kamar (Pemisah lorong)
                    const sideWall = new THREE.Mesh(new THREE.BoxGeometry(0.3, 4.8, 4.6), wallMat);
                    sideWall.position.set(1.4, 2.4, -0.1);
                    bedGroup.add(sideWall);

                    // Lantai Parket Khusus Kamar Tidur (Abu Hangat Cozy)
                    const bedFloor = new THREE.Mesh(new THREE.BoxGeometry(5.2, 0.02, 4.6), new THREE.MeshStandardMaterial({ color: 0x241d18, roughness: 0.7 }));
                    bedFloor.position.set(-1.0, 0.015, -0.1);
                    bedGroup.add(bedFloor);

                    // Karpet Bulu Empuk Kamar
                    const rug = new THREE.Mesh(new THREE.BoxGeometry(3.2, 0.03, 3.0), new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.95 }));
                    rug.position.set(-1.2, 0.03, 0.2);
                    bedGroup.add(rug);

                    // Rangka Ranjang Kayu Minimalis Elegan
                    const frameMat = new THREE.MeshStandardMaterial({ color: 0x3d271d, roughness: 0.7 });
                    const bedFrame = new THREE.Mesh(new THREE.BoxGeometry(2.3, 0.35, 2.8), frameMat);
                    bedFrame.position.set(-1.2, 0.18, 0);
                    bedFrame.castShadow = true;
                    bedGroup.add(bedFrame);

                    // Kasur Springbed Empuk Putih
                    const mattressMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.4 });
                    const mattress = new THREE.Mesh(new THREE.BoxGeometry(2.1, 0.28, 2.6), mattressMat);
                    mattress.position.set(-1.2, 0.45, 0);
                    mattress.castShadow = true;
                    bedGroup.add(mattress);

                    // Selimut Hangat Pink Pastel
                    const blanketMat = new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.8 });
                    const blanket = new THREE.Mesh(new THREE.BoxGeometry(2.12, 0.12, 1.7), blanketMat);
                    blanket.position.set(-1.2, 0.52, 0.45);
                    blanket.castShadow = true;
                    bedGroup.add(blanket);

                    // 2x Bantal Empuk
                    const pillowMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.5 });
                    const pillow1 = new THREE.Mesh(new THREE.BoxGeometry(0.70, 0.16, 0.50), pillowMat);
                    pillow1.position.set(-1.7, 0.62, -0.85);
                    bedGroup.add(pillow1);

                    const pillow2 = new THREE.Mesh(new THREE.BoxGeometry(0.70, 0.16, 0.50), pillowMat);
                    pillow2.position.set(-0.7, 0.62, -0.85);
                    bedGroup.add(pillow2);

                    // Meja Nakas & Lampu Tidur Warm
                    const nakas = new THREE.Mesh(new THREE.BoxGeometry(0.60, 0.55, 0.60), frameMat);
                    nakas.position.set(-2.65, 0.275, -0.85);
                    nakas.castShadow = true;
                    bedGroup.add(nakas);

                    const lampBase = new THREE.Mesh(new THREE.CylinderGeometry(0.10, 0.14, 0.28, 12), new THREE.MeshStandardMaterial({ color: 0xd4d4d8 }));
                    lampBase.position.set(-2.65, 0.68, -0.85);
                    bedGroup.add(lampBase);

                    const lampShade = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.22, 0.26, 14), new THREE.MeshStandardMaterial({ color: 0xfef08a, emissive: 0xfef08a, emissiveIntensity: 0.4 }));
                    lampShade.position.set(-2.65, 0.94, -0.85);
                    bedGroup.add(lampShade);

                    const nightLight = new THREE.PointLight(0xffe8ba, 1.1, 7, 2);
                    nightLight.position.set(-2.65, 1.1, -0.85);
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

                    // Poster Katalog iPhone RentSpace di dinding kantor
                    createFrame(-6.0, 3.4, -4.58, 1.4, 1.8, 'iPHONE 16 PRO', 'READY TO RENT', '#1e293b');
                    createFrame(-7.8, 3.4, -4.58, 1.2, 1.6, 'RENTSPACE TEAM', 'EXCELLENCE 2026', '#0f766e');
                    createFrame(8.2, 3.4, -4.58, 1.3, 1.7, 'COFFEE & CODE', 'AI LAB PURWOKERTO', '#854d0e');

                    this.scene.add(group);
                },

                // 10. AC Modern Dinding (Air Conditioner Inverter dengan Lampu Indikator Hijau)
                buildAirConditioner(x, y, z) {
                    const acGroup = new THREE.Group();
                    acGroup.position.set(x, y, z);

                    const acMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.25 });
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

                    const clockFace = new THREE.Mesh(new THREE.CircleGeometry(0.44, 32), new THREE.MeshBasicMaterial({ map: this.clockTexture }));
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

                    // Gagang Pel & Spons (NEMPEL DI TANGAN KANAN)
                    const mopPole = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 1.35, 8), new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.8 }));
                    mopPole.position.set(0.05, -0.25, 0.20);
                    mopPole.rotation.x = 0.45;
                    armR.add(mopPole);

                    const mopHead = new THREE.Mesh(new THREE.BoxGeometry(0.32, 0.08, 0.16), new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.9 }));
                    mopHead.position.set(0.05, -0.80, 0.48);
                    armR.add(mopHead);
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
                    // 1. Lantai Parquet Kayu Walnut Hangat Mewah
                    const floorGeo = new THREE.PlaneGeometry(24, 15);
                    const floorMat = new THREE.MeshStandardMaterial({ color: 0x2e231c, roughness: 0.6, metalness: 0.05 });
                    const floor = new THREE.Mesh(floorGeo, floorMat);
                    floor.rotation.x = -Math.PI / 2;
                    floor.receiveShadow = true;
                    this.scene.add(floor);

                    // Grid garis lantai subtle
                    const grid = new THREE.GridHelper(24, 24, 0x47382d, 0x382c23);
                    grid.position.y = 0.01;
                    this.scene.add(grid);

                    // 2. Dinding Utama & Partisi Multi-Ruangan (Warna Cream Taupe Hangat Elegan)
                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x2b2724, roughness: 0.9 });

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

                    // 7. Ruang Kamar Tidur AI (Rest Bedroom saat Token Habis / Low Energy)
                    this.buildBedroom(-8.5, 0, 2.5);

                    // 8. Dekorasi Dinding: Frame Galeri Poster, Jam Nyata, AC Dinding & Pintu
                    this.buildGalleryWall();
                    this.buildRealClock(-2.2, 4.8, -4.56);
                    this.buildAirConditioner(2.5, 4.4, -4.56);
                    this.buildOutdoorWindow();
                    this.buildOfficeDoors();

                    // 9. Perabot Pendukung (Server Rack, Lemari Arsip, Tanaman Hias)
                    this.buildServerRack(-8.8, 0, -4.0);
                    this.buildBookshelf(-5.2, 0, -4.0);
                    this.buildPlant(-6.5, 0, -4.1);
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
                    this.singgihGroup.position.set(this.spots.singgihDesk.x, this.spots.singgihDesk.y, this.spots.singgihDesk.z);
                    this.singgihGroup.rotation.y = this.spots.singgihDesk.rotY;
                    this.scene.add(this.singgihGroup);

                    // ANDERA (Report & Finance)
                    this.anderaGroup = this.buildMaleCharacter('Andera', 'Report & Finance', 0xd97706, 0x451a03, '#fbbf24', 'andera');
                    this.anderaGroup.position.set(this.spots.anderaDesk.x, this.spots.anderaDesk.y, this.spots.anderaDesk.z);
                    this.anderaGroup.rotation.y = this.spots.anderaDesk.rotY;
                    this.scene.add(this.anderaGroup);
                },

                animate() {
                    this.animationFrameId = requestAnimationFrame(() => this.animate());

                    if (!this.renderer || !this.scene || !this.camera) return;

                    const time = this.clock ? this.clock.getElapsedTime() : 0;
                    const delta = 0.016; // approx frame time

                    // Update Jam Dinding Real-Time
                    this.updateClockCanvas();

                    // Animasi Pak Budi (Patroli di LORONG DEPAN BEBAS RINTANGAN - tidak nabrak meja/kursi!)
                    if (this.budiGroup) {
                        this.budiWalk.progress += delta * this.budiWalk.speed;
                        const patrolT = (Math.sin(this.budiWalk.progress) + 1) / 2; // bolak-balik 0 ke 1
                        this.budiGroup.position.x = -4.8 + patrolT * 7.5; // Lorong depan luas antara x=-4.8 dan x=2.7
                        this.budiGroup.position.z = 3.6; // Di depan meja (Z positif aman tanpa rintangan meja)
                        this.budiGroup.rotation.y = Math.cos(this.budiWalk.progress) > 0 ? Math.PI / 2 : -Math.PI / 2;

                        // Gerakan mengayun pel lantai natural
                        const sweep = Math.sin(time * 5) * 0.20;
                        if (this.budiGroup.userData.armR) this.budiGroup.userData.armR.rotation.x = 0.2 + sweep;
                        if (this.budiGroup.userData.legL) this.budiGroup.userData.legL.rotation.x = Math.sin(time * 7) * 0.35;
                        if (this.budiGroup.userData.legR) this.budiGroup.userData.legR.rotation.x = -Math.sin(time * 7) * 0.35;
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

                    // B. Animasi Berjalan Dewi (NPC Walking Mechanics)
                    if (this.dewiWalk.isMoving && this.dewiGroup) {
                        this.dewiWalk.progress += delta * this.dewiWalk.speed;
                        const t = Math.min(1, this.dewiWalk.progress);

                        // Interpolasi posisi (lerp)
                        this.dewiGroup.position.x = this.dewiWalk.startX + (this.dewiWalk.targetX - this.dewiWalk.startX) * t;
                        this.dewiGroup.position.z = this.dewiWalk.startZ + (this.dewiWalk.targetZ - this.dewiWalk.startZ) * t;

                        // Langkah kaki mengayun saat berjalan
                        const legSwing = Math.sin(time * 16) * 0.45;
                        if (this.dewiGroup.userData.legL) this.dewiGroup.userData.legL.rotation.x = legSwing;
                        if (this.dewiGroup.userData.legR) this.dewiGroup.userData.legR.rotation.x = -legSwing;
                        if (this.dewiGroup.userData.armL) this.dewiGroup.userData.armL.rotation.x = -legSwing * 0.8;
                        if (this.dewiGroup.userData.armR) this.dewiGroup.userData.armR.rotation.x = legSwing * 0.8;

                        // Bobbing naik-turun saat melangkah
                        this.dewiGroup.position.y = 0.48 + Math.abs(Math.sin(time * 16)) * 0.06;

                        if (t >= 1) {
                            this.dewiWalk.isMoving = false;
                            this.dewiGroup.rotation.y = this.dewiWalk.targetRotY;
                        }
                    } else if (this.dewiGroup) {
                        // Posisi diam (Ngetik di meja ATAU bersantai santai di sofa)
                        const data = this.dewiGroup.userData;
                        if (this.currentCsStatus === 'working') {
                            // Dewi mengetik aktif di keyboard & paha masuk rapi ke bawah meja
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.58 + Math.sin(time * 14) * 0.18;
                                data.armR.rotation.x = 0.58 + Math.cos(time * 14) * 0.18;
                            }
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = 1.25; // Melipat maju ke arah depan (masuk ke bawah kolong meja)
                                data.legR.rotation.x = 1.25;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 3.5) * 0.012;
                            }
                        } else if (this.currentCsStatus === 'sleeping') {
                            // Dewi tidur santai di kasur kamar tidur AI & nafas beraturan
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.15;
                                data.armR.rotation.x = 0.15;
                            }
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = 0.05;
                                data.legR.rotation.x = 0.05;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 1.5) * 0.015; // Nafas pelan
                            }
                        } else {
                            // Dewi bersantai di sofa (tangan santai, kaki selonjor nyaman di sofa)
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.15 + Math.sin(time * 2) * 0.04;
                                data.armR.rotation.x = 0.15 - Math.sin(time * 2) * 0.04;
                            }
                            if (data.legL && data.legR) {
                                data.legL.rotation.x = -1.10;
                                data.legR.rotation.x = -1.10;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 2) * 0.010;
                            }
                        }
                    }

                    // C. Animasi Singgih (Core Dispatcher mengetik & kaki masuk rapi ke bawah meja)
                    if (this.singgihGroup) {
                        const data = this.singgihGroup.userData;
                        if (data && data.armL && data.armR) {
                            data.armL.rotation.x = 0.52 + Math.cos(time * 11) * 0.14;
                            data.armR.rotation.x = 0.52 + Math.sin(time * 11) * 0.14;
                        }
                        if (data && data.legL && data.legR) {
                            data.legL.rotation.x = 1.25; // Masuk ke arah bawah meja
                            data.legR.rotation.x = 1.25;
                        }
                    }

                    // D. Animasi Andera (Report Bot duduk di seberang hadap +Z, kaki masuk ke bawah meja)
                    if (this.anderaGroup) {
                        const data = this.anderaGroup.userData;
                        if (data && data.armL && data.armR) {
                            data.armL.rotation.x = 0.50 + Math.sin(time * 8) * 0.14;
                            data.armR.rotation.x = 0.50 + Math.cos(time * 8) * 0.14;
                        }
                        if (data && data.legL && data.legR) {
                            data.legL.rotation.x = 1.25; // Masuk rapi ke bawah meja seberang
                            data.legR.rotation.x = 1.25;
                        }
                    }

                    this.renderer.render(this.scene, this.camera);
                }
            };
        </script>

        <div class="relative w-full transition-all duration-300 bg-[#161513] overflow-hidden" 
             :class="isExpanded ? 'h-[760px]' : 'h-[540px] sm:h-[580px]'"
             x-data="{
                 isExpanded: false,
                 init() {
                     window._threeOfficeAlpine = this;
                     this.$nextTick(() => {
                         window._threeOfficeApp.init(this.$refs.canvasContainer, @js($csStatus));
                         @if(!empty($latestCustomerText))
                             window._threeOfficeApp.updateLiveBubble('dewi', @js($latestCustomerText));
                         @endif
                         @if(!empty($latestReportText))
                             window._threeOfficeApp.updateLiveBubble('andera', @js($latestReportText));
                         @endif
                     });
                 },
                 syncStatus(detail) {
                     if (detail.customerBubble) {
                         window._threeOfficeApp.updateCsPosition('working');
                         window._threeOfficeApp.updateLiveBubble('dewi', detail.customerBubble);
                     } else {
                         window._threeOfficeApp.updateCsPosition(detail.csStatus);
                     }
                     if (detail.reportBubble) {
                         window._threeOfficeApp.updateLiveBubble('andera', detail.reportBubble);
                     }
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
