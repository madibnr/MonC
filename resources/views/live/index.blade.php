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
{{-- Load go2rtc VideoRTC player directly from go2rtc server --}}
<script type="module">
import {VideoRTC} from '{{ $go2rtcApiUrl }}/video-rtc.js';

// Make VideoRTC available globally for Alpine.js
window.VideoRTC = VideoRTC;
window.go2rtcReady = true;

// Dispatch event so Alpine knows it's ready
window.dispatchEvent(new Event('go2rtc-ready'));
</script>

<script>
function liveMonitor() {
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
        clickTimer: null,
        
        init() {
            this.filteredCameras = [...this.cameras];
            this.checkGo2rtcStatus();
            setInterval(() => this.checkGo2rtcStatus(), 15000);

            // Wait for VideoRTC module to load
            if (window.go2rtcReady) {
                this.go2rtcLoaded = true;
            } else {
                window.addEventListener('go2rtc-ready', () => {
                    this.go2rtcLoaded = true;
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.focusCameraId) {
                    this.exitFocusMode();
                }
            });
        },

        async checkGo2rtcStatus() {
            try {
                const res = await fetch(this.go2rtcApiUrl + '/api');
                this.go2rtcOnline = res.ok;
            } catch {
                this.go2rtcOnline = false;
            }
        },
        
        get paginatedCameras() {
            return this.filteredCameras.slice(0, this.currentLayout);
        },
        
        get gridCols() {
            if (this.currentLayout <= 1) return 1;
            if (this.currentLayout <= 4) return 2;
            if (this.currentLayout <= 9) return 3;
            if (this.currentLayout <= 16) return 4;
            return 8;
        },

        get gridRows() {
            return Math.ceil(this.currentLayout / this.gridCols);
        },

        get gridGap() {
            return this.currentLayout <= 16 ? 4 : 2;
        },

        get gridStyle() {
            const cols = this.gridCols;
            const rows = this.gridRows;
            const gap = this.gridGap;

            if (this.isFullscreen) {
                // Fullscreen: fill entire viewport, rows divide height evenly
                return `display:grid; grid-template-columns: repeat(${cols}, 1fr); grid-template-rows: repeat(${rows}, 1fr); gap: ${gap}px; height: 100%;`;
            }

            // Normal mode: use aspect-ratio based height via padding trick
            return `display:grid; grid-template-columns: repeat(${cols}, 1fr); gap: ${gap}px;`;
        },

        get cellStyle() {
            // In fullscreen the grid rows handle sizing, no aspect-ratio needed
            if (this.isFullscreen) return '';
            // Normal mode: maintain 16:9 aspect ratio
            return 'aspect-ratio: 16/9;';
        },
        
        filterCameras() {
            if (this.selectedBuilding) {
                this.filteredCameras = this.cameras.filter(c => c.building_id == this.selectedBuilding);
            } else {
                this.filteredCameras = [...this.cameras];
            }
        },
        
        setLayout(layout) {
            this.currentLayout = layout;
        },
        
        async toggleStream(camera) {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer);
                this.clickTimer = null;
                return;
            }
            this.clickTimer = setTimeout(async () => {
                this.clickTimer = null;
                if (this.activeStreams[camera.id]) {
                    await this.stopStream(camera.id);
                } else {
                    await this.startStream(camera.id);
                }
            }, 250);
        },
        
        /**
         * Start stream: register in go2rtc via Laravel, then create VideoRTC element.
         */
        async startStream(cameraId, streamType = 'sub') {
            if (!this.go2rtcLoaded) {
                alert('go2rtc player is still loading. Please wait.');
                return;
            }

            this.loadingStreams[cameraId] = true;
            
            try {
                // 1. Register stream in go2rtc via Laravel backend
                const response = await fetch(`/live/stream/${cameraId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ stream_type: streamType }),
                });
                
                const data = await response.json();
                
                if (data.success && data.stream_name) {
                    this.activeStreams[cameraId] = true;
                    
                    await this.$nextTick();
                    
                    // 2. Create go2rtc VideoRTC player element
                    this.createVideoElement(cameraId, data.stream_name);
                } else {
                    alert(data.message || 'Failed to start stream');
                }
            } catch (error) {
                console.error('Stream error:', error);
                alert('Failed to connect to stream');
            } finally {
                this.loadingStreams[cameraId] = false;
            }
        },

        /**
         * Create a VideoRTC custom element and attach it to the container.
         */
        createVideoElement(cameraId, streamName) {
            const container = document.getElementById('video-container-' + cameraId);
            if (!container) return;

            // Clean up existing
            this.destroyVideoElement(cameraId);

            // Create a video element manually and use go2rtc WebSocket
            const video = document.createElement('video');
            video.autoplay = true;
            video.playsInline = true;
            video.muted = true;
            video.controls = false;
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            video.style.display = 'block';
            container.appendChild(video);

            // Connect via go2rtc MSE WebSocket
            const wsUrl = this.go2rtcApiUrl.replace('http', 'ws') + '/api/ws?src=' + encodeURIComponent(streamName);
            const ws = new WebSocket(wsUrl);
            ws.binaryType = 'arraybuffer';

            const CODECS = [
                'avc1.640029', 'avc1.64002A', 'avc1.640033',
                'hvc1.1.6.L153.B0',
                'mp4a.40.2', 'mp4a.40.5', 'flac', 'opus',
            ];

            function getCodecs() {
                const MS = window.ManagedMediaSource || window.MediaSource;
                if (!MS) return '';
                return CODECS
                    .filter(c => MS.isTypeSupported(`video/mp4; codecs="${c}"`))
                    .join();
            }

            let ms, sb, buf, bufLen = 0;

            ws.onopen = () => {
                console.log(`[go2rtc] WS open: camera ${cameraId}`);

                // Create MediaSource first, send codecs after sourceopen
                const MS = window.ManagedMediaSource || window.MediaSource;
                ms = new MS();

                ms.addEventListener('sourceopen', () => {
                    // Send supported codecs to go2rtc
                    ws.send(JSON.stringify({ type: 'mse', value: getCodecs() }));
                }, { once: true });

                if (window.ManagedMediaSource) {
                    video.disableRemotePlayback = true;
                    video.srcObject = ms;
                } else {
                    video.src = URL.createObjectURL(ms);
                    video.srcObject = null;
                }

                video.play().catch(e => {
                    if (!video.muted) {
                        video.muted = true;
                        video.play().catch(() => {});
                    }
                });
            };

            ws.onmessage = (ev) => {
                if (typeof ev.data === 'string') {
                    const msg = JSON.parse(ev.data);
                    if (msg.type === 'mse') {
                        // go2rtc tells us which codec to use
                        console.log(`[go2rtc] MSE codec: ${msg.value} for camera ${cameraId}`);
                        try {
                            sb = ms.addSourceBuffer(msg.value);
                            sb.mode = 'segments';

                            buf = new Uint8Array(2 * 1024 * 1024);
                            bufLen = 0;

                            sb.addEventListener('updateend', () => {
                                if (!sb.updating && bufLen > 0) {
                                    try {
                                        sb.appendBuffer(buf.slice(0, bufLen));
                                        bufLen = 0;
                                    } catch (e) {}
                                }

                                if (!sb.updating && sb.buffered && sb.buffered.length) {
                                    const end = sb.buffered.end(sb.buffered.length - 1);
                                    const start = end - 5;
                                    const start0 = sb.buffered.start(0);
                                    if (start > start0) {
                                        sb.remove(start0, start);
                                        ms.setLiveSeekableRange(start, end);
                                    }
                                    if (video.currentTime < start) {
                                        video.currentTime = start;
                                    }
                                    // Speed up playback to catch up to live edge
                                    const gap = end - video.currentTime;
                                    video.playbackRate = gap > 0.1 ? gap : 0.1;
                                }
                            });
                        } catch (e) {
                            console.error(`[go2rtc] addSourceBuffer error for camera ${cameraId}:`, e);
                        }
                    } else if (msg.type === 'error') {
                        console.error(`[go2rtc] Error for camera ${cameraId}:`, msg.value);
                    }
                } else {
                    // Binary data
                    if (sb) {
                        if (sb.updating || bufLen > 0) {
                            const b = new Uint8Array(ev.data);
                            buf.set(b, bufLen);
                            bufLen += b.byteLength;
                        } else {
                            try {
                                sb.appendBuffer(ev.data);
                            } catch (e) {}
                        }
                    }
                }
            };

            ws.onerror = (e) => console.error(`[go2rtc] WS error: camera ${cameraId}`, e);
            ws.onclose = () => console.log(`[go2rtc] WS closed: camera ${cameraId}`);

            this.videoElements[cameraId] = { video, ws, ms };
        },

        destroyVideoElement(cameraId) {
            const el = this.videoElements[cameraId];
            if (!el) return;

            if (el.ws && el.ws.readyState <= 1) el.ws.close();
            if (el.video) {
                el.video.pause();
                el.video.src = '';
                el.video.srcObject = null;
                el.video.load();
                el.video.remove();
            }

            delete this.videoElements[cameraId];

            const container = document.getElementById('video-container-' + cameraId);
            if (container) container.innerHTML = '';
        },
        
        async stopStream(cameraId) {
            try {
                this.destroyVideoElement(cameraId);
                
                await fetch(`/live/stream/${cameraId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                
                this.activeStreams[cameraId] = false;
            } catch (error) {
                console.error('Stop stream error:', error);
            }
        },
        
        async captureSnapshot(cameraId) {
            try {
                const response = await fetch('/snapshots/capture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ camera_id: cameraId, from_hls: false }),
                });
                const data = await response.json();
                if (data.success) {
                    const notif = document.createElement('div');
                    notif.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-[9999] text-sm';
                    notif.innerHTML = '<i class="fas fa-check mr-2"></i>Snapshot captured: ' + data.snapshot.camera_name;
                    document.body.appendChild(notif);
                    setTimeout(() => notif.remove(), 3000);
                } else {
                    alert(data.message || 'Failed to capture snapshot');
                }
            } catch (error) {
                console.error('Snapshot error:', error);
                alert('Failed to capture snapshot');
            }
        },
        
        async enterFocusMode(camera) {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer);
                this.clickTimer = null;
            }

            // If already in focus mode on this camera, exit focus mode
            if (this.focusCameraId === camera.id) {
                await this.exitFocusMode();
                return;
            }

            // If in focus mode on a different camera, exit first
            if (this.focusCameraId) {
                await this.exitFocusMode();
            }

            // Save current state for restoration
            this.previousLayout = this.currentLayout;
            this.previousFilteredCameras = [...this.filteredCameras];
            this.focusCameraId = camera.id;

            // Switch grid to show only this camera in layout=1 (fullscreen)
            this.filteredCameras = [camera];
            this.currentLayout = 1;
            this.isFullscreen = true;

            // Stop sub-stream and switch to main stream
            if (this.activeStreams[camera.id]) {
                this.destroyVideoElement(camera.id);
                await fetch(`/live/stream/${camera.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.activeStreams[camera.id] = false;
            }

            // Start main stream
            await this.startStream(camera.id, 'main');
        },

        async exitFocusMode() {
            if (!this.focusCameraId) return;
            const cameraId = this.focusCameraId;

            // Stop main stream
            if (this.activeStreams[cameraId]) {
                this.destroyVideoElement(cameraId);
                await fetch(`/live/stream/${cameraId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.activeStreams[cameraId] = false;
            }

            // Restore previous grid state
            this.filteredCameras = this.previousFilteredCameras.length > 0
                ? [...this.previousFilteredCameras]
                : [...this.cameras];
            this.currentLayout = this.previousLayout || 4;
            this.isFullscreen = false;
            this.focusCameraId = null;
            this.previousLayout = null;
            this.previousFilteredCameras = [];

            // Restart sub stream for the camera
            await this.$nextTick();
            await this.startStream(cameraId, 'sub');
        },
        
        async autoplayAll() {
            this.autoplayingAll = true;
            for (const camera of this.paginatedCameras) {
                if (this.activeStreams[camera.id]) continue;
                try {
                    await this.startStream(camera.id);
                } catch (e) {
                    console.error(`Failed to start camera ${camera.id}:`, e);
                }
                await new Promise(r => setTimeout(r, 300));
            }
            this.autoplayingAll = false;
        },
        
        async stopAllStreams() {
            for (const cameraId of Object.keys(this.activeStreams).filter(id => this.activeStreams[id])) {
                await this.stopStream(cameraId);
            }
        },
        
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
        }
    };
}
</script>
@endsection
