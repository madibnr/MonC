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
    .camera-cell video,
    .camera-cell video-stream {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: contain;
        display: block;
    }
    .camera-cell video-stream video {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
    }
    .camera-overlay {
        position: absolute; top: 0; left: 0; right: 0;
        padding: 4px 8px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
        z-index: 10; pointer-events: none;
    }
    .camera-status {
        position: absolute; bottom: 4px; right: 8px; z-index: 10;
    }
    .fullscreen-mode {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        z-index: 9999; background: #000; padding: 4px;
    }
    .camera-cell video::-webkit-media-controls { display: none !important; }
</style>
@endsection

@section('content')
<div x-data="liveMonitor()" x-init="init()">
    <!-- Toolbar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3 mb-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Left: filter + info -->
            <div class="flex items-center gap-3">
                <select x-model="selectedBuilding" @change="onFilterChange()"
                        class="text-sm border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                    @endforeach
                </select>

                <span class="text-xs text-slate-500">
                    <span x-text="filteredCameras.length"></span> cameras
                </span>

                <span x-show="go2rtcOnline" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> go2rtc
                </span>
                <span x-show="!go2rtcOnline" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> go2rtc offline
                </span>
            </div>

            <!-- Right: actions + layout + pagination -->
            <div class="flex items-center gap-2">
                <button @click="autoplayAll()" :disabled="autoplayingAll"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors disabled:opacity-50">
                    <i class="fas" :class="autoplayingAll ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                    <span x-text="autoplayingAll ? 'Starting...' : 'Play All'"></span>
                </button>
                <button @click="stopAllStreams()" x-show="Object.values(activeStreams).some(v => v)" x-cloak
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">
                    <i class="fas fa-stop"></i> Stop All
                </button>

                <!-- Layout selector -->
                <div class="flex bg-slate-100 rounded-lg p-0.5">
                    <template x-for="l in layouts" :key="l">
                        <button @click="setLayout(l)"
                                :class="currentLayout === l ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors" x-text="l"></button>
                    </template>
                </div>

                <!-- Pagination -->
                <div class="flex items-center gap-1 ml-1">
                    <button @click="prevPage()" :disabled="currentPage <= 1"
                            class="w-7 h-7 flex items-center justify-center rounded text-xs border border-slate-300 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <span class="text-xs text-slate-600 min-w-[60px] text-center">
                        <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                    </span>
                    <button @click="nextPage()" :disabled="currentPage >= totalPages"
                            class="w-7 h-7 flex items-center justify-center rounded text-xs border border-slate-300 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>

                <button @click="toggleFullscreen()" class="p-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                    <i class="fas" :class="isFullscreen ? 'fa-compress' : 'fa-expand'"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Video Grid -->
    <div :class="{ 'fullscreen-mode': isFullscreen }" id="videoGrid">
        <div class="video-grid" :style="gridStyle">
            <template x-for="camera in paginatedCameras" :key="camera.id">
                <div class="camera-cell cursor-pointer" :style="cellStyle" @click="toggleStream(camera)" @dblclick.stop="enterFocusMode(camera)">
                    <div class="camera-overlay">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-white text-xs font-medium truncate" x-text="camera.name"></span>
                                <span x-show="camera.ai_enabled" x-cloak class="px-1 py-0.5 rounded text-[8px] font-bold bg-purple-500 text-white uppercase leading-none">AI</span>
                                <span x-show="focusCameraId === camera.id" x-cloak class="px-1 py-0.5 rounded text-[8px] font-bold bg-blue-500 text-white uppercase leading-none">Main Stream</span>
                            </div>
                            <span class="text-[10px] text-slate-300" x-text="camera.location"></span>
                        </div>
                    </div>

                    <div :id="'video-container-' + camera.id" class="absolute inset-0 z-[1]"></div>

                    <template x-if="!activeStreams[camera.id] && !loadingStreams[camera.id]">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-[2]">
                            <i class="fas fa-video-slash text-2xl mb-2 opacity-30"></i>
                            <span class="text-xs opacity-50">Click to start</span>
                            <span class="text-[10px] opacity-30 mt-1">Double-click for HD</span>
                        </div>
                    </template>

                    <template x-if="loadingStreams[camera.id]">
                        <div class="absolute inset-0 flex items-center justify-center bg-black/50 z-[2]">
                            <i class="fas fa-spinner fa-spin text-white text-xl"></i>
                        </div>
                    </template>

                    <div x-show="activeStreams[camera.id]" class="absolute bottom-1 left-1 z-10" @click.stop>
                        <button @click="stopStream(camera.id)" class="w-6 h-6 bg-red-500/80 hover:bg-red-500 rounded flex items-center justify-center text-white text-[10px]" title="Stop">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>

                    <div class="camera-status">
                        <span :class="activeStreams[camera.id] ? 'bg-green-500' : 'bg-slate-500'" class="inline-block w-2 h-2 rounded-full"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Fullscreen controls -->
        <div x-show="isFullscreen && focusCameraId" x-cloak class="fixed top-4 left-4 z-[10000] flex items-center gap-2">
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-blue-500 text-white uppercase">Main Stream</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold text-green-400 bg-black/50"><i class="fas fa-circle text-[6px] mr-1"></i>LIVE</span>
            <span class="text-white text-xs bg-black/50 px-2 py-1 rounded">ESC to exit | ← → to navigate</span>
        </div>

        <!-- Focus mode navigation (Previous/Next camera) -->
        <div x-show="isFullscreen && focusCameraId" x-cloak class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[10000] flex items-center gap-3 bg-black/70 px-4 py-2 rounded-full">
            <button @click="previousCamera()" :disabled="!canGoPreviousCamera()" 
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="text-center px-2">
                <div class="text-white text-xs font-medium" x-text="getFocusCameraName()"></div>
                <div class="text-white/60 text-[10px]" x-text="getFocusCameraPosition()"></div>
            </div>
            <button @click="nextCamera()" :disabled="!canGoNextCamera()" 
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Fullscreen pagination (for grid mode) -->
        <div x-show="isFullscreen && !focusCameraId" x-cloak class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[10000] flex items-center gap-2 bg-black/70 px-4 py-2 rounded-full">
            <button @click="prevPage()" :disabled="currentPage <= 1" class="text-white disabled:opacity-30 text-sm px-2"><i class="fas fa-chevron-left"></i></button>
            <span class="text-white text-xs"><span x-text="currentPage"></span> / <span x-text="totalPages"></span></span>
            <button @click="nextPage()" :disabled="currentPage >= totalPages" class="text-white disabled:opacity-30 text-sm px-2"><i class="fas fa-chevron-right"></i></button>
        </div>

        <button x-show="isFullscreen" x-cloak @click="focusCameraId ? exitFocusMode() : toggleFullscreen()"
                class="fixed top-4 right-4 z-[10000] bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm">
            <i class="fas" :class="focusCameraId ? 'fa-th' : 'fa-times'"></i>
            <span x-text="focusCameraId ? 'Back to Grid' : 'Exit Fullscreen'"></span>
        </button>
    </div>
</div>
@endsection

@section('scripts')
{{-- Don't load video-stream.js statically — it will fail if go2rtc is not yet running.
     Instead, we load it dynamically after confirming go2rtc is online. --}}

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
        _videoStreamLoaded: false,
        _streamsPreRegistered: false,
        selectedBuilding: '',
        layouts: [1, 4, 9, 16],
        currentLayout: {{ min($currentLayout ?? 4, 16) }},
        currentPage: 1,
        isFullscreen: false,
        activeStreams: {},
        loadingStreams: {},
        videoElements: {},
        filteredCameras: [],
        autoplayingAll: false,
        focusCameraId: null,
        previousLayout: null,
        previousPage: 1,
        previousFilteredCameras: [],
        previousActiveIds: [],
        clickTimer: null,

        // ── Lifecycle ───────────────────────────────────────
        init() {
            this.filteredCameras = [...this.cameras];
            this._bootGo2rtc();
            setInterval(() => this.checkGo2rtcStatus(), 15000);

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.focusCameraId) {
                    this.exitFocusMode();
                }
                // Arrow keys for focus mode navigation
                if (this.focusCameraId) {
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.previousCamera();
                    } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.nextCamera();
                    }
                }
            });

            window.addEventListener('beforeunload', () => {
                Object.keys(this.videoElements).forEach(id => this.destroyVideoElement(id));
            });
        },

        /**
         * Boot sequence: ensure go2rtc is running, then load video-stream.js
         * and pre-register streams. Retries automatically on failure.
         */
        async _bootGo2rtc() {
            const maxAttempts = 20;  // ~60 seconds total
            for (let attempt = 1; attempt <= maxAttempts; attempt++) {
                // 1. Check if go2rtc is already online
                const online = await this._pingGo2rtc();
                if (online) {
                    this.go2rtcOnline = true;
                    // 2. Load video-stream.js (only once)
                    if (!this._videoStreamLoaded) {
                        await this._loadVideoStreamScript();
                    }
                    // 3. Pre-register camera streams (only once)
                    if (!this._streamsPreRegistered) {
                        this._preRegisterStreams();
                    }
                    return;
                }

                // Not online yet — try to start it via backend
                this.go2rtcOnline = false;
                try {
                    await fetch('/live/go2rtc-start', { method: 'POST', headers: HDR });
                } catch {}

                // Wait before retrying (3s for first few attempts, then slower)
                const delay = attempt <= 5 ? 3000 : 5000;
                await new Promise(r => setTimeout(r, delay));
            }
            console.warn('go2rtc failed to start after maximum attempts');
        },

        async _pingGo2rtc() {
            try {
                const res = await fetch(this.go2rtcApiUrl + '/api', { signal: AbortSignal.timeout(3000) });
                return res.ok;
            } catch {
                return false;
            }
        },

        /**
         * Dynamically load video-stream.js from go2rtc server.
         * This replaces the static <script> tag that would fail if go2rtc is down.
         */
        async _loadVideoStreamScript() {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.type = 'module';
                script.src = this.go2rtcApiUrl + '/video-stream.js';
                script.onload = () => {
                    this._videoStreamLoaded = true;
                    console.log('video-stream.js loaded successfully');
                    resolve(true);
                };
                script.onerror = () => {
                    console.warn('Failed to load video-stream.js, will retry on next check');
                    resolve(false);
                };
                document.head.appendChild(script);
            });
        },

        /**
         * Pre-register all camera streams in go2rtc via AJAX (non-blocking).
         */
        async _preRegisterStreams() {
            try {
                const res = await fetch('/live/pre-register-streams', { method: 'POST', headers: HDR });
                if (res.ok) {
                    this._streamsPreRegistered = true;
                }
            } catch {}
        },

        async checkGo2rtcStatus() {
            const wasOnline = this.go2rtcOnline;
            this.go2rtcOnline = await this._pingGo2rtc();

            if (!this.go2rtcOnline) {
                // Try to auto-start
                try { await fetch('/live/go2rtc-start', { method:'POST', headers:HDR }); } catch {}
            } else if (!wasOnline) {
                // Just came online — load script and register streams if not done yet
                if (!this._videoStreamLoaded) await this._loadVideoStreamScript();
                if (!this._streamsPreRegistered) this._preRegisterStreams();
            }
        },

        // ── Pagination + Grid ───────────────────────────────
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredCameras.length / this.currentLayout));
        },

        get paginatedCameras() {
            const start = (this.currentPage - 1) * this.currentLayout;
            return this.filteredCameras.slice(start, start + this.currentLayout);
        },

        get gridCols() {
            if (this.currentLayout <= 1) return 1;
            if (this.currentLayout <= 4) return 2;
            if (this.currentLayout <= 9) return 3;
            return 4;
        },
        get gridRows() { return Math.ceil(this.currentLayout / this.gridCols); },
        get gridGap()  { return 4; },
        get gridStyle() {
            const c = this.gridCols, r = this.gridRows, g = this.gridGap;
            if (this.isFullscreen)
                return `display:grid;grid-template-columns:repeat(${c},1fr);grid-template-rows:repeat(${r},1fr);gap:${g}px;height:100%`;
            return `display:grid;grid-template-columns:repeat(${c},1fr);gap:${g}px`;
        },
        get cellStyle() { return this.isFullscreen ? '' : 'aspect-ratio:16/9'; },

        setLayout(l) {
            if (this.currentLayout === l) return;
            this.stopStreamsOnCurrentPage();
            this.currentLayout = l;
            this.currentPage = 1;
        },

        prevPage() {
            if (this.currentPage <= 1) return;
            this.stopStreamsOnCurrentPage();
            this.currentPage--;
        },

        nextPage() {
            if (this.currentPage >= this.totalPages) return;
            this.stopStreamsOnCurrentPage();
            this.currentPage++;
        },

        onFilterChange() {
            this.stopStreamsOnCurrentPage();
            this.filteredCameras = this.selectedBuilding
                ? this.cameras.filter(c => c.building_id == this.selectedBuilding)
                : [...this.cameras];
            this.currentPage = 1;
        },

        async toggleFullscreen() {
            const activeIds = Object.keys(this.activeStreams).filter(id => this.activeStreams[id]).map(Number);

            // Stop all active streams first
            for (const id of activeIds) {
                this.destroyVideoElement(id);
                this._setActive(id, false);
            }

            // Toggle fullscreen state
            this.isFullscreen = !this.isFullscreen;

            // Wait for DOM to settle after layout change
            await this.$nextTick();
            await this.$nextTick();
            await new Promise(r => requestAnimationFrame(r));

            // Restart streams with sub stream (grid fullscreen uses sub for monitoring)
            for (const cameraId of activeIds) {
                await this.startStream(cameraId, 'sub').catch(() => {});
                await new Promise(r => setTimeout(r, 200));
            }
        },

        // Stop all streams on the current page (before navigating away)
        stopStreamsOnCurrentPage() {
            for (const cam of this.paginatedCameras) {
                if (this.activeStreams[cam.id]) {
                    this.destroyVideoElement(cam.id);
                }
            }
            // Clear active flags for cameras on this page
            const copy = Object.assign({}, this.activeStreams);
            for (const cam of this.paginatedCameras) { copy[cam.id] = false; }
            this.activeStreams = copy;
        },

        // ── Stream control ──────────────────────────────────
        toggleStream(camera) {
            if (this.clickTimer) { clearTimeout(this.clickTimer); this.clickTimer = null; return; }
            this.clickTimer = setTimeout(() => {
                this.clickTimer = null;
                if (this.activeStreams[camera.id]) {
                    this.stopStream(camera.id);
                } else {
                    // Use main stream if in focus mode, otherwise sub stream
                    const type = this.focusCameraId === camera.id ? 'main' : 'sub';
                    this.startStream(camera.id, type);
                }
            }, 250);
        },

        _streamName(cameraId, type = 'sub') {
            return type === 'main' ? `camera_${cameraId}_main` : `camera_${cameraId}`;
        },

        async startStream(cameraId, streamType = 'sub') {
            this._setLoading(cameraId, true);
            try {
                const streamName = this._streamName(cameraId, streamType);

                // For main stream: register in go2rtc via backend
                // If registration fails, still try to connect (go2rtc may already have it)
                if (streamType === 'main') {
                    try {
                        await fetch(`/live/stream/${cameraId}`, {
                            method: 'POST', headers: HDR,
                            body: JSON.stringify({ stream_type: streamType })
                        });
                    } catch (e) {
                        // Ignore registration errors — go2rtc may still serve the stream
                        console.warn(`Main stream registration request failed for camera ${cameraId}, trying anyway...`);
                    }
                }

                this._setActive(cameraId, true);
                await this.$nextTick();
                await this.$nextTick();
                await new Promise(r => requestAnimationFrame(r));

                this.createVideoElement(cameraId, streamName);
                return true;
            } catch (e) { 
                console.error(`Stream ${cameraId} error:`, e); 
                return false; 
            }
            finally { this._setLoading(cameraId, false); }
        },

        stopStream(cameraId) {
            this.destroyVideoElement(cameraId);
            this._setActive(cameraId, false);
        },

        _setActive(id, val) {
            const c = Object.assign({}, this.activeStreams); c[id] = val; this.activeStreams = c;
        },
        _setLoading(id, val) {
            const c = Object.assign({}, this.loadingStreams); c[id] = val; this.loadingStreams = c;
        },

        // ── Video Player (<video-stream> web component) ─────
        createVideoElement(cameraId, streamName) {
            const self = this;
            let attempts = 0;
            (function tryBuild() {
                const container = document.getElementById('video-container-' + cameraId);
                if (container) {
                    self._buildPlayer(cameraId, streamName, container);
                } else if (++attempts < 20 && self.activeStreams[cameraId]) {
                    setTimeout(tryBuild, 100);
                }
            })();
        },

        _buildPlayer(cameraId, streamName, container) {
            this.destroyVideoElement(cameraId);

            const el = document.createElement('video-stream');
            el.setAttribute('background', '');
            Object.assign(el.style, { width:'100%', height:'100%', display:'block' });
            container.appendChild(el);

            const wsUrl = this.go2rtcApiUrl.replace('http','ws') + '/api/ws?src=' + encodeURIComponent(streamName);
            el.src = wsUrl;

            // Timeout detection: if stream doesn't start playing within 10 seconds, fallback to sub
            const timeoutId = setTimeout(() => {
                // Check if video element is actually playing
                const videoEl = el.querySelector('video');
                if (!videoEl || videoEl.readyState < 2) {
                    console.warn(`Stream ${streamName} timeout - falling back to sub stream`);
                    // If this was a main stream, try sub stream instead
                    if (streamName.includes('_main')) {
                        this.destroyVideoElement(cameraId);
                        this._setActive(cameraId, false);
                        this.startStream(cameraId, 'sub');
                    }
                }
            }, 10000);

            this.videoElements[cameraId] = {
                el,
                timeoutId,
                cleanup() {
                    clearTimeout(timeoutId);
                    el.src = '';
                    el.remove();
                }
            };
        },

        destroyVideoElement(cameraId) {
            const entry = this.videoElements[cameraId];
            if (entry) { entry.cleanup(); delete this.videoElements[cameraId]; }
            const container = document.getElementById('video-container-' + cameraId);
            if (container) container.innerHTML = '';
        },

        // ── Focus mode ──────────────────────────────────────
        async enterFocusMode(camera) {
            if (this.clickTimer) { clearTimeout(this.clickTimer); this.clickTimer = null; }
            if (this.focusCameraId === camera.id) { await this.exitFocusMode(); return; }
            if (this.focusCameraId) { await this.exitFocusMode(); }

            this.previousLayout = this.currentLayout;
            this.previousPage = this.currentPage;
            this.previousFilteredCameras = [...this.filteredCameras];
            this.previousActiveIds = Object.keys(this.activeStreams).filter(id => this.activeStreams[id]).map(Number);

            // Stop and destroy ALL active video elements first (before DOM changes)
            for (const id of this.previousActiveIds) {
                this.destroyVideoElement(id);
                this._setActive(id, false);
            }

            // Now change layout — this triggers Alpine DOM re-render
            this.focusCameraId = camera.id;
            this.filteredCameras = [camera];
            this.currentLayout = 1;
            this.currentPage = 1;
            this.isFullscreen = true;

            // Wait for Alpine to fully re-render the new single-camera DOM
            await this.$nextTick();
            await this.$nextTick();
            await new Promise(r => requestAnimationFrame(r));

            // Start main stream (registers in go2rtc via backend, then builds video element)
            await this.startStream(camera.id, 'main');
        },

        async exitFocusMode() {
            if (!this.focusCameraId) return;
            const focusId = this.focusCameraId;
            const idsToRestore = this.previousActiveIds.filter(id => id !== focusId);

            // Stop current focus stream
            this.destroyVideoElement(focusId);
            this._setActive(focusId, false);

            // Restore previous state
            this.filteredCameras = this.previousFilteredCameras.length > 0 ? [...this.previousFilteredCameras] : [...this.cameras];
            this.currentLayout = this.previousLayout || 4;
            this.currentPage = this.previousPage || 1;
            this.isFullscreen = false;
            this.focusCameraId = null;
            this.previousLayout = null;
            this.previousPage = 1;
            this.previousFilteredCameras = [];
            this.previousActiveIds = [];

            // Wait for DOM to fully re-render the grid layout
            await this.$nextTick();
            await this.$nextTick();
            await new Promise(r => requestAnimationFrame(r));

            // Restart streams as sub
            const allToStart = [focusId, ...idsToRestore];
            for (const id of allToStart) {
                await this.startStream(id, 'sub').catch(() => {});
                await new Promise(r => setTimeout(r, 200));
            }
        },

        // ── Play All (current page only) ────────────────────
        async autoplayAll() {
            this.autoplayingAll = true;
            const pending = this.paginatedCameras.filter(c => !this.activeStreams[c.id]);
            // Always use sub stream for monitoring (Play All)
            const streamType = 'sub';

            for (const cam of pending) {
                await this.startStream(cam.id, streamType).catch(() => {});
                await new Promise(r => setTimeout(r, 150));
            }

            this.autoplayingAll = false;
        },

        stopAllStreams() {
            const ids = Object.keys(this.activeStreams).filter(id => this.activeStreams[id]);
            ids.forEach(id => this.destroyVideoElement(id));
            this.activeStreams = {};
            this.loadingStreams = {};
        },

        // ── Focus mode navigation ───────────────────────────
        getFocusCameraIndex() {
            if (!this.focusCameraId) return -1;
            return this.previousFilteredCameras.findIndex(c => c.id === this.focusCameraId);
        },

        canGoPreviousCamera() {
            const idx = this.getFocusCameraIndex();
            return idx > 0;
        },

        canGoNextCamera() {
            const idx = this.getFocusCameraIndex();
            return idx >= 0 && idx < this.previousFilteredCameras.length - 1;
        },

        getFocusCameraName() {
            if (!this.focusCameraId) return '';
            const camera = this.previousFilteredCameras.find(c => c.id === this.focusCameraId);
            return camera ? camera.name : '';
        },

        getFocusCameraPosition() {
            const idx = this.getFocusCameraIndex();
            if (idx < 0) return '';
            return `${idx + 1} / ${this.previousFilteredCameras.length}`;
        },

        async previousCamera() {
            if (!this.canGoPreviousCamera()) return;
            const idx = this.getFocusCameraIndex();
            const prevCamera = this.previousFilteredCameras[idx - 1];
            await this.switchFocusCamera(prevCamera);
        },

        async nextCamera() {
            if (!this.canGoNextCamera()) return;
            const idx = this.getFocusCameraIndex();
            const nextCamera = this.previousFilteredCameras[idx + 1];
            await this.switchFocusCamera(nextCamera);
        },

        async switchFocusCamera(camera) {
            if (!camera || !this.focusCameraId) return;

            // Stop current stream
            this.destroyVideoElement(this.focusCameraId);
            this._setActive(this.focusCameraId, false);

            // Update focus camera
            this.focusCameraId = camera.id;
            this.filteredCameras = [camera];

            // Wait for DOM update
            await this.$nextTick();
            await this.$nextTick();
            await new Promise(r => requestAnimationFrame(r));

            // Start new main stream
            await this.startStream(camera.id, 'main');
        },
    };
}
</script>
@endsection
