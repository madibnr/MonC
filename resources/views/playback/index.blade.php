@extends('layouts.app')

@section('title', 'Playback')
@section('page-title', 'Playback')

@section('content')
<div x-data="playbackPlayer()">
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
                </div>
            </div>
        </div>
        
        <!-- Video Player -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 aspect-video flex items-center justify-center relative">
                    <video id="playback-video" controls class="w-full h-full" x-show="isPlaying" style="display:none;"></video>
                    
                    <div x-show="!isPlaying" class="text-center text-slate-500">
                        <i class="fas fa-film text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm opacity-50">Select camera and time range to start playback</p>
                    </div>
                    
                    <!-- Playback Info -->
                    <div x-show="playbackInfo" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                        <p class="text-white text-sm" x-text="playbackInfo"></p>
                    </div>
                </div>
                
                <!-- Playback Details -->
                <div x-show="playbackUrl" class="p-4 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            <span class="font-medium">RTSP Playback URL:</span>
                            <code class="ml-2 text-xs bg-slate-100 px-2 py-1 rounded" x-text="playbackUrl"></code>
                        </div>
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
        selectedCamera: '',
        selectedDate: new Date().toISOString().split('T')[0],
        startTime: '00:00',
        endTime: '23:59',
        isLoading: false,
        isPlaying: false,
        playbackUrl: '',
        playbackInfo: '',
        
        async playRecording() {
            if (!this.selectedCamera || !this.selectedDate || !this.startTime || !this.endTime) {
                alert('Please fill in all fields');
                return;
            }
            
            this.isLoading = true;
            
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
                
                if (data.success) {
                    this.playbackUrl = data.playback_url;
                    this.playbackInfo = `Camera: ${data.camera_name} | ${data.date} ${data.start_time} - ${data.end_time}`;
                    this.isPlaying = true;
                    
                    // Note: RTSP playback URL is shown for reference
                    // Direct browser playback would require server-side transcoding
                } else {
                    alert(data.message || 'Failed to load playback');
                }
            } catch (error) {
                console.error('Playback error:', error);
                alert('Failed to connect to playback service');
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>
@endsection
