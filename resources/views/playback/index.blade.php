@extends('layouts.app')

@section('title', 'Playback')
@section('page-title', 'Playback')

@section('styles')
<style>
    #playback-video {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        background: #000;
    }
</style>
@endsection

@section('content')
<div x-data="playbackPlayer()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Controls Panel -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-800 mb-4">
                    <i class="fas fa-sliders-h text-slate-400 mr-2"></i>Playback Controls
                </h3>
                
                <div class="space-y-4">
                    <!-- Camera Select -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Camera</label>
                        <select x-model="selectedCamera" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select Camera</option>
                            @foreach($cameras as $buildingName => $buildingCameras)
                            <optgroup label="{{ $buildingName }}">
                                @foreach($buildingCameras as $camera)
                                <option value="{{ $camera->id }}">{{ $camera->name }} - {{ $camera->location }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                        <input type="date" x-model="selectedDate" 
                               class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <!-- Start Time -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Start Time</label>
                        <input type="time" x-model="startTime" 
                               class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <!-- End Time -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">End Time</label>
                        <input type="time" x-model="endTime" 
                               class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <!-- Play Button -->
                    <button @click="playRecording()" 
                            :disabled="!selectedCamera || !selectedDate || !startTime || !endTime || isLoading"
                            class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                        <span x-text="isLoading ? 'Loading...' : 'Play Recording'"></span>
                    </button>

                    <!-- Stop Button -->
                    <button x-show="isPlaying" @click="stopPlayback()"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-stop"></i>
                        <span>Stop Playback</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Video Player -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 aspect-video relative">
                    <!-- Video container -->
                    <div id="playback-video-container" class="w-full h-full" x-show="isPlaying"></div>
                    
                    <!-- Placeholder -->
                    <div x-show="!isPlaying && !isLoading" class="absolute inset-0 flex flex-col items-center justify-center text-slate-500">
                        <i class="fas fa-film text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm opacity-50">Select camera and time range to start playback</p>
                    </div>

                    <!-- Loading -->
                    <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 z-20">
                        <i class="fas fa-spinner fa-spin text-white text-3xl mb-3"></i>
                        <span class="text-white text-sm">Connecting to playback stream...</span>
                    </div>
                    
                    <!-- Playback Info Overlay -->
                    <div x-show="playbackInfo" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 pointer-events-none">
                        <p class="text-white text-sm" x-text="playbackInfo"></p>
                    </div>

                    <!-- Error -->
                    <div x-show="errorMessage" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 z-20">
                        <i class="fas fa-exclamation-triangle text-red-400 text-3xl mb-3"></i>
                        <span class="text-red-300 text-sm" x-text="errorMessage"></span>
                        <button @click="errorMessage = ''" class="mt-3 px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs">Dismiss</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function playbackPlayer() {
    return {
        go2rtcApiUrl: @json($go2rtcApiUrl),
        selectedCamera: '',
        selectedDate: new Date().toISOString().split('T')[0],
        startTime: '00:00',
        endTime: '23:59',
        isLoading: false,
        isPlaying: false,
        playbackInfo: '',
        errorMessage: '',
        activeCameraId: null,
        videoEl: null,
        wsEl: null,
        msEl: null,
        
        init() {
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => this.stopPlayback());
        },

        async playRecording() {
            if (!this.selectedCamera || !this.selectedDate || !this.startTime || !this.endTime) {
                alert('Please fill in all fields');
                return;
            }
            
            this.errorMessage = '';
            this.isLoading = true;
            
            // Stop existing playback first
            if (this.isPlaying) {
                await this.stopPlayback();
            }

            try {
                const response = await fetch('/playback/play', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        camera_id: this.selectedCamera,
                        date: this.selectedDate,
                        start_time: this.startTime,
                        end_time: this.endTime,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success && data.stream_name) {
                    this.activeCameraId = this.selectedCamera;
                    this.playbackInfo = `${data.camera_name} | ${data.date} ${data.start_time} - ${data.end_time}`;
                    this.isPlaying = true;

                    await this.$nextTick();
                    this.createVideoElement(data.stream_name);
                } else {
                    this.errorMessage = data.message || 'Failed to load playback';
                }
            } catch (error) {
                console.error('Playback error:', error);
                this.errorMessage = 'Failed to connect to playback service';
            } finally {
                this.isLoading = false;
            }
        },

        createVideoElement(streamName) {
            this.destroyVideoElement();

            const container = document.getElementById('playback-video-container');
            if (!container) return;

            const video = document.createElement('video');
            video.id = 'playback-video';
            video.autoplay = true;
            video.playsInline = true;
            video.muted = true;
            video.controls = true;
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            video.style.display = 'block';
            video.style.background = '#000';
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
                return CODECS.filter(c => MS.isTypeSupported(`video/mp4; codecs="${c}"`)).join();
            }

            let ms, sb, buf, bufLen = 0;
            const self = this;

            ws.onopen = () => {
                console.log('[go2rtc] Playback WS open');

                const MS = window.ManagedMediaSource || window.MediaSource;
                ms = new MS();
                self.msEl = ms;

                ms.addEventListener('sourceopen', () => {
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
                        console.log(`[go2rtc] Playback MSE codec: ${msg.value}`);
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
                                    const gap = end - video.currentTime;
                                    video.playbackRate = gap > 0.1 ? gap : 0.1;
                                }
                            });
                        } catch (e) {
                            console.error('[go2rtc] Playback addSourceBuffer error:', e);
                        }
                    } else if (msg.type === 'error') {
                        console.error('[go2rtc] Playback error:', msg.value);
                        self.errorMessage = 'Stream error: ' + msg.value;
                    }
                } else {
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

            ws.onerror = (e) => {
                console.error('[go2rtc] Playback WS error:', e);
                self.errorMessage = 'WebSocket connection error';
            };
            ws.onclose = () => console.log('[go2rtc] Playback WS closed');

            this.videoEl = video;
            this.wsEl = ws;
        },

        destroyVideoElement() {
            if (this.wsEl && this.wsEl.readyState <= 1) this.wsEl.close();
            if (this.videoEl) {
                this.videoEl.pause();
                this.videoEl.src = '';
                this.videoEl.srcObject = null;
                this.videoEl.load();
                this.videoEl.remove();
            }
            this.videoEl = null;
            this.wsEl = null;
            this.msEl = null;

            const container = document.getElementById('playback-video-container');
            if (container) container.innerHTML = '';
        },

        async stopPlayback() {
            this.destroyVideoElement();

            if (this.activeCameraId) {
                try {
                    await fetch('/playback/stop', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ camera_id: this.activeCameraId }),
                    });
                } catch (e) {
                    console.error('Stop playback error:', e);
                }
            }

            this.isPlaying = false;
            this.activeCameraId = null;
            this.playbackInfo = '';
        },
    };
}
</script>
@endsection
