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
            <div class="flex items-center gap-2.5">
                <span class="text-sm">🏢</span>
                <span class="text-xs font-bold text-foreground">Virtual Office & Lounge</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-semibold {{ $csStatus === 'working' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20' }}">
                    CS: {{ $csStatus === 'working' ? 'Aktif di Meja' : 'Istirahat (Sofa)' }}
                </span>
            </div>

            <!-- Simple Clean Controls -->
            <div class="flex items-center gap-2">
                <button wire:click="bonkAgent('cs_bot', 'work')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25 active:scale-95 transition">
                    <span>⚡</span>
                    <span>Tugaskan CS</span>
                </button>
                <button wire:click="bonkAgent('cs_bot', 'break')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-muted hover:bg-muted/80 text-muted-foreground border border-border active:scale-95 transition">
                    <span>🛋️</span>
                    <span>Istirahat</span>
                </button>
            </div>
        </div>

        @if($bonkMessage)
            <div class="px-5 py-2 bg-primary/10 border-b border-primary/20 text-xs text-primary font-medium flex items-center justify-between">
                <span>{{ $bonkMessage }}</span>
                <button wire:click="dismissBonk" class="text-muted-foreground hover:text-foreground text-xs ml-2">✕</button>
            </div>
        @endif

        <!-- 3D Canvas Viewport (Diperluas: height 440px dengan full width) -->
        <div class="relative w-full h-[400px] sm:h-[460px] bg-[#121816] overflow-hidden" 
             x-data="threeOffice({ csStatus: @js($csStatus), reportStatus: @js($reportStatus), bonk: @js($bonkedAgent) })" 
             x-init="init()" 
             wire:ignore>
            
            <div x-ref="canvasContainer" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

            <!-- Clean Floating HUD Overlay -->
            <div class="absolute bottom-3 left-4 pointer-events-none flex items-center gap-2 text-[11px] font-mono text-zinc-400/80 bg-zinc-950/60 px-3 py-1.5 rounded-lg border border-white/5 backdrop-blur-xs">
                <span>Meja Kerja (Kiri)</span>
                <span>•</span>
                <span>Lounge Sofa (Kanan)</span>
                <span>•</span>
                <span class="text-zinc-500 hidden sm:inline">Geser mouse untuk putar sudut 3D</span>
            </div>
        </div>
    </div>

    <!-- Three.js Library & Custom Isometric Office Engine -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('threeOffice', (config) => ({
                scene: null,
                camera: null,
                renderer: null,
                csGroup: null,
                reportGroup: null,
                coreGroup: null,
                screenMeshes: [],
                clock: null,
                isDragging: false,
                prevMouse: { x: 0, y: 0 },
                rotY: 0.45,
                rotX: 0.38,

                init() {
                    const container = this.$refs.canvasContainer;
                    const width = container.clientWidth || 800;
                    const height = container.clientHeight || 440;

                    this.clock = new THREE.Clock();

                    // 1. Scene
                    this.scene = new THREE.Scene();
                    this.scene.background = new THREE.Color(0x131a17);
                    this.scene.fog = new THREE.Fog(0x131a17, 25, 45);

                    // 2. Camera: Isometric Perspective
                    const aspect = width / height;
                    this.camera = new THREE.PerspectiveCamera(40, aspect, 0.1, 100);
                    this.updateCameraPos();

                    // 3. Renderer
                    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                    this.renderer.setSize(width, height);
                    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                    this.renderer.shadowMap.enabled = true;
                    this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
                    container.appendChild(this.renderer.domElement);

                    // 4. Lights
                    const ambient = new THREE.AmbientLight(0xffffff, 0.85);
                    this.scene.add(ambient);

                    const dirLight = new THREE.DirectionalLight(0xfffaed, 1.2);
                    dirLight.position.set(12, 20, 10);
                    dirLight.castShadow = true;
                    dirLight.shadow.mapSize.width = 1024;
                    dirLight.shadow.mapSize.height = 1024;
                    this.scene.add(dirLight);

                    const softBlueLight = new THREE.PointLight(0x38bdf8, 0.8, 15);
                    softBlueLight.position.set(-3, 3, 0);
                    this.scene.add(softBlueLight);

                    // 5. Build Room Props
                    this.buildRoom();

                    // 6. Mouse Orbit Drag
                    const dom = this.renderer.domElement;
                    dom.addEventListener('mousedown', (e) => {
                        this.isDragging = true;
                        this.prevMouse = { x: e.clientX, y: e.clientY };
                    });
                    window.addEventListener('mouseup', () => { this.isDragging = false; });
                    window.addEventListener('mousemove', (e) => {
                        if (!this.isDragging) return;
                        const dx = e.clientX - this.prevMouse.x;
                        const dy = e.clientY - this.prevMouse.y;
                        this.rotY -= dx * 0.006;
                        this.rotX = Math.max(0.2, Math.min(0.7, this.rotX + dy * 0.004));
                        this.prevMouse = { x: e.clientX, y: e.clientY };
                        this.updateCameraPos();
                    });

                    // Resize Listener
                    window.addEventListener('resize', () => {
                        if (!container) return;
                        const w = container.clientWidth;
                        const h = container.clientHeight;
                        this.camera.aspect = w / h;
                        this.camera.updateProjectionMatrix();
                        this.renderer.setSize(w, h);
                    });

                    this.animate();
                },

                updateCameraPos() {
                    const radius = 17;
                    this.camera.position.x = radius * Math.sin(this.rotY) * Math.cos(this.rotX);
                    this.camera.position.y = radius * Math.sin(this.rotX) + 1.5;
                    this.camera.position.z = radius * Math.cos(this.rotY) * Math.cos(this.rotX);
                    this.camera.lookAt(0, 0.8, 0);
                },

                buildRoom() {
                    // Floor (Isometric Grid Plane)
                    const floorGeo = new THREE.PlaneGeometry(18, 10);
                    const floorMat = new THREE.MeshStandardMaterial({ color: 0x1b2420, roughness: 0.8 });
                    const floor = new THREE.Mesh(floorGeo, floorMat);
                    floor.rotation.x = -Math.PI / 2;
                    floor.receiveShadow = true;
                    this.scene.add(floor);

                    // Subtle Grid Floor
                    const grid = new THREE.GridHelper(18, 18, 0x2e4238, 0x22312a);
                    grid.position.y = 0.01;
                    this.scene.add(grid);

                    // Back Wall
                    const wallGeo = new THREE.BoxGeometry(18, 4.5, 0.4);
                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x161e1b, roughness: 0.9 });
                    const wall = new THREE.Mesh(wallGeo, wallMat);
                    wall.position.set(0, 2.25, -5);
                    wall.receiveShadow = true;
                    this.scene.add(wall);

                    // Server Rack (Background Left)
                    this.buildServerRack(-7, 0, -4.2);

                    // WORK AREA (Kiri): 2 Work Desks with PC
                    // Desk 1: CS Bot Desk (x: -4.5, z: -1)
                    this.buildWorkDesk(-4.5, 0, -1);

                    // Desk 2: Core Engine Desk (x: -1.5, z: -1)
                    this.buildWorkDesk(-1.5, 0, -1);

                    // LOUNGE AREA (Kanan): Sofa & Coffee Table
                    this.buildLounge(4.5, 0, -1);

                    // CHARACTERS:
                    // 1. CS Bot: Jika 'working' duduk di Desk 1, jika 'break' duduk di Sofa
                    this.csGroup = this.buildCharacter(0x0284c7, 0x1e1b4b);
                    if (config.csStatus === 'working') {
                        this.csGroup.position.set(-4.5, 0.55, -1.9); // Kursi Desk 1
                        this.csGroup.rotation.y = 0;
                    } else {
                        this.csGroup.position.set(3.8, 0.45, -0.9); // Sofa
                        this.csGroup.rotation.y = 0.3;
                    }
                    this.scene.add(this.csGroup);

                    // 2. Core Bot: Selalu di Meja 2
                    this.coreGroup = this.buildCharacter(0x059669, 0x78350f);
                    this.coreGroup.position.set(-1.5, 0.55, -1.9);
                    this.scene.add(this.coreGroup);

                    // 3. Report Bot: Duduk di sofa sisi kanan
                    this.reportGroup = this.buildCharacter(0xd97706, 0x312e81);
                    this.reportGroup.position.set(5.2, 0.45, -0.9);
                    this.reportGroup.rotation.y = -0.3;
                    this.scene.add(this.reportGroup);
                },

                buildWorkDesk(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Top Table (Wood Oak)
                    const topGeo = new THREE.BoxGeometry(2.2, 0.12, 1.2);
                    const topMat = new THREE.MeshStandardMaterial({ color: 0x422e1b, roughness: 0.6 });
                    const top = new THREE.Mesh(topGeo, topMat);
                    top.position.y = 1.0;
                    top.castShadow = true;
                    top.receiveShadow = true;
                    group.add(top);

                    // Legs (Dark Metal)
                    const legMat = new THREE.MeshStandardMaterial({ color: 0x1f2937, metalness: 0.5 });
                    const legGeo = new THREE.CylinderGeometry(0.04, 0.04, 1.0, 8);
                    [[-0.95, -0.45], [0.95, -0.45], [-0.95, 0.45], [0.95, 0.45]].forEach(([lx, lz]) => {
                        const leg = new THREE.Mesh(legGeo, legMat);
                        leg.position.set(lx, 0.5, lz);
                        leg.castShadow = true;
                        group.add(leg);
                    });

                    // PC Monitor Screen
                    const screenGeo = new THREE.BoxGeometry(1.0, 0.6, 0.05);
                    const screenFrameMat = new THREE.MeshStandardMaterial({ color: 0x111827 });
                    const screenFrame = new THREE.Mesh(screenGeo, screenFrameMat);
                    screenFrame.position.set(0, 1.5, -0.2);
                    screenFrame.castShadow = true;
                    group.add(screenFrame);

                    // Glowing Display
                    const displayGeo = new THREE.PlaneGeometry(0.92, 0.52);
                    const displayMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8 });
                    const display = new THREE.Mesh(displayGeo, displayMat);
                    display.position.set(0, 1.5, -0.17);
                    group.add(display);
                    this.screenMeshes.push(display);

                    // Monitor Stand
                    const standGeo = new THREE.CylinderGeometry(0.03, 0.03, 0.35, 8);
                    const stand = new THREE.Mesh(standGeo, screenFrameMat);
                    stand.position.set(0, 1.15, -0.2);
                    group.add(stand);

                    // Keyboard
                    const kbGeo = new THREE.BoxGeometry(0.65, 0.03, 0.22);
                    const kbMat = new THREE.MeshStandardMaterial({ color: 0x374151 });
                    const kb = new THREE.Mesh(kbGeo, kbMat);
                    kb.position.set(0, 1.07, 0.15);
                    group.add(kb);

                    // Mouse
                    const mouseGeo = new THREE.BoxGeometry(0.08, 0.03, 0.12);
                    const mouse = new THREE.Mesh(mouseGeo, kbMat);
                    mouse.position.set(0.45, 1.07, 0.15);
                    group.add(mouse);

                    // Office Chair (Belakang meja)
                    const chairGeo = new THREE.BoxGeometry(0.6, 0.1, 0.6);
                    const chairMat = new THREE.MeshStandardMaterial({ color: 0x1f2937, roughness: 0.9 });
                    const chairSeat = new THREE.Mesh(chairGeo, chairMat);
                    chairSeat.position.set(0, 0.6, -0.9);
                    chairSeat.castShadow = true;
                    group.add(chairSeat);

                    const chairBackGeo = new THREE.BoxGeometry(0.55, 0.7, 0.08);
                    const chairBack = new THREE.Mesh(chairBackGeo, chairMat);
                    chairBack.position.set(0, 0.95, -1.2);
                    group.add(chairBack);

                    this.scene.add(group);
                },

                buildLounge(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Modern Blue Sofa
                    const sofaMat = new THREE.MeshStandardMaterial({ color: 0x1e3a8a, roughness: 0.8 });
                    const seatBase = new THREE.Mesh(new THREE.BoxGeometry(2.6, 0.45, 1.1), sofaMat);
                    seatBase.position.set(0, 0.35, 0);
                    seatBase.castShadow = true;
                    group.add(seatBase);

                    const seatBack = new THREE.Mesh(new THREE.BoxGeometry(2.6, 0.7, 0.25), sofaMat);
                    seatBack.position.set(0, 0.8, -0.42);
                    group.add(seatBack);

                    // Armrests
                    const armL = new THREE.Mesh(new THREE.BoxGeometry(0.25, 0.55, 1.1), sofaMat);
                    armL.position.set(-1.3, 0.55, 0);
                    group.add(armL);

                    const armR = new THREE.Mesh(new THREE.BoxGeometry(0.25, 0.55, 1.1), sofaMat);
                    armR.position.set(1.3, 0.55, 0);
                    group.add(armR);

                    // Coffee Table
                    const tableMat = new THREE.MeshStandardMaterial({ color: 0x78350f, roughness: 0.7 });
                    const table = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.35, 0.7), tableMat);
                    table.position.set(0, 0.2, 1.2);
                    table.castShadow = true;
                    group.add(table);

                    // Coffee Cup
                    const cupMat = new THREE.MeshStandardMaterial({ color: 0xf3f4f6 });
                    const cup = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.12, 12), cupMat);
                    cup.position.set(0.2, 0.43, 1.2);
                    group.add(cup);

                    this.scene.add(group);
                },

                buildServerRack(x, y, z) {
                    const rackMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.5, metalness: 0.6 });
                    const rack = new THREE.Mesh(new THREE.BoxGeometry(1.2, 3.2, 0.9), rackMat);
                    rack.position.set(x, 1.6, z);
                    rack.castShadow = true;
                    this.scene.add(rack);

                    // LEDs
                    const ledMat1 = new THREE.MeshBasicMaterial({ color: 0x10b981 });
                    const ledMat2 = new THREE.MeshBasicMaterial({ color: 0x38bdf8 });
                    for (let i = 0; i < 4; i++) {
                        const led1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, 0.02), ledMat1);
                        led1.position.set(x - 0.3, 1.0 + (i * 0.4), z + 0.46);
                        this.scene.add(led1);

                        const led2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, 0.02), ledMat2);
                        led2.position.set(x + 0.3, 1.0 + (i * 0.4), z + 0.46);
                        this.scene.add(led2);
                    }
                },

                buildCharacter(shirtColor, hairColor) {
                    const char = new THREE.Group();

                    // Head
                    const headGeo = new THREE.BoxGeometry(0.38, 0.38, 0.35);
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.7 });
                    const head = new THREE.Mesh(headGeo, skinMat);
                    head.position.y = 0.82;
                    head.castShadow = true;
                    char.add(head);

                    // Hair
                    const hairGeo = new THREE.BoxGeometry(0.4, 0.14, 0.38);
                    const hairMat = new THREE.MeshStandardMaterial({ color: hairColor });
                    const hair = new THREE.Mesh(hairGeo, hairMat);
                    hair.position.set(0, 0.98, -0.01);
                    char.add(hair);

                    // Eyes
                    const eyeMat = new THREE.MeshBasicMaterial({ color: 0x111827 });
                    const eyeL = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, 0.02), eyeMat);
                    eyeL.position.set(-0.09, 0.83, 0.18);
                    const eyeR = eyeL.clone();
                    eyeR.position.x = 0.09;
                    char.add(eyeL);
                    char.add(eyeR);

                    // Torso (Shirt)
                    const torsoGeo = new THREE.BoxGeometry(0.42, 0.44, 0.28);
                    const shirtMat = new THREE.MeshStandardMaterial({ color: shirtColor });
                    const torso = new THREE.Mesh(torsoGeo, shirtMat);
                    torso.position.y = 0.45;
                    torso.castShadow = true;
                    char.add(torso);

                    // Arms
                    const armGeo = new THREE.BoxGeometry(0.1, 0.35, 0.12);
                    const armL = new THREE.Mesh(armGeo, skinMat);
                    armL.position.set(-0.27, 0.43, 0.05);
                    armL.rotation.x = 0.3;
                    const armR = new THREE.Mesh(armGeo, skinMat);
                    armR.position.set(0.27, 0.43, 0.05);
                    armR.rotation.x = 0.3;
                    char.add(armL);
                    char.add(armR);

                    char.userData = { armL, armR, head, baseHeadY: 0.82 };
                    return char;
                },

                animate() {
                    requestAnimationFrame(() => this.animate());

                    const time = this.clock.getElapsedTime();

                    // Subtle typing / breathing animation
                    if (this.csGroup && config.csStatus === 'working') {
                        const data = this.csGroup.userData;
                        data.armL.rotation.x = 0.4 + Math.sin(time * 12) * 0.15;
                        data.armR.rotation.x = 0.4 + Math.cos(time * 12) * 0.15;
                        data.head.position.y = data.baseHeadY + Math.sin(time * 3) * 0.015;
                    }

                    if (this.coreGroup) {
                        const data = this.coreGroup.userData;
                        data.armL.rotation.x = 0.4 + Math.cos(time * 10) * 0.12;
                        data.armR.rotation.x = 0.4 + Math.sin(time * 10) * 0.12;
                    }

                    // Screen glow pulse
                    this.screenMeshes.forEach((mesh, idx) => {
                        const intensity = 0.85 + Math.sin(time * 4 + idx) * 0.15;
                        mesh.material.color.setRGB(0.22 * intensity, 0.74 * intensity, 0.97 * intensity);
                    });

                    this.renderer.render(this.scene, this.camera);
                }
            }));
        });
    </script>

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
