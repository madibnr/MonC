@extends('layouts.app')

@section('title', 'Camera Details')
@section('page-title', 'Camera Details')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-semibold text-slate-800">
                <i class="fas fa-camera text-slate-400 mr-2"></i>{{ $camera->name }}
            </h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('cameras.edit', $camera) }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-edit mr-1"></i>Edit</a>
                <a href="{{ route('cameras.index') }}" class="text-sm text-slate-500 hover:text-slate-700"><i class="fas fa-arrow-left mr-1"></i>Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="text-xs text-slate-500 block">Building</span><span class="text-sm font-medium text-slate-800">{{ $camera->building->name ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">NVR</span><span class="text-sm font-medium text-slate-800">{{ $camera->nvr->name ?? '-' }} ({{ $camera->nvr->ip_address ?? '-' }})</span></div>
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
            <div><span class="text-xs text-slate-500 block">Active</span><span class="text-sm font-medium {{ $camera->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $camera->is_active ? 'Yes' : 'No' }}</span></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500 block">Main Stream URL</span><code class="text-xs bg-slate-100 px-2 py-1 rounded block mt-1">{{ $camera->getMainStreamUrl() ?? 'Not configured' }}</code></div>
            <div class="md:col-span-2"><span class="text-xs text-slate-500 block">Sub Stream URL</span><code class="text-xs bg-slate-100 px-2 py-1 rounded block mt-1">{{ $camera->getSubStreamUrl() ?? 'Not configured' }}</code></div>
        </div>
    </div>
</div>
@endsection
