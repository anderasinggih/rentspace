<div class="h-[calc(100vh-160px)] flex flex-col" x-data="radarMap()">
    {{-- Top Header --}}
    <div class="flex items-center justify-between mb-4 shrink-0">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-bold tracking-tight">Radar</h1>
            <span class="h-4 w-px bg-border"></span>
            <p class="text-[11px] text-muted-foreground hidden sm:block">Live device tracking</p>
        </div>
        <div class="flex items-center gap-1 bg-muted/50 p-1 rounded-lg border border-border">
            <a href="{{ route('admin.monitoring') }}" class="px-3 py-1.5 text-[10px] font-medium hover:bg-background rounded-md transition-all">Monitoring</a>
            <button class="px-3 py-1.5 text-[10px] font-medium bg-background border border-border shadow-xs rounded-md">Radar</button>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="flex-1 flex flex-col lg:flex-row gap-4 min-h-0 pb-4">
        {{-- Device List: Horizontal on Mobile, Sidebar on Desktop --}}
        <div class="w-full lg:w-72 shrink-0">
            <div class="bg-card border border-border rounded-xl flex flex-col h-full overflow-hidden">
                <div class="p-3 border-b border-border bg-muted/20 hidden lg:block">
                    <p class="text-[10px] font-bold text-muted-foreground">Aktif ({{ count($devices) }})</p>
                </div>
                {{-- Scroll Container --}}
                <div class="flex lg:flex-col overflow-x-auto lg:overflow-y-auto p-1.5 gap-1.5 scrollbar-hide">
                    @forelse($devices as $device)
                        <button 
                            @click="focusDevice({{ json_encode($device) }})"
                            class="flex-shrink-0 w-48 lg:w-full text-left p-2.5 rounded-lg border border-transparent hover:bg-muted transition-all"
                            :class="selectedId === {{ $device['id'] }} ? 'bg-muted border-border' : ''"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold truncate leading-none">{{ $device['seri'] }}</span>
                                @if($device['battery'])
                                    <span class="text-[9px] font-bold {{ $device['battery'] < 20 ? 'text-red-500' : 'text-emerald-500' }}">{{ $device['battery'] }}%</span>
                                @endif
                            </div>
                            <div class="mt-1.5 flex items-center justify-between text-[10px] text-muted-foreground">
                                <span class="truncate max-w-[80px]">{{ explode(' ', trim($device['nama_peminjam']))[0] }}</span>
                                <span class="opacity-50 italic">Seen: {{ str_replace('ago', '', $device['last_seen']) }}</span>
                            </div>
                        </button>
                    @empty
                        <div class="p-4 text-center w-full">
                            <p class="text-[10px] font-medium text-muted-foreground">Kosong</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Map View --}}
        <div class="flex-1 relative min-h-[400px] lg:min-h-0 mt-2 lg:mt-0">
            <div id="radarMap" class="w-full h-full rounded-xl border border-border bg-card z-0" wire:ignore></div>
            
            {{-- Map Controls --}}
            <div class="absolute bottom-4 right-4 flex flex-col gap-2 z-[1000]">
                <button @click="resetView()" class="p-2 bg-background border border-border rounded-lg shadow-sm hover:bg-muted transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Leaflet Styles & Scripts --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        .leaflet-container {
            background: #09090b !important;
        }
        .leaflet-tile {
            filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
        }
        .custom-div-icon {
            background: none !important;
            border: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .marker-pin {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #0ea5e9;
            border: 2px solid white;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.2);
            position: relative;
            z-index: 1;
        }
        .marker-pin::after {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: 50%;
            border: 2px solid #0ea5e9;
            animation: marker-pulse 2s infinite;
            opacity: 0;
        }
        @keyframes marker-pulse {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(3.5); opacity: 0; }
        }
        
        /* Shadcn Style Popup Overrides */
        .shadcn-popup .leaflet-popup-content-wrapper {
            background: #09090b;
            color: #fafafa;
            border-radius: 12px;
            padding: 0;
            border: 1px solid #27272a;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .shadcn-popup .leaflet-popup-content {
            margin: 0;
            width: auto !important;
        }
        .shadcn-popup .leaflet-popup-tip {
            background: #27272a;
        }
        .shadcn-popup .leaflet-popup-close-button {
            color: #a1a1aa !important;
            padding: 8px !important;
        }
    </style>

    <script>
        function radarMap() {
            return {
                map: null,
                markers: {},
                selectedId: null,
                devices: @json($devices),

                init() {
                    this.$nextTick(() => {
                        this.map = L.map('radarMap', {
                            zoomControl: false,
                            attributionControl: false
                        }).setView([-7.4243, 109.2303], 13); // Default to Purwokerto

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

                        this.loadMarkers();
                    });
                },

                loadMarkers() {
                    const iconHtml = `
                        <div class="marker-pin"></div>
                        <svg class="marker-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10"/><path d="M18.42 15.61a8.85 8.85 0 1 0-12.84 0"/></svg>
                    `;

                    this.devices.forEach(device => {
                        if (device.lat && device.lng) {
                            const marker = L.marker([device.lat, device.lng], {
                                icon: L.divIcon({
                                    className: 'custom-div-icon',
                                    html: `
                                        <div class="flex flex-col items-center">
                                            <div class="bg-card/70 backdrop-blur-md border border-border/50 rounded-lg px-2 py-1 shadow-2xl mb-1 whitespace-nowrap pointer-events-none translate-y-[-4px]">
                                                <div class="flex flex-col gap-0">
                                                    <p class="text-[10px] font-black text-foreground leading-tight">${device.seri}</p>
                                                    <p class="text-[8px] text-muted-foreground font-medium leading-none mt-1 opacity-70">${device.nama_peminjam.split(' ')[0]} • ${device.last_seen.replace('ago', '')}</p>
                                                </div>
                                            </div>
                                            <div class="marker-pin"></div>
                                        </div>
                                    `,
                                    iconSize: [0, 0],
                                    iconAnchor: [0, 0]
                                })
                            }).addTo(this.map);

                            // Optional: Click on marker also triggers focus
                            marker.on('click', () => this.focusDevice(device));

                            this.markers[device.id] = marker;
                        }
                    });

                    // Fit map bounds if markers exist
                    const markerArray = Object.values(this.markers).map(m => m.getLatLng());
                    if (markerArray.length > 0) {
                        const bounds = L.latLngBounds(markerArray);
                        this.map.fitBounds(bounds, { padding: [50, 50] });
                    }
                },

                currentPolyline: null,

                focusDevice(device) {
                    if (device.lat && device.lng) {
                        this.selectedId = device.id;
                        
                        // Handle Route Shadow (Polyline)
                        if (this.currentPolyline) {
                            this.map.removeLayer(this.currentPolyline);
                        }

                        if (device.history && device.history.length > 1) {
                            this.currentPolyline = L.polyline(device.history, {
                                color: '#0ea5e9',
                                weight: 3,
                                opacity: 0.4,
                                lineJoin: 'round'
                            }).addTo(this.map);
                        }

                        this.map.flyTo([device.lat, device.lng], 16, {
                            duration: 1.5
                        });
                    }
                },

                resetView() {
                    if (this.currentPolyline) {
                        this.map.removeLayer(this.currentPolyline);
                        this.currentPolyline = null;
                    }
                    const markerArray = Object.values(this.markers).map(m => m.getLatLng());
                    if (markerArray.length > 0) {
                        const bounds = L.latLngBounds(markerArray);
                        this.map.fitBounds(bounds, { padding: [50, 50] });
                    } else {
                        this.map.setView([-7.4243, 109.2303], 13);
                    }
                    this.selectedId = null;
                }
            }
        }
    </script>
</div>
