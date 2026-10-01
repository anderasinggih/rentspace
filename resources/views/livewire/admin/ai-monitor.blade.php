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
                    <span>Dewi Istirahat</span>
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

                // Posisi koordinat penting di ruangan
                spots: {
                    dewiDesk: { x: -3.8, y: 0.48, z: 0.3, rotY: 0 },
                    dewiLounge: { x: 3.5, y: 0.45, z: -0.1, rotY: -0.4 },
                    singgihDesk: { x: -0.9, y: 0.48, z: 0.3, rotY: 0 },
                    anderaDesk: { x: 2.0, y: 0.48, z: 0.3, rotY: 0 }
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

                init(container, initialStatus) {
                    if (!container) return;
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
                            height = rect.height || 480;
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

                        // 1. Scene & Warm Modern Aesthetics
                        this.scene = new THREE.Scene();
                        this.scene.background = new THREE.Color(0x131b18);
                        this.scene.fog = new THREE.FogExp2(0x131b18, 0.022);

                        // 2. Camera Isometric Perspective
                        const aspect = width / height;
                        this.camera = new THREE.PerspectiveCamera(36, aspect, 0.1, 100);
                        this.updateCameraPos();

                        // 3. WebGL Renderer with Shadows
                        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
                        this.renderer.setSize(width, height);
                        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
                        this.renderer.shadowMap.enabled = true;
                        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
                        this.renderer.outputEncoding = THREE.sRGBEncoding;
                        container.appendChild(this.renderer.domElement);

                        // 4. Studio Lighting (Warm, Vibrant, Cute & Real)
                        const ambient = new THREE.AmbientLight(0xfff6ea, 0.95);
                        this.scene.add(ambient);

                        const sunLight = new THREE.DirectionalLight(0xfff8e7, 1.4);
                        sunLight.position.set(10, 16, 12);
                        sunLight.castShadow = true;
                        sunLight.shadow.mapSize.width = 1024;
                        sunLight.shadow.mapSize.height = 1024;
                        sunLight.shadow.camera.near = 0.5;
                        sunLight.shadow.camera.far = 40;
                        sunLight.shadow.bias = -0.001;
                        this.scene.add(sunLight);

                        // Lampu aksen hangat & pastel cerah
                        const pinkAccent = new THREE.PointLight(0xf472b6, 1.6, 12);
                        pinkAccent.position.set(-3.8, 3.5, 1.5);
                        this.scene.add(pinkAccent);

                        const tealAccent = new THREE.PointLight(0x2dd4bf, 1.4, 12);
                        tealAccent.position.set(-0.9, 3.5, 1.5);
                        this.scene.add(tealAccent);

                        const warmLounge = new THREE.PointLight(0xfbbf24, 1.2, 14);
                        warmLounge.position.set(4.5, 3.0, 1.0);
                        this.scene.add(warmLounge);

                        // 5. Inisialisasi Dynamic Animated Screen Texture (Live Code/Chat Matrix)
                        this.initAnimatedScreenCanvas();

                        // 6. Build 3D Studio & Characters
                        this.buildRoom();

                        // Posisi awal Dewi
                        if (this.dewiGroup) {
                            const initSpot = (this.currentCsStatus === 'working') ? this.spots.dewiDesk : this.spots.dewiLounge;
                            this.dewiGroup.position.set(initSpot.x, initSpot.y, initSpot.z);
                            this.dewiGroup.rotation.y = initSpot.rotY;
                        }

                        this.renderer.render(this.scene, this.camera);

                        // 7. Mouse / Touch Controls
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
                            this.rotY -= dx * 0.005;
                            this.rotX = Math.max(0.18, Math.min(0.70, this.rotX + dy * 0.004));
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
                            this.rotX = Math.max(0.18, Math.min(0.70, this.rotX + dy * 0.005));
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
                    const radius = 16.8;
                    this.camera.position.x = radius * Math.sin(this.rotY) * Math.cos(this.rotX);
                    this.camera.position.y = radius * Math.sin(this.rotX) + 2.0;
                    this.camera.position.z = radius * Math.cos(this.rotY) * Math.cos(this.rotX);
                    this.camera.lookAt(0, 0.8, 0);
                },

                // Trigger perpindahan jalan Dewi (NPC Walking) antara Meja dan Sofa
                updateCsPosition(status) {
                    if (this.currentCsStatus === status && !this.dewiWalk.isMoving) return;
                    this.currentCsStatus = status;

                    if (!this.dewiGroup) return;

                    const target = (status === 'working') ? this.spots.dewiDesk : this.spots.dewiLounge;
                    
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

                // Update teks balon percakapan live
                updateLiveBubble(agent, text) {
                    if (!this.bubbles[agent]) return;
                    const b = this.bubbles[agent];
                    if (!text) {
                        b.mesh.visible = false;
                        return;
                    }
                    const ctx = b.canvas.getContext('2d');
                    ctx.clearRect(0, 0, b.canvas.width, b.canvas.height);

                    // Gambar bubble chat bulat lucu
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
                    ctx.strokeStyle = agent === 'dewi' ? '#f472b6' : (agent === 'singgih' ? '#2dd4bf' : '#fbbf24');
                    ctx.lineWidth = 6;
                    
                    // Rounded rect
                    const w = b.canvas.width - 20;
                    const h = b.canvas.height - 35;
                    ctx.beginPath();
                    ctx.roundRect(10, 10, w, h, 24);
                    ctx.fill();
                    ctx.stroke();

                    // Tail segitiga
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
                    ctx.beginPath();
                    ctx.moveTo(w / 2 - 15, 10 + h);
                    ctx.lineTo(w / 2, 10 + h + 20);
                    ctx.lineTo(w / 2 + 15, 10 + h);
                    ctx.closePath();
                    ctx.fill();

                    // Teks bubble
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 26px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    const cleanText = text.length > 28 ? text.substring(0, 26) + '...' : text;
                    ctx.fillText('💬 ' + cleanText, b.canvas.width / 2, (h / 2) + 10);

                    b.texture.needsUpdate = true;
                    b.mesh.visible = true;
                },

                // Membuat Label Nama 3D Melayang di atas kepala karakter
                createNameTag(name, role, badgeColor) {
                    const canvas = document.createElement('canvas');
                    canvas.width = 384;
                    canvas.height = 120;
                    const ctx = canvas.getContext('2d');

                    // Background pill badge
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.88)';
                    ctx.strokeStyle = badgeColor;
                    ctx.lineWidth = 5;
                    ctx.beginPath();
                    ctx.roundRect(10, 10, canvas.width - 20, canvas.height - 20, 32);
                    ctx.fill();
                    ctx.stroke();

                    // Indicator dot
                    ctx.fillStyle = badgeColor;
                    ctx.beginPath();
                    ctx.arc(42, 60, 12, 0, Math.PI * 2);
                    ctx.fill();

                    // Text Name & Role
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 36px sans-serif';
                    ctx.textAlign = 'left';
                    ctx.fillText(name, 72, 54);

                    ctx.fillStyle = badgeColor;
                    ctx.font = 'bold 22px sans-serif';
                    ctx.fillText(role, 72, 88);

                    const texture = new THREE.CanvasTexture(canvas);
                    const mat = new THREE.SpriteMaterial({ map: texture, transparent: true });
                    const sprite = new THREE.Sprite(mat);
                    sprite.scale.set(1.4, 0.44, 1);
                    sprite.position.y = 1.38;
                    return sprite;
                },

                // Membuat Speech Bubble Sprite
                createSpeechBubble() {
                    const canvas = document.createElement('canvas');
                    canvas.width = 440;
                    canvas.height = 140;
                    const texture = new THREE.CanvasTexture(canvas);
                    const mat = new THREE.SpriteMaterial({ map: texture, transparent: true });
                    const sprite = new THREE.Sprite(mat);
                    sprite.scale.set(1.8, 0.58, 1);
                    sprite.position.y = 1.95;
                    sprite.visible = false;
                    return { mesh: sprite, canvas, texture };
                },

                // Karakter Dewi (Cewek Cantik: Rambut Panjang Cokelat-Caramel, Baju Pink Manis)
                buildDewiCharacter() {
                    const char = new THREE.Group();

                    // Head
                    const headGeo = new THREE.BoxGeometry(0.36, 0.36, 0.34);
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfed7aa, roughness: 0.6 });
                    const head = new THREE.Mesh(headGeo, skinMat);
                    head.position.y = 0.82;
                    head.castShadow = true;
                    char.add(head);

                    // Rambut Panjang Cantik Dewi
                    const hairMat = new THREE.MeshStandardMaterial({ color: 0x5c2e14, roughness: 0.8 }); // Brown hazelnut
                    const hairTop = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.16, 0.38), hairMat);
                    hairTop.position.set(0, 0.98, 0);
                    char.add(hairTop);

                    // Helai rambut panjang samping & belakang
                    const hairBack = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.58, 0.14), hairMat);
                    hairBack.position.set(0, 0.72, -0.16);
                    char.add(hairBack);

                    const hairL = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.48, 0.22), hairMat);
                    hairL.position.set(-0.20, 0.76, 0.05);
                    char.add(hairL);

                    const hairR = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.48, 0.22), hairMat);
                    hairR.position.set(0.20, 0.76, 0.05);
                    char.add(hairR);

                    // Eyes (Lucu & Ramah)
                    const eyeMat = new THREE.MeshBasicMaterial({ color: 0x1e293b });
                    const eyeL = new THREE.Mesh(new THREE.BoxGeometry(0.045, 0.05, 0.02), eyeMat);
                    eyeL.position.set(-0.09, 0.83, 0.175);
                    const eyeR = eyeL.clone();
                    eyeR.position.x = 0.09;
                    char.add(eyeL);
                    char.add(eyeR);

                    // Pipi Blush Pink Manis
                    const blushMat = new THREE.MeshBasicMaterial({ color: 0xf472b6 });
                    const blushL = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.025, 0.01), blushMat);
                    blushL.position.set(-0.11, 0.77, 0.176);
                    const blushR = blushL.clone();
                    blushR.position.x = 0.11;
                    char.add(blushL);
                    char.add(blushR);

                    // Torso: Baju Pink Cantik
                    const shirtMat = new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.6 }); // Pastel Lovely Pink
                    const torso = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.44, 0.26), shirtMat);
                    torso.position.y = 0.45;
                    torso.castShadow = true;
                    char.add(torso);

                    // Arms
                    const armGeo = new THREE.BoxGeometry(0.09, 0.34, 0.11);
                    const armL = new THREE.Mesh(armGeo, skinMat);
                    armL.position.set(-0.24, 0.43, 0.04);
                    const armR = new THREE.Mesh(armGeo, skinMat);
                    armR.position.set(0.24, 0.43, 0.04);
                    char.add(armL);
                    char.add(armR);

                    // Legs / Kaki saat jalan
                    const legGeo = new THREE.BoxGeometry(0.12, 0.38, 0.14);
                    const pantsMat = new THREE.MeshStandardMaterial({ color: 0x334155 });
                    const legL = new THREE.Mesh(legGeo, pantsMat);
                    legL.position.set(-0.10, 0.19, 0);
                    const legR = new THREE.Mesh(legGeo, pantsMat);
                    legR.position.set(0.10, 0.19, 0);
                    char.add(legL);
                    char.add(legR);

                    // Name Tag "Dewi (CS Customer)"
                    const nameTag = this.createNameTag('Dewi', 'CS Customer Chat', '#f472b6');
                    char.add(nameTag);

                    // Speech Bubble
                    this.bubbles.dewi = this.createSpeechBubble();
                    char.add(this.bubbles.dewi.mesh);

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.82 };
                    return char;
                },

                // Karakter Pria (Singgih & Andera)
                buildMaleCharacter(name, role, shirtColor, hairColor, badgeColor, bubbleKey) {
                    const char = new THREE.Group();

                    // Head
                    const headGeo = new THREE.BoxGeometry(0.38, 0.38, 0.35);
                    const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.7 });
                    const head = new THREE.Mesh(headGeo, skinMat);
                    head.position.y = 0.82;
                    head.castShadow = true;
                    char.add(head);

                    // Hair
                    const hairMat = new THREE.MeshStandardMaterial({ color: hairColor });
                    const hair = new THREE.Mesh(new THREE.BoxGeometry(0.40, 0.14, 0.38), hairMat);
                    hair.position.set(0, 0.98, -0.01);
                    char.add(hair);

                    // Eyes
                    const eyeMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });
                    const eyeL = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, 0.02), eyeMat);
                    eyeL.position.set(-0.09, 0.83, 0.18);
                    const eyeR = eyeL.clone();
                    eyeR.position.x = 0.09;
                    char.add(eyeL);
                    char.add(eyeR);

                    // Torso
                    const shirtMat = new THREE.MeshStandardMaterial({ color: shirtColor });
                    const torso = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.44, 0.28), shirtMat);
                    torso.position.y = 0.45;
                    torso.castShadow = true;
                    char.add(torso);

                    // Arms
                    const armGeo = new THREE.BoxGeometry(0.10, 0.35, 0.12);
                    const armL = new THREE.Mesh(armGeo, skinMat);
                    armL.position.set(-0.27, 0.43, 0.05);
                    const armR = new THREE.Mesh(armGeo, skinMat);
                    armR.position.set(0.27, 0.43, 0.05);
                    char.add(armL);
                    char.add(armR);

                    // Legs
                    const pantsMat = new THREE.MeshStandardMaterial({ color: 0x1e293b });
                    const legGeo = new THREE.BoxGeometry(0.13, 0.38, 0.14);
                    const legL = new THREE.Mesh(legGeo, pantsMat);
                    legL.position.set(-0.11, 0.19, 0);
                    const legR = new THREE.Mesh(legGeo, pantsMat);
                    legR.position.set(0.11, 0.19, 0);
                    char.add(legL);
                    char.add(legR);

                    // Name Tag
                    const nameTag = this.createNameTag(name, role, badgeColor);
                    char.add(nameTag);

                    // Speech Bubble
                    this.bubbles[bubbleKey] = this.createSpeechBubble();
                    char.add(this.bubbles[bubbleKey].mesh);

                    char.userData = { armL, armR, legL, legR, head, baseHeadY: 0.82 };
                    return char;
                },

                // Meja Kerja Lengkap dengan Kursi Ergonomis, Monitor, PC, Keyboard, Mouse, Mug Kopi
                buildWorkDesk(x, y, z, deskLabel, colorTheme) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Top Table (Warm Teak Wood Finish)
                    const topGeo = new THREE.BoxGeometry(2.3, 0.1, 1.25);
                    const topMat = new THREE.MeshStandardMaterial({ color: 0x4a3222, roughness: 0.5 });
                    const top = new THREE.Mesh(topGeo, topMat);
                    top.position.y = 0.95;
                    top.castShadow = true;
                    top.receiveShadow = true;
                    group.add(top);

                    // Meja Legs (Matte Black Metal Frame)
                    const legMat = new THREE.MeshStandardMaterial({ color: 0x18181b, metalness: 0.8, roughness: 0.3 });
                    const legGeo = new THREE.CylinderGeometry(0.038, 0.038, 0.95, 12);
                    [[-1.02, -0.52], [1.02, -0.52], [-1.02, 0.52], [1.02, 0.52]].forEach(([lx, lz]) => {
                        const leg = new THREE.Mesh(legGeo, legMat);
                        leg.position.set(lx, 0.475, lz);
                        leg.castShadow = true;
                        group.add(leg);
                    });

                    // Desk Leather Pad
                    const padMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.8 });
                    const pad = new THREE.Mesh(new THREE.BoxGeometry(1.65, 0.015, 0.72), padMat);
                    pad.position.set(0, 1.01, 0.05);
                    group.add(pad);

                    // Curved Ultra-Wide Monitor Frame
                    const screenFrameGeo = new THREE.BoxGeometry(1.12, 0.65, 0.04);
                    const screenFrameMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.2 });
                    const screenFrame = new THREE.Mesh(screenFrameGeo, screenFrameMat);
                    screenFrame.position.set(0, 1.50, 0.36);
                    screenFrame.castShadow = true;
                    group.add(screenFrame);

                    // Animated Live Computer Screen Texture
                    const displayGeo = new THREE.PlaneGeometry(1.06, 0.58);
                    const displayMat = new THREE.MeshBasicMaterial({ map: this.screenTexture });
                    const display = new THREE.Mesh(displayGeo, displayMat);
                    display.position.set(0, 1.50, 0.338);
                    display.rotation.y = Math.PI;
                    group.add(display);
                    this.screenMeshes.push(display);

                    // Monitor Stand & Base
                    const stand = new THREE.Mesh(new THREE.CylinderGeometry(0.025, 0.025, 0.45, 8), screenFrameMat);
                    stand.position.set(0, 1.18, 0.36);
                    group.add(stand);

                    const standBase = new THREE.Mesh(new THREE.BoxGeometry(0.32, 0.02, 0.22), screenFrameMat);
                    standBase.position.set(0, 1.02, 0.36);
                    group.add(standBase);

                    // PC Gaming Tower Case with RGB Strip
                    const pcCaseMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.2, metalness: 0.6 });
                    const pcCase = new THREE.Mesh(new THREE.BoxGeometry(0.30, 0.58, 0.52), pcCaseMat);
                    pcCase.position.set(0.92, 1.29, 0.22);
                    pcCase.castShadow = true;
                    group.add(pcCase);

                    const fanGlow = new THREE.Mesh(new THREE.CircleGeometry(0.09, 16), new THREE.MeshBasicMaterial({ color: colorTheme }));
                    fanGlow.position.set(0.92, 1.29, -0.045);
                    group.add(fanGlow);

                    // Keyboard RGB & Mouse
                    const kbMat = new THREE.MeshStandardMaterial({ color: 0x27272a, roughness: 0.7 });
                    const kb = new THREE.Mesh(new THREE.BoxGeometry(0.68, 0.03, 0.22), kbMat);
                    kb.position.set(0, 1.03, 0.05);
                    group.add(kb);

                    const mouse = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.035, 0.13), kbMat);
                    mouse.position.set(0.50, 1.03, 0.05);
                    group.add(mouse);

                    // Mug Kopi Lucu di Meja
                    const mugMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.3 });
                    const mug = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.055, 0.13, 12), mugMat);
                    mug.position.set(-0.65, 1.07, 0.15);
                    group.add(mug);

                    // Kursi Kerja Ergonomis Nyata (Modern Mesh Chair)
                    const chairMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.8 });
                    const chairSeat = new THREE.Mesh(new THREE.BoxGeometry(0.62, 0.08, 0.62), chairMat);
                    chairSeat.position.set(0, 0.54, -0.32);
                    chairSeat.castShadow = true;
                    group.add(chairSeat);

                    const chairBack = new THREE.Mesh(new THREE.BoxGeometry(0.56, 0.68, 0.08), chairMat);
                    chairBack.position.set(0, 0.90, -0.61);
                    chairBack.castShadow = true;
                    group.add(chairBack);

                    // Armrest kursi
                    const armrestGeo = new THREE.BoxGeometry(0.08, 0.28, 0.42);
                    const armL = new THREE.Mesh(armrestGeo, legMat);
                    armL.position.set(-0.32, 0.72, -0.32);
                    const armR = new THREE.Mesh(armrestGeo, legMat);
                    armR.position.set(0.32, 0.72, -0.32);
                    group.add(armL);
                    group.add(armR);

                    // Tiang kursi & roda bintang
                    const chairStem = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.035, 0.52, 8), legMat);
                    chairStem.position.set(0, 0.26, -0.32);
                    group.add(chairStem);

                    const chairBase = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.28, 0.03, 5), legMat);
                    chairBase.position.set(0, 0.04, -0.32);
                    group.add(chairBase);

                    this.scene.add(group);
                },

                // Lounge Area Istirahat (Sofa Lembut, Meja Kopi, Tanaman Hias)
                buildLounge(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    // Sofa Modern L-Shape / Cozy Fabric Sofa
                    const sofaMat = new THREE.MeshStandardMaterial({ color: 0x2563eb, roughness: 0.85 }); // Royal Cozy Blue
                    const seatBase = new THREE.Mesh(new THREE.BoxGeometry(3.2, 0.46, 1.3), sofaMat);
                    seatBase.position.set(0, 0.35, 0);
                    seatBase.castShadow = true;
                    group.add(seatBase);

                    const seatBack = new THREE.Mesh(new THREE.BoxGeometry(3.2, 0.80, 0.30), sofaMat);
                    seatBack.position.set(0, 0.85, -0.48);
                    seatBack.castShadow = true;
                    group.add(seatBack);

                    const armL = new THREE.Mesh(new THREE.BoxGeometry(0.30, 0.65, 1.3), sofaMat);
                    armL.position.set(-1.6, 0.58, 0);
                    group.add(armL);

                    const armR = new THREE.Mesh(new THREE.BoxGeometry(0.30, 0.65, 1.3), sofaMat);
                    armR.position.set(1.6, 0.58, 0);
                    group.add(armR);

                    // Bantal Sofa Pastel Lucu
                    const pillowMat1 = new THREE.MeshStandardMaterial({ color: 0xf472b6, roughness: 0.9 });
                    const pillow1 = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.45, 0.18), pillowMat1);
                    pillow1.position.set(-1.1, 0.70, -0.32);
                    pillow1.rotation.z = 0.15;
                    group.add(pillow1);

                    const pillowMat2 = new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.9 });
                    const pillow2 = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.45, 0.18), pillowMat2);
                    pillow2.position.set(1.1, 0.70, -0.32);
                    pillow2.rotation.z = -0.15;
                    group.add(pillow2);

                    // Coffee Table
                    const tableMat = new THREE.MeshStandardMaterial({ color: 0x78350f, roughness: 0.6 });
                    const table = new THREE.Mesh(new THREE.BoxGeometry(1.8, 0.36, 0.85), tableMat);
                    table.position.set(0, 0.22, 1.4);
                    table.castShadow = true;
                    group.add(table);

                    // Majalah & Snack di Meja
                    const magMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0 });
                    const mag = new THREE.Mesh(new THREE.BoxGeometry(0.35, 0.02, 0.45), magMat);
                    mag.position.set(-0.4, 0.41, 1.4);
                    group.add(mag);

                    // Karpet Bulu Estetik
                    const rugMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.95 });
                    const rug = new THREE.Mesh(new THREE.BoxGeometry(3.6, 0.02, 2.6), rugMat);
                    rug.position.set(0, 0.01, 0.7);
                    group.add(rug);

                    this.scene.add(group);
                },

                // Tulisan Neon 3D di Tembok: "RENTSPACE" Modern & Estetik
                buildWallBranding() {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1024;
                    canvas.height = 256;
                    const ctx = canvas.getContext('2d');

                    // Latar belakang transparan
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Glow effect
                    ctx.shadowColor = '#10b981';
                    ctx.shadowBlur = 24;

                    // Neon Sign "RENTSPACE"
                    ctx.fillStyle = '#10b981';
                    ctx.font = '900 86px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText('⚡ RENTSPACE STUDIO', canvas.width / 2, 110);

                    ctx.shadowBlur = 10;
                    ctx.fillStyle = '#38bdf8';
                    ctx.font = 'bold 36px sans-serif';
                    ctx.fillText('OFFICIAL AI & CUSTOMER MISSION CONTROL', canvas.width / 2, 175);

                    const texture = new THREE.CanvasTexture(canvas);
                    const mat = new THREE.MeshBasicMaterial({ map: texture, transparent: true });
                    const plane = new THREE.Mesh(new THREE.PlaneGeometry(6.8, 1.7), mat);
                    plane.position.set(0, 3.4, -4.28);
                    this.scene.add(plane);
                },

                // Dispenser Air Minum & Coffee Station
                buildWaterDispenser(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    const bodyMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.3 });
                    const body = new THREE.Mesh(new THREE.BoxGeometry(0.5, 1.2, 0.5), bodyMat);
                    body.position.y = 0.6;
                    body.castShadow = true;
                    group.add(body);

                    const gallonMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.75, roughness: 0.1 });
                    const gallon = new THREE.Mesh(new THREE.CylinderGeometry(0.20, 0.20, 0.55, 16), gallonMat);
                    gallon.position.y = 1.45;
                    group.add(gallon);

                    this.scene.add(group);
                },

                // Rak Buku / Lemari File
                buildBookshelf(x, y, z) {
                    const group = new THREE.Group();
                    group.position.set(x, y, z);

                    const woodMat = new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.7 });
                    const shelf = new THREE.Mesh(new THREE.BoxGeometry(1.6, 2.6, 0.55), woodMat);
                    shelf.position.y = 1.3;
                    shelf.castShadow = true;
                    group.add(shelf);

                    // Buku-buku warna-warni lucu
                    const colors = [0xf43f5e, 0x3b82f6, 0x10b981, 0xf59e0b, 0x8b5cf6];
                    for (let row = 0; row < 3; row++) {
                        for (let col = 0; col < 6; col++) {
                            const bMat = new THREE.MeshStandardMaterial({ color: colors[(row + col) % colors.length] });
                            const book = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.38, 0.32), bMat);
                            book.position.set(-0.55 + col * 0.18, 0.6 + row * 0.75, 0.10);
                            group.add(book);
                        }
                    }

                    this.scene.add(group);
                },

                // Tanaman Hias Monsterra Pot
                buildPlant(x, y, z) {
                    const plantGroup = new THREE.Group();
                    plantGroup.position.set(x, y, z);

                    const potMat = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.4 });
                    const pot = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.22, 0.55, 16), potMat);
                    pot.position.y = 0.275;
                    pot.castShadow = true;
                    plantGroup.add(pot);

                    const leafMat = new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.6 });
                    for (let i = 0; i < 7; i++) {
                        const leaf = new THREE.Mesh(new THREE.SphereGeometry(0.28, 8, 8), leafMat);
                        leaf.scale.set(0.65, 1.8, 0.65);
                        leaf.position.set(Math.sin(i * 1.1) * 0.22, 0.65 + i * 0.11, Math.cos(i * 1.1) * 0.22);
                        leaf.rotation.x = Math.sin(i) * 0.3;
                        plantGroup.add(leaf);
                    }

                    this.scene.add(plantGroup);
                },

                buildServerRack(x, y, z) {
                    const rackMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.4, metalness: 0.7 });
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

                buildRoom() {
                    // Lantai Vinyl Kayu Hangat Estetik
                    const floorGeo = new THREE.PlaneGeometry(19, 13);
                    const floorMat = new THREE.MeshStandardMaterial({ color: 0x1e2722, roughness: 0.8 });
                    const floor = new THREE.Mesh(floorGeo, floorMat);
                    floor.rotation.x = -Math.PI / 2;
                    floor.receiveShadow = true;
                    this.scene.add(floor);

                    const grid = new THREE.GridHelper(19, 19, 0x2e4238, 0x22322a);
                    grid.position.y = 0.01;
                    this.scene.add(grid);

                    // Tembok Belakang Studio
                    const wallGeo = new THREE.BoxGeometry(19, 5.2, 0.4);
                    const wallMat = new THREE.MeshStandardMaterial({ color: 0x131a17, roughness: 0.95 });
                    const wall = new THREE.Mesh(wallGeo, wallMat);
                    wall.position.set(0, 2.6, -4.5);
                    wall.receiveShadow = true;
                    this.scene.add(wall);

                    // Wall Branding Tulisan "RENTSPACE"
                    this.buildWallBranding();

                    // Perabot & Dekorasi Kamar Lengkap
                    this.buildServerRack(-7.2, 0, -3.8);
                    this.buildBookshelf(7.2, 0, -3.8);
                    this.buildWaterDispenser(7.2, 0, 1.2);

                    this.buildPlant(-7.2, 0, 1.5);
                    this.buildPlant(5.8, 0, -3.8);
                    this.buildPlant(-2.3, 0, -3.8);

                    // 1. Meja CS Customer (Dewi)
                    this.buildWorkDesk(-3.8, 0, 0.8, 'Dewi CS Desk', 0xf472b6);

                    // 2. Meja Core Dispatcher (Singgih)
                    this.buildWorkDesk(-0.9, 0, 0.8, 'Singgih Core Desk', 0x2dd4bf);

                    // 3. Meja Tim Report (Andera)
                    this.buildWorkDesk(2.0, 0, 0.8, 'Andera Report Desk', 0xfbbf24);

                    // 4. Area Santai Lounge (Sofa Break)
                    this.buildLounge(4.5, 0, 1.8);

                    // 5. Spawn Karakter 3D
                    // DEWI (CS Customer: Baju Pink Cantik, Rambut Panjang)
                    this.dewiGroup = this.buildDewiCharacter();
                    this.scene.add(this.dewiGroup);

                    // SINGGIH (Core Dispatcher)
                    this.singgihGroup = this.buildMaleCharacter('Singgih', 'Core Dispatcher', 0x0d9488, 0x1e1b4b, '#2dd4bf', 'singgih');
                    this.singgihGroup.position.set(this.spots.singgihDesk.x, this.spots.singgihDesk.y, this.spots.singgihDesk.z);
                    this.scene.add(this.singgihGroup);

                    // ANDERA (Report & Finance)
                    this.anderaGroup = this.buildMaleCharacter('Andera', 'Report & Finance', 0xd97706, 0x451a03, '#fbbf24', 'andera');
                    this.anderaGroup.position.set(this.spots.anderaDesk.x, this.spots.anderaDesk.y, this.spots.anderaDesk.z);
                    this.scene.add(this.anderaGroup);
                },

                animate() {
                    this.animationFrameId = requestAnimationFrame(() => this.animate());

                    if (!this.renderer || !this.scene || !this.camera) return;

                    const time = this.clock ? this.clock.getElapsedTime() : 0;
                    const delta = 0.016; // approx frame time

                    // A. Update Layar Komputer Live (Simulasi coding terminal & live chat bubble)
                    if (this.screenCtx && this.screenTexture) {
                        const ctx = this.screenCtx;
                        ctx.fillStyle = '#090d16';
                        ctx.fillRect(0, 0, 512, 280);

                        // Top bar OS
                        ctx.fillStyle = '#1e293b';
                        ctx.fillRect(0, 0, 512, 34);
                        ctx.fillStyle = '#ef4444';
                        ctx.beginPath(); ctx.arc(20, 17, 6, 0, Math.PI * 2); ctx.fill();
                        ctx.fillStyle = '#f59e0b';
                        ctx.beginPath(); ctx.arc(40, 17, 6, 0, Math.PI * 2); ctx.fill();
                        ctx.fillStyle = '#10b981';
                        ctx.beginPath(); ctx.arc(60, 17, 6, 0, Math.PI * 2); ctx.fill();

                        ctx.fillStyle = '#94a3b8';
                        ctx.font = 'bold 15px monospace';
                        ctx.fillText('RentSpace Core OS · Live Terminal', 85, 22);

                        // Simulated Chat Streams & Code Bars
                        const wave = Math.sin(time * 6);
                        for (let i = 0; i < 7; i++) {
                            const barW = 120 + Math.sin(time * 3 + i * 1.5) * 80;
                            const isGreen = (i % 2 === 0);
                            ctx.fillStyle = isGreen ? '#10b981' : '#38bdf8';
                            ctx.fillRect(25, 55 + i * 28, Math.max(60, barW), 14);

                            ctx.fillStyle = '#475569';
                            ctx.fillRect(25 + barW + 15, 55 + i * 28, 80, 14);
                        }

                        // Status pill running
                        ctx.fillStyle = wave > 0 ? '#10b981' : '#059669';
                        ctx.fillRect(360, 240, 125, 25);
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 13px sans-serif';
                        ctx.fillText('● SYSTEM READY', 372, 257);

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
                            if (this.dewiGroup.userData.legL) this.dewiGroup.userData.legL.rotation.x = 0;
                            if (this.dewiGroup.userData.legR) this.dewiGroup.userData.legR.rotation.x = 0;
                        }
                    } else if (this.dewiGroup) {
                        // Posisi diam (Ngetik di meja ATAU bersantai santai di sofa)
                        const data = this.dewiGroup.userData;
                        if (this.currentCsStatus === 'working') {
                            // Dewi ngetik aktif di keyboard
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.5 + Math.sin(time * 14) * 0.22;
                                data.armR.rotation.x = 0.5 + Math.cos(time * 14) * 0.22;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 3.5) * 0.015;
                            }
                        } else {
                            // Dewi bersantai di sofa (tangan santai, kepala breathing tenang)
                            if (data.armL && data.armR) {
                                data.armL.rotation.x = 0.15 + Math.sin(time * 2) * 0.05;
                                data.armR.rotation.x = 0.15 - Math.sin(time * 2) * 0.05;
                            }
                            if (data.head) {
                                data.head.position.y = data.baseHeadY + Math.sin(time * 2) * 0.012;
                            }
                        }
                    }

                    // C. Animasi Singgih (Core Dispatcher mengetik konstan)
                    if (this.singgihGroup) {
                        const data = this.singgihGroup.userData;
                        if (data && data.armL && data.armR) {
                            data.armL.rotation.x = 0.45 + Math.cos(time * 11) * 0.16;
                            data.armR.rotation.x = 0.45 + Math.sin(time * 11) * 0.16;
                        }
                    }

                    // D. Animasi Andera (Report Bot mengetik & memantau)
                    if (this.anderaGroup) {
                        const data = this.anderaGroup.userData;
                        if (data && data.armL && data.armR) {
                            data.armL.rotation.x = 0.40 + Math.sin(time * 8) * 0.14;
                            data.armR.rotation.x = 0.40 + Math.cos(time * 8) * 0.14;
                        }
                    }

                    this.renderer.render(this.scene, this.camera);
                }
            };
        </script>

        <div class="relative w-full h-[440px] sm:h-[490px] bg-[#131b18] overflow-hidden" 
             x-data="{
                 init() {
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
                     window._threeOfficeApp.updateCsPosition(detail.csStatus);
                     if (detail.customerBubble) {
                         window._threeOfficeApp.updateLiveBubble('dewi', detail.customerBubble);
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
