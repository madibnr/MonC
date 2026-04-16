@extends('layouts.app')
@section('title', 'NVR Details')
@section('page-title', 'NVR Details')
@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-semibold text-slate-800"><i class="fas fa-server text-slate-400 mr-2"></i>{{ $nvr->name }}</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('nvrs.edit', $nvr) }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-edit mr-1"></i>Edit</a>
                <a href="{{ route('nvrs.index') }}" class="text-sm text-slate-500 hover:text-slate-700"><i class="fas fa-arrow-left mr-1"></i>Back</a>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><span class="text-xs text-slate-500 block">Building</span><span class="text-sm font-medium text-slate-800">{{ $nvr->building->name ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">IP Address</span><span class="text-sm font-medium text-slate-800 font-mono">{{ $nvr->ip_address }}:{{ $nvr->port }}</span></div>
            <div><span class="text-xs text-slate-500 block">Model</span><span class="text-sm font-medium text-slate-800">{{ $nvr->model ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">Status</span>
                @if($nvr->status === 'online')<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Online</span>
                @elseif($nvr->status === 'offline')<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Offline</span>
                @else<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Maintenance</span>@endif
            </div>
            <div><span class="text-xs text-slate-500 block">Total Channels</span><span class="text-sm font-medium text-slate-800">{{ $nvr->total_channels }}</span></div>
            <div><span class="text-xs text-slate-500 block">Cameras Registered</span><span class="text-sm font-medium text-slate-800">{{ $nvr->cameras->count() }}</span></div>
            <div><span class="text-xs text-slate-500 block">Last Seen</span><span class="text-sm font-medium text-slate-800">{{ $nvr->last_seen_at ? $nvr->last_seen_at->format('d M Y H:i') : 'Never' }}</span></div>
            <div><span class="text-xs text-slate-500 block">Active</span><span class="text-sm font-medium {{ $nvr->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $nvr->is_active ? 'Yes' : 'No' }}</span></div>
        </div>
    </div>
    <!-- Cameras List -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-800"><i class="fas fa-camera text-slate-400 mr-2"></i>Cameras ({{ $nvr->cameras->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">CH</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Location</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($nvr->cameras->sortBy('channel_no') as $camera)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $camera->channel_no }}</td>
                        <td class="px-4 py-2 text-sm font-medium text-slate-800">{{ $camera->name }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $camera->location ?? '-' }}</td>
                        <td class="px-4 py-2">
                            @if($camera->status === 'online')<span class="text-xs text-green-600"><i class="fas fa-circle text-[6px]"></i> Online</span>
                            @else<span class="text-xs text-red-600"><i class="fas fa-circle text-[6px]"></i> Offline</span>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
