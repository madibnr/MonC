@extends('layouts.app')

@section('title', 'Live Monitoring')
@section('page-title', 'Live Monitoring')

@section('styles')
<style>
    .camera-cell {
        position: relative;
        background: #1a1a2e;
        border-radius: 4px;
        overflow: hidden;
    }
    .camera-cell video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .camera-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        padding: 4px 8px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
        z-index: 10;
        pointer-events: none;
    }
    .camera-status {
        position: absolute;
        bottom: 4px;
        right: 8px;
        z-index: 10;
    }

    /* Fullscreen overlay fills viewport */
    .fullscreen-mode {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        background: #000;
        padding: 4px;
    }

    /* Hide default video controls inside grid cells */
    .camera-cell video::-webkit-media-controls { display: none !important; }
</style>
@endsection

@section('content')
<div x-data="liveMonitor()" x-init="init()">
    <!-- Toolbar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <select x-model="selectedBuilding" @change="filterCameras()" 
                        class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                    @endforeach
                </select>
                
                <span class="text-sm text-slate-500">
                    <span x-text="filteredCameras.length"></span> cameras
                </span>

                <span x-show="go2rtcOnline" x-cloak class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-medium bg-green-100 text-green-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> go2rtc
                </span>
                <span x-show="!go2rtcOnline" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-medium bg-red-100 text-red-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> go2rtc offline
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="autoplayAll()" 
                        :disabled="autoplayingAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas" :class="autoplayingAll ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                    <span x-text="autoplayingAll ? 'Starting...' : 'Play All'"></span>
                </button>
                <button @click="stopAllStreams()" 
                        x-show="Object.values(activeStreams).some(v => v)"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">
                    <i class="fas fa-stop"></i> Stop All
                </button>

                <div class="flex bg-slate-100 rounded-lg p-1">
                    <template x-for="layout in [1, 4, 9, 16, 32, 64]" :key="layout">
                        <button @click="setLayout(layout)" 
                                :class="currentLayout === layout ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors">
                            <span x-text="layout"></span>
                        </button>
                    </template>
                </div>
                
                <button @click="toggleFullscreen()" class="p-2 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                    <i class="fas" :class="isFullscreen ? 'fa-compress' : 'fa-expand'"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Video Grid -->
    <div :class="{ 'fullscreen-mode': isFullscreen }" id="videoGrid">
        <div class="video-grid" :class="'grid-' + currentLayout" :style="gridStyle">
            <template x-for="(camera, index) in paginatedCameras" :key="camera.id">
                <div class="camera-cell cursor-pointer" :style="cellStyle" @click="toggleStream(camera)" @dblclick.stop="enterFocusMode(camera)">
                    <div class="camera-overlay">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-white text-xs font-medium truncate" x-text="camera.name"></span>
                                <span x-show="camera.ai_enabled" x-cloak
                                      class="px-1 py-0.5 rounded text-[8px] font-bold bg-purple-500 text-white uppercase leading-none">
                                    AI
                                </span>
                                <span x-show="focusCameraId === camera.id" x-cloak
                                      class="px-1 py-0.5 rounded text-[8px] font-bold bg-blue-500 text-white uppercase leading-none">
                                    Main Stream
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-300" x-text="camera.location"></span>
                        </div>
                    </div>
                    
                    <!-- Video container - go2rtc video element will be placed here -->
                    <div :id="'video-container-' + camera.id" class="absolute inset-0"
                         x-show="activeStreams[camera.id]"></div>
                    
                    <!-- Placeholder -->
                    <div x-show="!activeStreams[camera.id] && !loadingStreams[camera.id]" 
                         class="absolute inset-0 flex flex-col items-center justify-center text-slate-500">
                        <i class="fas fa-video-slash text-2xl mb-2 opacity-30"></i>
                        <span class="text-xs opacity-50">Click to start stream</span>
                    </div>
                    
                    <!-- Loading -->
                    <div x-show="loadingStreams[camera.id]" 
                         class="absolute inset-0 flex items-center justify-center bg-black/50 z-20">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-spinner fa-spin text-white text-xl mb-2"></i>
                            <span class="text-white text-xs">Connecting...</span>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div x-show="activeStreams[camera.id]" class="absolute bottom-1 left-1 z-10 flex items-center gap-1" @click.stop>
                        <button @click="captureSnapshot(camera.id)" class="w-6 h-6 bg-white/80 hover:bg-white rounded flex items-center justify-center text-slate-700 text-[10px]" title="Capture Snapshot">
                            <i class="fas fa-camera"></i>
                        </button>
                        <button @click="stopStream(camera.id)" class="w-6 h-6 bg-red-500/80 hover:bg-red-500 rounded flex items-center justify-center text-white text-[10px]" title="Stop Stream">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                    
                    <div class="camera-status">
                        <span :class="activeStreams[camera.id] ? 'bg-green-500' : 'bg-slate-500'" 
                              class="inline-block w-2 h-2 rounded-full"></span>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Focus mode indicator -->
        <div x-show="isFullscreen && focusCameraId" x-cloak
             class="fixed top-4 left-4 z-[10000] flex items-center gap-2">
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-blue-500 text-white uppercase">Main Stream</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold text-green-400 bg-black/50">
                <i class="fas fa-circle text-[6px] mr-1"></i>LIVE
            </span>
            <span class="text-white text-xs bg-black/50 px-2 py-1 rounded">Double-click to exit</span>
        </div>

        <button x-show="isFullscreen" @click="focusCameraId ? exitFocusMode() : toggleFullscreen()" 
                class="fixed top-4 right-4 z-[10000] bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm">
            <i class="fas" :class="focusCameraId ? 'fa-th' : 'fa-times'"></i>
            <span x-text="focusCameraId ? 'Back to Grid' : 'Exit Fullscreen'"></span>
        </button>
    </div>


</div>
@endsection

@section('scripts')
{{-- go2rtc module loader with retry --}}
<script>
(function() {
    var go2rtcUrl = @json($go2rtcApiUrl);

    function loadGo2rtc() {
        var script = document.createElement('script');
        script.type = 'module';
        script.textContent = 'import {VideoRTC} from "' + go2rtcUrl + '/video-rtc.js";' +
            'window.VideoRTC = VideoRTC;' +
            'window.go2rtcReady = true;' +
            'window.dispatchEvent(new Event("go2rtc-ready"));';
        document.head.appendChild(script);
    }

    // Try to load immediately
    loadGo2rtc();

    // Retry every 5 seconds if not loaded
    var retryInterval = setInterval(function() {
        if (window.go2rtcReady) {
            clearInterval(retryInterval);
            return;
        }
        // Check if go2rtc is reachable before retrying
        fetch(go2rtcUrl + '/api', { signal: AbortSignal.timeout(3000) })
            .then(function(res) {
                if (res.ok && !window.go2rtcReady) {
                    loadGo2rtc();
                }
            })
            .catch(function() {});
    }, 5000);
})();
</script>

<script>
function liveMonitor() {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const HDR  = { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' };

    return {
        cameras: @json($cameras->map(function($cam) {
            $data = $cam->toArray();
            $data['ai_enabled'] = $cam->aiSetting && $cam->aiSetting->ai_enabled;
            return $data;
        })),
        go2rtcApiUrl: @json($go2rtcApiUrl),
        go2rtcOnline: false,
        go2rtcLoaded: false,
        selectedBuilding: '',
        currentLayout: {{ $currentLayout ?? 4 }},
        isFullscreen: false,
        activeStreams: {},
        loadingStreams: {},
        videoElements: {},
        filteredCameras: [],
        autoplayingAll: false,
        focusCameraId: null,
        previousLayout: null,
        previousFilteredCameras: [],
        previousActiveIds: [],
        clickTimer: null,

        // ── Lifecycle ───────────────────────────────────────────
        init() {
            this.filteredCameras = [...this.cameras];
            this.checkGo2rtcStatus();
            setInterval(() => this.checkGo2rtcStatus(), 15000);

            if (window.go2rtcReady) {
                this.go2rtcLoaded = true;
            } else {
                window.addEventListener('go2rtc-ready', () => { this.go2rtcLoaded = true; });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.focusCameraId) this.exitFocusMode();
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                Object.keys(this.videoElements).forEach(id => this.destroyVideoElement(id));
            });
        },

        async checkGo2rtcStatus() {
            try {
                const res = await fetch(this.go2rtcApiUrl + '/api', { signal: AbortSignal.timeout(3000) });
                this.go2rtcOnline = res.ok;
            } catch {
                this.go2rtcOnline = false;
                try { await fetch('/live/go2rtc-start', { method:'POST', headers:HDR }); } catch {}
            }
            if (window.go2rtcReady) this.go2rtcLoaded = true;
        },

        // ── Grid helpers ────────────────────────────────────────
        get paginatedCameras() { return this.filteredCameras.slice(0, this.currentLayout); },
        get gridCols() {
            if (this.currentLayout <= 1) return 1;
            if (this.currentLayout <= 4) return 2;
            if (this.currentLayout <= 9) return 3;
            if (this.currentLayout <= 16) return 4;
            return 8;
        },
        get gridRows() { return Math.ceil(this.currentLayout / this.gridCols); },
        get gridGap()  { return this.currentLayout <= 16 ? 4 : 2; },
        get gridStyle() {
            const c = this.gridCols, r = this.gridRows, g = this.gridGap;
            if (this.isFullscreen)
                return `display:grid;grid-template-columns:repeat(${c},1fr);grid-template-rows:repeat(${r},1fr);gap:${g}px;height:100%`;
            return `display:grid;grid-template-columns:repeat(${c},1fr);gap:${g}px`;
        },
        get cellStyle() { return this.isFullscreen ? '' : 'aspect-ratio:16/9'; },

        filterCameras() {
            this.filteredCameras = this.selectedBuilding
                ? this.cameras.filter(c => c.building_id == this.selectedBuilding)
                : [...this.cameras];
        },
        setLayout(l) { this.currentLayout = l; },
        toggleFullscreen() { this.isFullscreen = !this.isFullscreen; },

        // ── Stream control ──────────────────────────────────────
        async toggleStream(camera) {
            if (this.clickTimer) { clearTimeout(this.clickTimer); this.clickTimer = null; return; }
            this.clickTimer = setTimeout(async () => {
                this.clickTimer = null;
                this.activeStreams[camera.id] ? await this.stopStream(camera.id) : await this.startStream(camera.id);
            }, 250);
        },

        async startStream(cameraId, streamType = 'sub') {
            if (!this.go2rtcLoaded) { console.warn('go2rtc not ready'); return false; }

            this.loadingStreams = { ...this.loadingStreams, [cameraId]: true };
            try {
                const res = await fetch(`/live/stream/${cameraId}`, { method:'POST', headers:HDR, body:JSON.stringify({ stream_type: streamType }) });
                const data = await res.json();
                if (!data.success || !data.stream_name) { console.error(`Stream ${cameraId}:`, data.message); return false; }

                this.activeStreams = { ...this.activeStreams, [cameraId]: true };

                // Wait for Alpine to render the video container
                await this.$nextTick();
                await new Promise(r => requestAnimationFrame(r));

                this.createVideoElement(cameraId, data.stream_name);
                return true;
            } catch (e) { console.error(`Stream ${cameraId}:`, e); return false; }
            finally { this.loadingStreams = { ...this.loadingStreams, [cameraId]: false }; }
        },

        async stopStream(cameraId) {
            this.destroyVideoElement(cameraId);
            this.activeStreams = { ...this.activeStreams, [cameraId]: false };
            try { await fetch(`/live/stream/${cameraId}`, { method:'DELETE', headers:HDR }); } catch {}
        },

        // ── MSE Video Player ────────────────────────────────────
        createVideoElement(cameraId, streamName) {
            const container = document.getElementById('video-container-' + cameraId);
            if (!container) {
                // Container not yet rendered by Alpine, retry once after a short delay
                setTimeout(() => {
                    const c = document.getElementById('video-container-' + cameraId);
                    if (c && this.activeStreams[cameraId]) {
                        this._buildPlayer(cameraId, streamName, c);
                    }
                }, 200);
                return;
            }
            this._buildPlayer(cameraId, streamName, container);
        },

        _buildPlayer(cameraId, streamName, container) {
            this.destroyVideoElement(cameraId);

            const video = document.createElement('video');
            video.autoplay = true;
            video.playsInline = true;
            video.muted = true;
            video.controls = false;
            Object.assign(video.style, { width:'100%', height:'100%', objectFit:'contain', display:'block' });
            container.appendChild(video);

            const wsUrl = this.go2rtcApiUrl.replace('http','ws') + '/api/ws?src=' + encodeURIComponent(streamName);
            const self = this;
            let ws, ms, sb, buf, bufLen = 0, reconnectTimer = null;

            function getCodecs() {
                const MS = window.ManagedMediaSource || window.MediaSource;
                if (!MS) return '';
                return ['avc1.640029','avc1.64002A','avc1.640033','hvc1.1.6.L153.B0','mp4a.40.2','mp4a.40.5','flac','opus']
                    .filter(c => MS.isTypeSupported(`video/mp4; codecs="${c}"`)).join();
            }

            function connect() {
                ws = new WebSocket(wsUrl);
                ws.binaryType = 'arraybuffer';

                ws.onopen = () => {
                    const MS = window.ManagedMediaSource || window.MediaSource;
                    ms = new MS();
                    ms.addEventListener('sourceopen', () => {
                        ws.send(JSON.stringify({ type:'mse', value:getCodecs() }));
                    }, { once:true });

                    if (window.ManagedMediaSource) {
                        video.disableRemotePlayback = true;
                        video.srcObject = ms;
                    } else {
                        video.src = URL.createObjectURL(ms);
                        video.srcObject = null;
                    }
                    video.play().catch(() => { video.muted = true; video.play().catch(() => {}); });
                };

                ws.onmessage = (ev) => {
                    if (typeof ev.data === 'string') {
                        const msg = JSON.parse(ev.data);
                        if (msg.type === 'mse') {
                            try {
                                sb = ms.addSourceBuffer(msg.value);
                                sb.mode = 'segments';
                                buf = new Uint8Array(2 * 1024 * 1024);
                                bufLen = 0;

                                sb.addEventListener('updateend', () => {
                                    // Flush pending buffer
                                    if (!sb.updating && bufLen > 0) {
                                        try { sb.appendBuffer(buf.slice(0, bufLen)); bufLen = 0; } catch {}
                                    }
                                    // Keep only last 5s of buffer to save memory
                                    if (!sb.updating && sb.buffered && sb.buffered.length) {
                                        const end = sb.buffered.end(sb.buffered.length - 1);
                                        const start0 = sb.buffered.start(0);
                                        if (end - start0 > 10) {
                                            try { sb.remove(start0, end - 5); } catch {}
                                        }
                                        // Jump to live edge if behind
                                        if (video.currentTime < end - 3) {
                                            video.currentTime = end - 0.5;
                                        }
                                        // Gentle speed correction
                                        const gap = end - video.currentTime;
                                        video.playbackRate = gap > 2 ? 1.5 : gap > 0.5 ? 1.1 : 1.0;
                                    }
                                });
                            } catch (e) { console.error(`[MSE] addSourceBuffer ${cameraId}:`, e); }
                        }
                    } else if (sb) {
                        if (sb.updating || bufLen > 0) {
                            const b = new Uint8Array(ev.data);
                            if (bufLen + b.byteLength <= buf.byteLength) {
                                buf.set(b, bufLen);
                                bufLen += b.byteLength;
                            }
                        } else {
                            try { sb.appendBuffer(ev.data); } catch {}
                        }
                    }
                };

                ws.onerror = () => {};
                ws.onclose = () => {
                    // Auto-reconnect if stream is still supposed to be active
                    if (self.activeStreams[cameraId] && !reconnectTimer) {
                        reconnectTimer = setTimeout(() => {
                            reconnectTimer = null;
                            if (self.activeStreams[cameraId]) {
                                console.log(`[WS] Reconnecting camera ${cameraId}...`);
                                connect();
                            }
                        }, 3000);
                    }
                };
            }

            connect();
            this.videoElements[cameraId] = { video, getWs: () => ws, getMs: () => ms, reconnectTimer: () => reconnectTimer, cleanup() {
                if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
                if (ws && ws.readyState <= 1) ws.close();
                video.pause(); video.src = ''; video.srcObject = null; video.load(); video.remove();
            }};
        },

        destroyVideoElement(cameraId) {
            const el = this.videoElements[cameraId];
            if (!el) return;
            el.cleanup();
            delete this.videoElements[cameraId];
            const container = document.getElementById('video-container-' + cameraId);
            if (container) container.innerHTML = '';
        },

        // ── Snapshot ────────────────────────────────────────────
        async captureSnapshot(cameraId) {
            try {
                const res = await fetch('/snapshots/capture', { method:'POST', headers:HDR, body:JSON.stringify({ camera_id:cameraId, from_hls:false }) });
                const data = await res.json();
                if (data.success) {
                    const n = document.createElement('div');
                    n.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-[9999] text-sm';
                    n.innerHTML = '<i class="fas fa-check mr-2"></i>Snapshot captured: ' + data.snapshot.camera_name;
                    document.body.appendChild(n);
                    setTimeout(() => n.remove(), 3000);
                }
            } catch (e) { console.error('Snapshot error:', e); }
        },

        // ── Focus mode (double-click → fullscreen main stream) ─
        async enterFocusMode(camera) {
            if (this.clickTimer) { clearTimeout(this.clickTimer); this.clickTimer = null; }
            if (this.focusCameraId === camera.id) { await this.exitFocusMode(); return; }
            if (this.focusCameraId) { await this.exitFocusMode(); }

            // Save state before focus
            this.previousLayout = this.currentLayout;
            this.previousFilteredCameras = [...this.filteredCameras];
            // Remember which cameras were playing
            this.previousActiveIds = Object.keys(this.activeStreams).filter(id => this.activeStreams[id]).map(Number);

            this.focusCameraId = camera.id;

            // Destroy all other video elements (their DOM will be removed by Alpine)
            for (const id of this.previousActiveIds) {
                if (id !== camera.id) this.destroyVideoElement(id);
            }

            // Switch to single camera view
            this.filteredCameras = [camera];
            this.currentLayout = 1;
            this.isFullscreen = true;

            // Stop sub-stream, start main stream
            if (this.activeStreams[camera.id]) await this.stopStream(camera.id);
            await this.startStream(camera.id, 'main');
        },

        async exitFocusMode() {
            if (!this.focusCameraId) return;
            const focusId = this.focusCameraId;
            const idsToRestore = this.previousActiveIds.filter(id => id !== focusId);

            // Stop main stream of focused camera
            if (this.activeStreams[focusId]) await this.stopStream(focusId);

            // Restore grid
            this.filteredCameras = this.previousFilteredCameras.length > 0 ? [...this.previousFilteredCameras] : [...this.cameras];
            this.currentLayout = this.previousLayout || 4;
            this.isFullscreen = false;
            this.focusCameraId = null;
            this.previousLayout = null;
            this.previousFilteredCameras = [];
            this.previousActiveIds = [];

            // Wait for Alpine to re-render all camera containers
            await this.$nextTick();
            await new Promise(r => requestAnimationFrame(r));

            // Restart all previously active cameras (including the focused one as sub)
            const allToStart = [focusId, ...idsToRestore];
            for (const id of allToStart) {
                try {
                    await this.startStream(id, 'sub');
                } catch {}
                await new Promise(r => setTimeout(r, 150));
            }
        },

        // ── Play All ────────────────────────────────────────────
        async autoplayAll() {
            this.autoplayingAll = true;
            const pending = this.paginatedCameras.filter(c => !this.activeStreams[c.id]);

            for (const camera of pending) {
                try {
                    await this.startStream(camera.id);
                } catch {}
                // Small delay to let browser establish WebSocket before next
                await new Promise(r => setTimeout(r, 150));
            }
            this.autoplayingAll = false;
        },

        async stopAllStreams() {
            const ids = Object.keys(this.activeStreams).filter(id => this.activeStreams[id]);
            ids.forEach(id => this.destroyVideoElement(id));
            this.activeStreams = {};
            await Promise.all(ids.map(id => fetch(`/live/stream/${id}`, { method:'DELETE', headers:HDR }).catch(() => {})));
        },
    };
}
</script>
@endsection
