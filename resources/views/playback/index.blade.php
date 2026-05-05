@extends('layouts.app')

@section('title', 'Playback')
@section('page-title', 'Playback')

@section('styles')
<style>
    #nvr-playback-container video { width:100%;height:100%;object-fit:contain;background:#000;display:block; }

    /* Timeline */
    .tl-wrap { position:relative;user-select:none; }
    .tl-ruler { position:relative;height:22px;overflow:hidden; }
    .tl-tick { position:absolute;top:0;width:1px;background:#94a3b8; }
    .tl-tick.major { height:14px; }
    .tl-tick.minor { height:7px;top:7px; }
    .tl-tick-label { position:absolute;top:1px;font-size:9px;color:#64748b;transform:translateX(-50%);white-space:nowrap; }

    .tl-bar { position:relative;height:36px;background:#0f172a;border-radius:6px;overflow:hidden;cursor:pointer; }
    .tl-recording { position:absolute;top:6px;bottom:6px;background:#22c55e;border-radius:3px;opacity:0.7;pointer-events:none; }

    .tl-cursor { position:absolute;top:0;bottom:0;width:2px;background:#ef4444;z-index:10;pointer-events:none;transition:left 0.3s linear; }
    .tl-cursor-head { position:absolute;top:-5px;left:-5px;width:12px;height:12px;background:#ef4444;border-radius:50%;border:2px solid #fff;box-shadow:0 0 4px rgba(0,0,0,0.4); }

    .tl-hover { position:absolute;top:-24px;background:#1e293b;color:#fff;font-size:10px;padding:2px 8px;border-radius:4px;transform:translateX(-50%);pointer-events:none;white-space:nowrap;z-index:20; }

    .tl-bar-drag { cursor:grabbing !important; }

    .cam-btn { transition:all 0.15s; }
    .cam-btn:hover { background:#f1f5f9; }
    .cam-btn.active { background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe; }
</style>
@endsection

@section('content')
<div x-data="playbackApp()" x-init="init()" class="flex flex-col" style="height:calc(100vh - 140px)">
    <!-- Top row: sidebar + video -->
    <div class="flex flex-1 gap-4 min-h-0 mb-3">
        <!-- Left: camera list + date -->
        <div class="w-60 flex-shrink-0 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
            <div class="p-3 flex-1 overflow-y-auto">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase mb-2">Camera</h3>

                @foreach($camerasGrouped as $buildingName => $nvrs)
                <div class="mb-3">
                    {{-- Building header --}}
                    <div class="text-[9px] font-bold text-slate-400 uppercase px-2 mb-1">{{ $buildingName }}</div>

                    @foreach($nvrs as $nvrName => $cams)
                    {{-- NVR collapsible group --}}
                    <div x-data="{ open: false }" class="mb-1">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-2 py-1.5 rounded text-[11px] font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                            <span class="flex items-center gap-1.5 truncate">
                                <i class="fas fa-server text-[9px] text-slate-400"></i>
                                {{ $nvrName }}
                                <span class="text-[9px] text-slate-400 font-normal">({{ $cams->count() }})</span>
                            </span>
                            <i class="fas fa-chevron-right text-[8px] text-slate-400 transition-transform duration-200" :class="open && 'rotate-90'"></i>
                        </button>

                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="pl-2 mt-0.5 space-y-0.5">
                            @foreach($cams as $cam)
                            <button @click="selectCamera({{ $cam->id }}, '{{ addslashes($cam->name) }}')"
                                    :class="selectedCameraId === {{ $cam->id }} ? 'active' : ''"
                                    class="cam-btn w-full text-left text-[11px] px-2 py-1 rounded border border-transparent truncate">
                                <i class="fas fa-video text-[8px] mr-1" :class="selectedCameraId === {{ $cam->id }} ? 'text-blue-500' : 'text-slate-300'"></i>{{ $cam->name }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
            <div class="p-3 border-t border-slate-200">
                <label class="text-[10px] font-bold text-slate-400 uppercase">Date</label>
                <input type="date" x-model="selectedDate" @change="onDateChange()"
                       class="w-full text-sm border border-slate-300 rounded-lg px-2 py-1.5 mt-1 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Center: video -->
        <div class="flex-1 bg-slate-900 rounded-xl overflow-hidden relative flex items-center justify-center min-h-0">
            <div id="nvr-playback-container" x-show="nvrPlaying" x-cloak class="w-full h-full"></div>

            <!-- Placeholder -->
            <div x-show="!nvrPlaying && !loading" class="text-center text-slate-500 px-8">
                <i class="fas fa-film text-5xl mb-4 opacity-20"></i>
                <p class="text-sm opacity-60">Select a camera, pick a date, then click on the timeline below</p>
            </div>

            <!-- Loading -->
            <div x-show="loading" x-cloak class="absolute inset-0 flex items-center justify-center bg-black/70 z-20">
                <div class="text-center"><i class="fas fa-spinner fa-spin text-white text-3xl mb-3"></i><p class="text-white text-sm">Connecting to NVR...</p></div>
            </div>

            <!-- Overlay info -->
            <div x-show="nvrPlaying" x-cloak class="absolute top-3 left-3 z-10 flex items-center gap-2 pointer-events-none">
                <span class="bg-black/70 text-white text-[11px] px-2 py-1 rounded font-medium" x-text="selectedCameraName"></span>
                <span class="bg-red-600 text-white text-[11px] px-2 py-1 rounded font-bold">
                    <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i><span x-text="playbackTimeDisplay"></span>
                </span>
                <span x-show="playbackSpeed !== 1" class="bg-blue-600 text-white text-[11px] px-2 py-1 rounded font-bold">
                    <i class="fas fa-forward text-[8px] mr-1"></i><span x-text="playbackSpeed + 'x'"></span>
                </span>
            </div>

            <!-- Stop button -->
            <button x-show="nvrPlaying" x-cloak @click="stopPlayback()"
                    class="absolute top-3 right-3 z-10 bg-red-500/80 hover:bg-red-600 text-white text-xs px-3 py-1.5 rounded-lg transition-colors">
                <i class="fas fa-stop mr-1"></i>Stop
            </button>
        </div>
    </div>

    <!-- Bottom: Timeline -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 px-4 py-3 flex-shrink-0">
        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-700"><i class="fas fa-clock text-slate-400 mr-1"></i>Timeline</span>
                <span x-show="selectedDate" x-cloak class="text-[11px] text-slate-400" x-text="selectedDate"></span>
                <span x-show="nvrPlaying" x-cloak class="text-[10px] text-green-600 font-medium"><i class="fas fa-circle text-[5px] mr-1"></i>Playing</span>
            </div>
            <div class="flex items-center gap-3">
                <!-- Playback Speed Controls -->
                <div x-show="nvrPlaying" x-cloak class="flex items-center gap-1 border-r border-slate-200 pr-3">
                    <span class="text-[10px] font-medium text-slate-500 mr-1">Speed:</span>
                    <template x-for="speed in playbackSpeeds" :key="speed">
                        <button @click="setPlaybackSpeed(speed)"
                                :class="playbackSpeed === speed ? 'bg-blue-500 text-white border-blue-500' : 'border-slate-300 text-slate-600 hover:bg-slate-100'"
                                class="px-2 py-0.5 text-[10px] rounded border transition-colors font-medium"
                                x-text="speed + 'x'"></button>
                    </template>
                </div>

                <!-- Zoom Controls -->
                <template x-for="z in zoomLevels" :key="z.hours">
                    <button @click="setZoom(z.hours)"
                            :class="zoomHours === z.hours ? 'bg-blue-500 text-white border-blue-500' : 'border-slate-300 text-slate-600 hover:bg-slate-100'"
                            class="px-2 py-0.5 text-[10px] rounded border transition-colors font-medium"
                            x-text="z.label"></button>
                </template>
            </div>
        </div>

        <!-- Timeline -->
        <div class="tl-wrap">
            <!-- Ruler -->
            <div class="tl-ruler">
                <template x-for="t in rulerTicks" :key="t.pos">
                    <div>
                        <div class="tl-tick" :class="t.major?'major':'minor'" :style="'left:'+t.pos+'%'"></div>
                        <template x-if="t.label"><span class="tl-tick-label" :style="'left:'+t.pos+'%'" x-text="t.label"></span></template>
                    </div>
                </template>
            </div>

            <!-- Bar -->
            <div class="tl-bar" x-ref="tlBar"
                 @mousedown="onTlMouseDown($event)"
                 @mousemove="onTlMouseMove($event)"
                 @mouseleave="hoverTime=null"
                 @mouseup="onTlMouseUp($event)">

                <!-- Green recording block (full 24h for NVR) -->
                <div x-show="selectedCameraId && selectedDate" x-cloak
                     class="tl-recording" style="left:0%;width:100%"></div>

                <!-- Playback cursor -->
                <template x-if="cursorPct !== null">
                    <div class="tl-cursor" :style="'left:'+cursorPct+'%'">
                        <div class="tl-cursor-head"></div>
                    </div>
                </template>

                <!-- Hover tooltip -->
                <div x-show="hoverTime" x-cloak class="tl-hover" :style="'left:'+hoverPct+'%'" x-text="hoverTime"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function playbackApp() {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const HDR  = {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'};
    const GO2RTC = @json($go2rtcApiUrl);

    return {
        go2rtcApiUrl: GO2RTC,
        selectedCameraId: null,
        selectedCameraName: '',
        selectedDate: new Date().toISOString().split('T')[0],
        loading: false,
        nvrPlaying: false,
        nvrVideoEl: null,
        nvrWsEl: null,
        playbackSpeed: 1,
        playbackSpeeds: [0.5, 1, 2, 4, 8],

        // Timeline
        cursorPct: null,
        hoverPct: 0,
        hoverTime: null,
        isDragging: false,
        playbackTimeDisplay: '--:--:--',
        playbackStartTime: null,   // Date object: the NVR start time of current stream
        playbackStreamStart: null, // performance.now() when stream started receiving data

        // Zoom: view window in hours
        zoomHours: 24,
        zoomStart: 0, // start hour of visible window
        zoomLevels: [
            { label:'24h', hours:24 },
            { label:'12h', hours:12 },
            { label:'6h',  hours:6 },
            { label:'3h',  hours:3 },
            { label:'1h',  hours:1 },
        ],

        init() {
            window.addEventListener('beforeunload', () => this.stopPlayback());
            // Update cursor every 500ms during playback
            setInterval(() => this.updateCursorDuringPlayback(), 500);
        },

        // ── Camera / Date ───────────────────────────────────
        selectCamera(id, name) {
            if (this.selectedCameraId === id) return;
            this.stopPlayback();
            this.selectedCameraId = id;
            this.selectedCameraName = name;
            this.cursorPct = null;
        },

        onDateChange() {
            this.stopPlayback();
            this.cursorPct = null;
        },

        // ── Zoom ────────────────────────────────────────────
        setZoom(hours) {
            if (this.zoomHours === hours) {
                // Shift window forward
                const next = this.zoomStart + hours;
                this.zoomStart = next >= 24 ? 0 : next;
            } else {
                this.zoomHours = hours;
                // Center on cursor if playing
                if (this.cursorPct !== null) {
                    const curMin = (this.cursorPct / 100) * (this.zoomEnd - this.zoomStart) * 60 + this.zoomStart * 60;
                    const halfSpan = hours * 30;
                    this.zoomStart = Math.max(0, Math.min(24 - hours, Math.floor((curMin - halfSpan) / 60)));
                } else {
                    this.zoomStart = 0;
                }
            }
        },

        get zoomEnd() { return Math.min(24, this.zoomStart + this.zoomHours); },

        // ── Ruler ticks ─────────────────────────────────────
        get rulerTicks() {
            const ticks = [];
            const span = this.zoomEnd - this.zoomStart;
            const step = span <= 1 ? 5 : span <= 3 ? 10 : span <= 6 ? 30 : 60;
            const totalMin = span * 60;

            for (let m = this.zoomStart * 60; m <= this.zoomEnd * 60; m += step) {
                const h = Math.floor(m / 60), mm = m % 60;
                const pct = ((m - this.zoomStart * 60) / totalMin) * 100;
                const major = mm === 0;
                const label = major ? `${String(h).padStart(2,'0')}:00` : (step <= 10 ? `${String(h).padStart(2,'0')}:${String(mm).padStart(2,'0')}` : null);
                ticks.push({ pos: pct, major, label });
            }
            return ticks;
        },

        // ── Time ↔ Position ─────────────────────────────────
        minutesToPct(totalMinutes) {
            const spanMin = (this.zoomEnd - this.zoomStart) * 60;
            const offset = totalMinutes - this.zoomStart * 60;
            return Math.max(0, Math.min(100, (offset / spanMin) * 100));
        },

        pctToMinutes(pct) {
            const spanMin = (this.zoomEnd - this.zoomStart) * 60;
            return (pct / 100) * spanMin + this.zoomStart * 60;
        },

        pctToTimeStr(pct) {
            const min = this.pctToMinutes(pct);
            const h = Math.floor(min / 60), m = Math.floor(min % 60), s = Math.floor((min % 1) * 60);
            return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        },

        minutesToTimeStr(min) {
            const h = Math.floor(min / 60), m = Math.floor(min % 60), s = Math.floor((min % 1) * 60);
            return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        },

        // ── Timeline mouse events ───────────────────────────
        _getPct(e) {
            const rect = this.$refs.tlBar.getBoundingClientRect();
            return Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
        },

        onTlMouseDown(e) {
            if (!this.selectedCameraId || !this.selectedDate) return;
            this.isDragging = true;
            this.$refs.tlBar.classList.add('tl-bar-drag');
            const pct = this._getPct(e);
            this.cursorPct = pct;
            this.playbackTimeDisplay = this.pctToTimeStr(pct);
        },

        onTlMouseMove(e) {
            const pct = this._getPct(e);
            this.hoverPct = pct;
            this.hoverTime = this.pctToTimeStr(pct);

            if (this.isDragging) {
                this.cursorPct = pct;
                this.playbackTimeDisplay = this.pctToTimeStr(pct);
            }
        },

        onTlMouseUp(e) {
            if (!this.isDragging) return;
            this.isDragging = false;
            this.$refs.tlBar.classList.remove('tl-bar-drag');

            const pct = this._getPct(e);
            const timeStr = this.pctToTimeStr(pct);
            this.playFromTime(timeStr);
        },

        // ── NVR Playback ────────────────────────────────────
        async playFromTime(timeStr) {
            if (!this.selectedCameraId || !this.selectedDate) return;
            this.loading = true;
            this.stopPlayback();

            // Calculate end time: from clicked time to end of day
            const startMin = this._timeStrToMinutes(timeStr);
            const endMin = 24 * 60 - 1; // 23:59
            const endStr = this.minutesToTimeStr(endMin);

            try {
                const res = await fetch('/playback/play', {
                    method: 'POST', headers: HDR,
                    body: JSON.stringify({
                        camera_id: this.selectedCameraId,
                        date: this.selectedDate,
                        start_time: timeStr.substring(0, 5), // HH:MM
                        end_time: endStr.substring(0, 5),
                    }),
                });
                const data = await res.json();
                if (!data.success || !data.stream_name) {
                    alert(data.message || 'Failed to start playback');
                    return;
                }

                this.nvrPlaying = true;
                this.playbackStartTime = this._timeStrToDate(timeStr);
                this.playbackStreamStart = null; // will be set when first data arrives
                this.playbackTimeDisplay = timeStr;
                this.cursorPct = this.minutesToPct(startMin);

                await this.$nextTick();
                this._createMsePlayer(data.stream_name);
            } catch (e) {
                console.error('Playback error:', e);
                alert('Failed to connect to NVR');
            } finally {
                this.loading = false;
            }
        },

        _timeStrToMinutes(ts) {
            const p = ts.split(':');
            return parseInt(p[0]) * 60 + parseInt(p[1]) + (p[2] ? parseInt(p[2]) / 60 : 0);
        },

        _timeStrToDate(ts) {
            const p = ts.split(':');
            const d = new Date();
            d.setHours(parseInt(p[0]), parseInt(p[1]), p[2] ? parseInt(p[2]) : 0, 0);
            return d;
        },

        // Update cursor position during playback based on elapsed time and speed
        updateCursorDuringPlayback() {
            if (!this.nvrPlaying || !this.playbackStartTime || !this.playbackStreamStart) return;

            const elapsedSec = (performance.now() - this.playbackStreamStart) / 1000;
            // Multiply by playback speed to get actual video time elapsed
            const videoElapsedSec = elapsedSec * this.playbackSpeed;
            const currentMin = this._timeStrToMinutes(
                `${String(this.playbackStartTime.getHours()).padStart(2,'0')}:${String(this.playbackStartTime.getMinutes()).padStart(2,'0')}:${String(this.playbackStartTime.getSeconds()).padStart(2,'0')}`
            ) + videoElapsedSec / 60;

            this.cursorPct = this.minutesToPct(currentMin);
            this.playbackTimeDisplay = this.minutesToTimeStr(currentMin);
        },

        stopPlayback() {
            this._destroyMsePlayer();
            if (this.nvrPlaying && this.selectedCameraId) {
                fetch('/playback/stop', { method:'DELETE', headers:HDR, body:JSON.stringify({camera_id:this.selectedCameraId}) }).catch(()=>{});
            }
            this.nvrPlaying = false;
            this.playbackStartTime = null;
            this.playbackStreamStart = null;
            this.playbackSpeed = 1; // Reset speed on stop
        },

        setPlaybackSpeed(speed) {
            this.playbackSpeed = speed;
            if (this.nvrVideoEl) {
                this.nvrVideoEl.playbackRate = speed;
            }
        },

        // ── MSE Player (go2rtc WebSocket) ───────────────────
        _createMsePlayer(streamName) {
            this._destroyMsePlayer();
            const container = document.getElementById('nvr-playback-container');
            if (!container) return;

            const video = document.createElement('video');
            video.autoplay = true; video.playsInline = true; video.muted = true; video.controls = true;
            video.playbackRate = this.playbackSpeed; // Apply current speed
            Object.assign(video.style, { width:'100%', height:'100%', objectFit:'contain', background:'#000' });
            container.appendChild(video);

            const wsUrl = GO2RTC.replace('http','ws') + '/api/ws?src=' + encodeURIComponent(streamName);
            const ws = new WebSocket(wsUrl);
            ws.binaryType = 'arraybuffer';
            let ms, sb, buf, bufLen = 0;
            const self = this;

            function codecs() {
                const M = window.ManagedMediaSource || window.MediaSource;
                if (!M) return '';
                return ['avc1.640029','avc1.64002A','avc1.640033','hvc1.1.6.L153.B0','mp4a.40.2','mp4a.40.5','flac','opus']
                    .filter(c => M.isTypeSupported(`video/mp4; codecs="${c}"`)).join();
            }

            ws.onopen = () => {
                const M = window.ManagedMediaSource || window.MediaSource;
                ms = new M();
                ms.addEventListener('sourceopen', () => {
                    ws.send(JSON.stringify({ type:'mse', value:codecs() }));
                }, { once:true });

                if (window.ManagedMediaSource) { video.disableRemotePlayback = true; video.srcObject = ms; }
                else { video.src = URL.createObjectURL(ms); video.srcObject = null; }
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
                                if (!sb.updating && bufLen > 0) {
                                    try { sb.appendBuffer(buf.slice(0, bufLen)); bufLen = 0; } catch {}
                                }
                                if (!sb.updating && sb.buffered && sb.buffered.length) {
                                    const end = sb.buffered.end(sb.buffered.length - 1);
                                    const s0 = sb.buffered.start(0);
                                    if (end - s0 > 10) try { sb.remove(s0, end - 5); } catch {}
                                    // Keep video near live edge but respect user's playback speed
                                    if (video.currentTime < end - 3) video.currentTime = end - 0.5;
                                }
                            });

                            // Mark stream start time for cursor tracking
                            self.playbackStreamStart = performance.now();
                        } catch (e) { console.error('MSE:', e); }
                    }
                } else if (sb) {
                    if (sb.updating || bufLen > 0) {
                        const b = new Uint8Array(ev.data);
                        if (bufLen + b.byteLength <= buf.byteLength) { buf.set(b, bufLen); bufLen += b.byteLength; }
                    } else {
                        try { sb.appendBuffer(ev.data); } catch {}
                    }
                }
            };

            ws.onerror = () => {};
            ws.onclose = () => {};
            this.nvrVideoEl = video;
            this.nvrWsEl = ws;
        },

        _destroyMsePlayer() {
            if (this.nvrWsEl && this.nvrWsEl.readyState <= 1) this.nvrWsEl.close();
            if (this.nvrVideoEl) { this.nvrVideoEl.pause(); this.nvrVideoEl.src = ''; this.nvrVideoEl.srcObject = null; this.nvrVideoEl.load(); this.nvrVideoEl.remove(); }
            this.nvrVideoEl = null; this.nvrWsEl = null;
            const c = document.getElementById('nvr-playback-container'); if (c) c.innerHTML = '';
        },

        formatDuration(s) {
            if (!s) return '-';
            const h = Math.floor(s/3600), m = Math.floor((s%3600)/60);
            return h > 0 ? `${h}h ${m}m` : `${m}m`;
        },
    };
}
</script>
@endsection
