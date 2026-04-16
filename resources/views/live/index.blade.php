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
        aspect-ratio: 16/9;
    }
    .camera-cell video {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .camera-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        padding: 4px 8px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
        z-index: 10;
    }
    .camera-status {
        position: absolute;
        bottom: 4px;
        right: 8px;
        z-index: 10;
    }
    .fullscreen-mode {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        background: #000;
        padding: 8px;
    }
</style>
@endsection

@section('content')
<div x-data="liveMonitor()" x-init="init()">
    <!-- Toolbar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <!-- Building Filter -->
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
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Grid Layout Buttons -->
                <div class="flex bg-slate-100 rounded-lg p-1">
                    <template x-for="layout in [1, 4, 9, 16, 32, 64]" :key="layout">
                        <button @click="setLayout(layout)" 
                                :class="currentLayout === layout ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors">
                            <span x-text="layout"></span>
                        </button>
                    </template>
                </div>
                
                <!-- Fullscreen -->
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
                <div class="camera-cell cursor-pointer" @click="toggleStream(camera)">
                    <!-- Camera Name Overlay -->
                    <div class="camera-overlay">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-white text-xs font-medium truncate" x-text="camera.name"></span>
                                <span x-show="camera.ai_enabled" x-cloak
                                      class="px-1 py-0.5 rounded text-[8px] font-bold bg-purple-500 text-white uppercase leading-none">
                                    AI
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-300" x-text="camera.location"></span>
                        </div>
                    </div>
                    
                    <!-- Video Element -->
                    <video :id="'video-' + camera.id" muted autoplay playsinline
                           class="w-full h-full"
                           x-show="activeStreams[camera.id]"></video>
                    
                    <!-- Placeholder when no stream -->
                    <div x-show="!activeStreams[camera.id]" 
                         class="w-full h-full flex flex-col items-center justify-center text-slate-500">
                        <i class="fas fa-video-slash text-2xl mb-2 opacity-30"></i>
                        <span class="text-xs opacity-50">Click to start stream</span>
                    </div>
                    
                    <!-- Loading indicator -->
                    <div x-show="loadingStreams[camera.id]" 
                         class="absolute inset-0 flex items-center justify-center bg-black/50 z-20">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-spinner fa-spin text-white text-xl mb-2"></i>
                            <span class="text-white text-xs">Connecting...</span>
                        </div>
                    </div>
                    
                    <!-- Camera Actions (snapshot, stop) -->
                    <div x-show="activeStreams[camera.id]" class="absolute bottom-1 left-1 z-10 flex items-center gap-1" @click.stop>
                        <button @click="captureSnapshot(camera.id)" class="w-6 h-6 bg-white/80 hover:bg-white rounded flex items-center justify-center text-slate-700 text-[10px]" title="Capture Snapshot">
                            <i class="fas fa-camera"></i>
                        </button>
                        <button @click="stopStream(camera.id)" class="w-6 h-6 bg-red-500/80 hover:bg-red-500 rounded flex items-center justify-center text-white text-[10px]" title="Stop Stream">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                    
                    <!-- Status -->
                    <div class="camera-status">
                        <span :class="activeStreams[camera.id] ? 'bg-green-500' : 'bg-slate-500'" 
                              class="inline-block w-2 h-2 rounded-full"></span>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Fullscreen close button -->
        <button x-show="isFullscreen" @click="toggleFullscreen()" 
                class="fixed top-4 right-4 z-[10000] bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm">
            <i class="fas fa-times mr-1"></i> Exit Fullscreen
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
function liveMonitor() {
    return {
        cameras: @json($cameras->map(function($cam) {
            $data = $cam->toArray();
            $data['ai_enabled'] = $cam->aiSetting && $cam->aiSetting->ai_enabled;
            return $data;
        })),
        selectedBuilding: '',
        currentLayout: {{ $currentLayout ?? 4 }},
        isFullscreen: false,
        activeStreams: {},
        loadingStreams: {},
        hlsInstances: {},
        filteredCameras: [],
        
        init() {
            this.filteredCameras = [...this.cameras];
        },
        
        get paginatedCameras() {
            return this.filteredCameras.slice(0, this.currentLayout);
        },
        
        get gridStyle() {
            if (this.currentLayout <= 1) return 'display:grid; grid-template-columns: 1fr';
            if (this.currentLayout <= 4) return 'display:grid; grid-template-columns: repeat(2, 1fr); gap: 4px';
            if (this.currentLayout <= 9) return 'display:grid; grid-template-columns: repeat(3, 1fr); gap: 4px';
            if (this.currentLayout <= 16) return 'display:grid; grid-template-columns: repeat(4, 1fr); gap: 4px';
            if (this.currentLayout <= 32) return 'display:grid; grid-template-columns: repeat(8, 1fr); gap: 2px';
            return 'display:grid; grid-template-columns: repeat(8, 1fr); gap: 2px';
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
            if (this.activeStreams[camera.id]) {
                await this.stopStream(camera.id);
            } else {
                await this.startStream(camera.id);
            }
        },
        
        async startStream(cameraId) {
            this.loadingStreams[cameraId] = true;
            
            try {
                const response = await fetch(`/live/stream/${cameraId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success && data.stream_url) {
                    this.activeStreams[cameraId] = true;
                    
                    // Wait for DOM update
                    await this.$nextTick();
                    
                    // Initialize HLS player
                    setTimeout(() => {
                        this.initHlsPlayer(cameraId, data.stream_url);
                    }, 1000);
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
        
        async stopStream(cameraId) {
            try {
                // Destroy HLS instance
                if (this.hlsInstances[cameraId]) {
                    this.hlsInstances[cameraId].destroy();
                    delete this.hlsInstances[cameraId];
                }
                
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
        
        initHlsPlayer(cameraId, url) {
            const video = document.getElementById('video-' + cameraId);
            if (!video) return;
            
            if (Hls.isSupported()) {
                const hls = new Hls({
                    liveDurationInfinity: true,
                    liveBackBufferLength: 0,
                    maxBufferLength: 5,
                    maxMaxBufferLength: 10,
                });
                
                hls.loadSource(url);
                hls.attachMedia(video);
                
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    video.play().catch(e => console.log('Autoplay prevented:', e));
                });
                
                hls.on(Hls.Events.ERROR, (event, data) => {
                    if (data.fatal) {
                        console.error('HLS fatal error:', data);
                        if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                            setTimeout(() => hls.startLoad(), 3000);
                        } else {
                            this.activeStreams[cameraId] = false;
                        }
                    }
                });
                
                this.hlsInstances[cameraId] = hls;
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = url;
                video.play().catch(e => console.log('Autoplay prevented:', e));
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
                    body: JSON.stringify({
                        camera_id: cameraId,
                        from_hls: true,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show brief success notification
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
        
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
        }
    };
}
</script>
@endsection
