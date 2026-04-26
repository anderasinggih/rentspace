<div class="lg:h-[calc(100vh-140px)] 
            lg:mt-0 lg:rounded-2xl 
            fixed inset-0 lg:relative lg:inset-auto z-[0]
            overflow-hidden border-0 lg:border lg:border-border" 
     x-data="{ ...radarMap(), isExpanded: false }">
    {{-- Full Background Map --}}
    <div id="radarMap" class="absolute inset-0 z-0 bg-card lg:rounded-2xl" wire:ignore></div>

    {{-- Top Overlay Header --}}
    <div class="absolute top-4 left-4 right-4 flex items-center justify-between z-[1002] pointer-events-none">
        <div class="flex items-center gap-2 bg-background/60 backdrop-blur-md border border-white/10 px-3 py-2 rounded-2xl shadow-xl pointer-events-auto">
            <h1 class="text-xs font-black tracking-tighter">RADAR</h1>
            <span class="h-3 w-px bg-white/20"></span>
            <p class="text-[9px] font-bold text-muted-foreground uppercase opacity-70 tracking-widest">{{ count($devices) }} UNITS</p>
        </div>

        <div class="flex items-center gap-1 bg-background/60 backdrop-blur-md border border-white/10 p-1 rounded-xl shadow-xl pointer-events-auto">
            <a href="{{ route('admin.monitoring') }}" class="px-3 py-1 text-[9px] font-bold hover:bg-white/10 rounded-lg transition-all opacity-60">MONITOR</a>
            <button class="px-3 py-1 text-[9px] font-black bg-white/10 rounded-lg shadow-sm">RADAR</button>
        </div>
    </div>

    {{-- Map Tools Overlay --}}
    <div class="absolute top-20 right-4 flex flex-col gap-2 z-[1000] pointer-events-auto">
        <button @click="resetView()" class="p-3 bg-background/60 backdrop-blur-lg border border-white/10 rounded-2xl shadow-2xl hover:bg-white/10 transition-all active:scale-95 group">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-180 transition-transform duration-500"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        </button>
    </div>

    {{-- Floating Device Panel/Dock --}}
    <div 
        class="absolute z-[1001]
               lg:bottom-6 lg:left-1/2 lg:-translate-x-1/2 lg:w-[500px]
               fixed bottom-4 left-4 right-4 
               transition-all duration-300 cubic-bezier(0.4, 0, 0.2, 1)"
        :class="isExpanded ? 'h-[440px]' : 'h-20 lg:h-20'"
    >
        <div class="bg-background/70 backdrop-blur-md border border-white/10 rounded-2xl flex flex-col h-full shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden">
            {{-- Unified Drag Handle/Header --}}
            <div @click="isExpanded = !isExpanded" class="h-8 flex items-center justify-center shrink-0 cursor-pointer group hover:bg-white/5 transition-all">
                <div class="w-10 h-1 rounded-full bg-white/20 group-hover:bg-white/40 transition-colors"></div>
            </div>

            <div @click="isExpanded = !isExpanded" class="px-6 pb-3 border-b border-white/5 flex items-center justify-between shrink-0 cursor-pointer hover:bg-white/5 transition-all">
                <div>
                    <h2 class="text-[10px] font-black tracking-[0.2em] uppercase text-white/90 leading-none">Devices</h2>
                    <p class="text-[9px] font-bold text-white/30 mt-1 uppercase tracking-tighter">{{ count($devices) }} Units Tracked</p>
                </div>
            </div>

            {{-- Scrollable List (Max 5 items before scroll) --}}
            <div class="flex-1 overflow-y-auto px-2 py-2 space-y-1 scrollbar-hide">
                @forelse($devices as $device)
                    <button 
                        @click="focusDevice({{ json_encode($device) }}); if(window.innerWidth < 1024) isExpanded = false"
                        class="w-full text-left px-3 py-2.5 rounded-xl border border-transparent hover:bg-white/5 transition-all group relative"
                        :class="selectedId === {{ $device['id'] }} ? ({{ $device['is_overdue'] ? 'true' : 'false' }} ? 'bg-red-500/20 border-red-500/30' : 'bg-white/10 border-white/10') : ({{ $device['is_overdue'] ? 'true' : 'false' }} ? 'bg-red-500/10' : '')"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[11px] font-black truncate leading-none mb-1 {{ $device['is_overdue'] ? 'text-red-400' : 'text-white' }}">{{ $device['seri'] }}</h4>
                                <p class="text-[10px] font-medium text-muted-foreground truncate opacity-60">{{ $device['nama_peminjam'] }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[10px] font-black tracking-tighter leading-none mb-1 {{ $device['is_overdue'] ? 'text-red-400' : 'text-emerald-400' }}">
                                    {{ $device['time_left'] }}
                                </p>
                                <p class="text-[8px] font-bold text-white/20 italic leading-none uppercase">{{ str_replace('ago', '', $device['last_seen']) }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="p-12 text-center opacity-30">
                        <p class="text-[10px] font-black uppercase tracking-widest">No Signals</p>
                    </div>
                @endforelse
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
        .marker-pin.is-overdue {
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
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
        .marker-pin.is-overdue::after {
            border: 2px solid #ef4444;
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

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(this.map);

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
                                            <div class="${device.is_overdue ? 'bg-red-600/80 border-red-500 shadow-[0_0_15px_rgba(239,68,68,0.3)]' : 'bg-card/70 border-border/50'} backdrop-blur-md border rounded-lg px-2 py-1 shadow-2xl mb-1 whitespace-nowrap pointer-events-none translate-y-[-4px]">
                                                <div class="flex flex-col gap-0 text-center">
                                                    <p class="text-[10px] font-black ${device.is_overdue ? 'text-white' : 'text-foreground'} leading-tight">${device.seri}</p>
                                                    <p class="text-[8px] ${device.is_overdue ? 'text-white/80 font-bold' : 'text-muted-foreground font-medium'} leading-none mt-1 opacity-70">
                                                        ${device.is_overdue ? '❌ TELAT: ' : ''}${device.nama_peminjam.split(' ')[0]} • ${device.last_seen.replace('ago', '')}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="marker-pin ${device.is_overdue ? 'is-overdue' : ''}"></div>
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

                smoothPoints(points, iterations = 2) {
                    if (points.length < 3) return points;
                    let newPoints = points;
                    for (let i = 0; i < iterations; i++) {
                        let temp = [newPoints[0]];
                        for (let j = 0; j < newPoints.length - 1; j++) {
                            let p1 = newPoints[j];
                            let p2 = newPoints[j+1];
                            temp.push([0.75 * p1[0] + 0.25 * p2[0], 0.75 * p1[1] + 0.25 * p2[1]]);
                            temp.push([0.25 * p1[0] + 0.75 * p2[0], 0.25 * p1[1] + 0.75 * p2[1]]);
                        }
                        temp.push(newPoints[newPoints.length - 1]);
                        newPoints = temp;
                    }
                    return newPoints;
                },

                focusDevice(device) {
                    if (device.lat && device.lng) {
                        this.selectedId = device.id;
                        
                        // Handle Route Shadow (Polyline)
                        if (this.currentPolyline) {
                            this.map.removeLayer(this.currentPolyline);
                        }

                        if (device.history && device.history.length > 1) {
                            const smoothedPath = this.smoothPoints(device.history, 3);
                            this.currentPolyline = L.polyline(smoothedPath, {
                                color: '#0ea5e9',
                                weight: 4,
                                opacity: 0.4,
                                lineJoin: 'round',
                                lineCap: 'round'
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
