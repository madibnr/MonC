@extends('layouts.app')

@section('title', 'Edit Camera')
@section('page-title', 'Edit Camera')

@section('content')
<div class="max-w-2xl">
    <!-- Navigation Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('cameras.index') }}" class="text-slate-600 hover:text-slate-800 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="text-lg font-semibold text-slate-800">Edit Camera</h2>
        </div>
        
        <!-- Previous/Next Navigation -->
        <div class="flex items-center gap-2">
            @if($previousCamera)
                <a href="{{ route('cameras.edit', $previousCamera) }}" 
                   class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors"
                   title="Previous: {{ $previousCamera->name }}">
                    <i class="fas fa-chevron-left"></i>
                    <span class="hidden sm:inline">Previous</span>
                </a>
            @else
                <span class="flex items-center gap-2 px-3 py-2 text-sm text-slate-400 cursor-not-allowed">
                    <i class="fas fa-chevron-left"></i>
                    <span class="hidden sm:inline">Previous</span>
                </span>
            @endif

            <span class="text-xs text-slate-500 px-2">
                Camera #{{ $camera->id }}
            </span>

            @if($nextCamera)
                <a href="{{ route('cameras.edit', $nextCamera) }}" 
                   class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors"
                   title="Next: {{ $nextCamera->name }}">
                    <span class="hidden sm:inline">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="flex items-center gap-2 px-3 py-2 text-sm text-slate-400 cursor-not-allowed">
                    <span class="hidden sm:inline">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-6">
            <i class="fas fa-camera text-slate-400 mr-2"></i>{{ $camera->name }}
        </h3>

        <form method="POST" action="{{ route('cameras.update', $camera) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Building <span class="text-red-500">*</span></label>
                    <select name="building_id" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        @foreach($buildings as $building)
                        <option value="{{ $building->id }}" {{ old('building_id', $camera->building_id) == $building->id ? 'selected' : '' }}>{{ $building->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">NVR <span class="text-red-500">*</span></label>
                    <select name="nvr_id" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        @foreach($nvrs as $nvr)
                        <option value="{{ $nvr->id }}" {{ old('nvr_id', $camera->nvr_id) == $nvr->id ? 'selected' : '' }}>{{ $nvr->name }} ({{ $nvr->ip_address }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Channel No <span class="text-red-500">*</span></label>
                    <input type="number" name="channel_no" value="{{ old('channel_no', $camera->channel_no) }}" min="1" max="128" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Camera Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $camera->name) }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" name="location" value="{{ old('location', $camera->location) }}" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">{{ old('description', $camera->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Custom Stream URL</label>
                    <input type="text" name="stream_url" value="{{ old('stream_url', $camera->stream_url) }}" placeholder="rtsp://..." class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Custom Sub-Stream URL</label>
                    <input type="text" name="sub_stream_url" value="{{ old('sub_stream_url', $camera->sub_stream_url) }}" placeholder="rtsp://..." class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select name="status" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="online" {{ old('status', $camera->status) == 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ old('status', $camera->status) == 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="maintenance" {{ old('status', $camera->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $camera->is_active) ? 'checked' : '' }} class="w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-200">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Update Camera
                </button>
                <a href="{{ route('cameras.index') }}" class="text-sm text-slate-600 hover:text-slate-800">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Bottom Navigation (for easier access) -->
    <div class="flex items-center justify-between mt-4 px-4">
        @if($previousCamera)
            <a href="{{ route('cameras.edit', $previousCamera) }}" 
               class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-800 transition-colors group">
                <i class="fas fa-chevron-left group-hover:-translate-x-1 transition-transform"></i>
                <div class="text-left">
                    <div class="text-xs text-slate-500">Previous</div>
                    <div class="font-medium">{{ $previousCamera->name }}</div>
                </div>
            </a>
        @else
            <div></div>
        @endif

        @if($nextCamera)
            <a href="{{ route('cameras.edit', $nextCamera) }}" 
               class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-800 transition-colors group">
                <div class="text-right">
                    <div class="text-xs text-slate-500">Next</div>
                    <div class="font-medium">{{ $nextCamera->name }}</div>
                </div>
                <i class="fas fa-chevron-right group-hover:translate-x-1 transition-transform"></i>
            </a>
        @endif
    </div>
</div>

@section('scripts')
<script>
// Keyboard shortcuts for navigation
document.addEventListener('keydown', function(e) {
    // Alt + Left Arrow = Previous
    if (e.altKey && e.key === 'ArrowLeft') {
        e.preventDefault();
        @if($previousCamera)
            window.location.href = "{{ route('cameras.edit', $previousCamera) }}";
        @endif
    }
    // Alt + Right Arrow = Next
    if (e.altKey && e.key === 'ArrowRight') {
        e.preventDefault();
        @if($nextCamera)
            window.location.href = "{{ route('cameras.edit', $nextCamera) }}";
        @endif
    }
});
</script>
@endsection
@endsection
