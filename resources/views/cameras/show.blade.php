@extends('layouts.app')

@section('title', 'Camera Details')
@section('page-title', 'Camera Details')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Navigation Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('cameras.index') }}" class="text-slate-600 hover:text-slate-800 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="text-lg font-semibold text-slate-800">Camera Details</h2>
        </div>
        
        <!-- Previous/Next Navigation -->
        <div class="flex items-center gap-2">
            @if($previousCamera)
                <a href="{{ route('cameras.show', $previousCamera) }}" 
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
                <a href="{{ route('cameras.show', $nextCamera) }}" 
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
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-semibold text-slate-800">
                <i class="fas fa-camera text-slate-400 mr-2"></i>{{ $camera->name }}
            </h3>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('cameras.check-status', $camera) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="text-sm text-blue-500 hover:text-blue-700" title="Check camera status">
                        <i class="fas fa-sync-alt mr-1"></i>Check Status
                    </button>
                </form>
                <a href="{{ route('cameras.edit', $camera) }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-edit mr-1"></i>Edit</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="text-xs text-slate-500 block">Building</span><span class="text-sm font-medium text-slate-800">{{ $camera->building->name ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">NVR</span><span class="text-sm font-medium text-slate-800">{{ $camera->nvr->name ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">Channel</span><span class="text-sm font-medium text-slate-800">{{ $camera->channel_no }}</span></div>
            <div><span class="text-xs text-slate-500 block">Location</span><span class="text-sm font-medium text-slate-800">{{ $camera->location ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">Status</span>
                @if($camera->status === 'online')
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700"><i class="fas fa-circle text-[6px]"></i> Online</span>
                @elseif($camera->status === 'offline')
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700"><i class="fas fa-circle text-[6px]"></i> Offline</span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"><i class="fas fa-circle text-[6px]"></i> Maintenance</span>
                @endif
            </div>
            <div><span class="text-xs text-slate-500 block">Last Seen</span><span class="text-sm font-medium text-slate-800">{{ $camera->last_seen_at ? $camera->last_seen_at->diffForHumans() : 'Never' }}</span></div>
            <div><span class="text-xs text-slate-500 block">Active</span><span class="text-sm font-medium {{ $camera->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $camera->is_active ? 'Yes' : 'No' }}</span></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500 block">Main Stream URL</span><code class="text-xs bg-slate-100 px-2 py-1 rounded block mt-1">{{ $camera->getMainStreamUrl() ?? 'Not configured' }}</code></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500 block">Sub Stream URL</span><code class="text-xs bg-slate-100 px-2 py-1 rounded block mt-1">{{ $camera->getSubStreamUrl() ?? 'Not configured' }}</code></div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="flex items-center justify-between px-4">
        @if($previousCamera)
            <a href="{{ route('cameras.show', $previousCamera) }}" 
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
            <a href="{{ route('cameras.show', $nextCamera) }}" 
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
            window.location.href = "{{ route('cameras.show', $previousCamera) }}";
        @endif
    }
    // Alt + Right Arrow = Next
    if (e.altKey && e.key === 'ArrowRight') {
        e.preventDefault();
        @if($nextCamera)
            window.location.href = "{{ route('cameras.show', $nextCamera) }}";
        @endif
    }
});
</script>
@endsection
@endsection
