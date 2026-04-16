@extends('layouts.app')
@section('title', 'Snapshots')
@section('page-title', 'Snapshots')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('snapshots.index') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ request('date') }}" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
        </form>
        <span class="text-sm text-slate-500">{{ $snapshots->total() }} snapshot(s)</span>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($snapshots as $snapshot)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden group">
            <div class="aspect-video bg-slate-900 relative">
                <img src="{{ $snapshot->getUrl() }}" alt="{{ $snapshot->file_name }}" class="w-full h-full object-cover" loading="lazy">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <div class="flex items-center gap-2">
                        <a href="{{ $snapshot->getUrl() }}" target="_blank" class="w-8 h-8 bg-white/90 rounded-full flex items-center justify-center text-slate-700 hover:bg-white text-sm"><i class="fas fa-expand"></i></a>
                        <a href="{{ route('snapshots.download', $snapshot) }}" class="w-8 h-8 bg-white/90 rounded-full flex items-center justify-center text-slate-700 hover:bg-white text-sm"><i class="fas fa-download"></i></a>
                        <form method="POST" action="{{ route('snapshots.destroy', $snapshot) }}" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="w-8 h-8 bg-red-500/90 rounded-full flex items-center justify-center text-white hover:bg-red-600 text-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="p-2">
                <p class="text-xs font-medium text-slate-800 truncate">{{ $snapshot->camera->name ?? '-' }}</p>
                <p class="text-[10px] text-slate-400">{{ $snapshot->created_at->format('d M Y H:i:s') }}</p>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-slate-400">
            <i class="fas fa-camera-retro text-3xl mb-3 block opacity-30"></i>
            <p class="text-sm">No snapshots captured yet</p>
            <p class="text-xs mt-1">Use the capture button on the Live Monitoring page</p>
        </div>
        @endforelse
    </div>

    @if($snapshots->hasPages())
    <div class="mt-4">{{ $snapshots->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection
