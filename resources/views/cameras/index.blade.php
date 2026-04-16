@extends('layouts.app')

@section('title', 'Camera Management')
@section('page-title', 'Camera Management')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('cameras.index') }}" class="flex items-center gap-2">
                <select name="building_id" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                    <option value="{{ $building->id }}" {{ request('building_id') == $building->id ? 'selected' : '' }}>{{ $building->name }}</option>
                    @endforeach
                </select>
                <select name="nvr_id" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All NVRs</option>
                    @foreach($nvrs as $nvr)
                    <option value="{{ $nvr->id }}" {{ request('nvr_id') == $nvr->id ? 'selected' : '' }}>{{ $nvr->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                    <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </form>
        </div>
        <a href="{{ route('cameras.create') }}" class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Add Camera
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Camera Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Building</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">NVR</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($cameras as $camera)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $loop->iteration + ($cameras->currentPage() - 1) * $cameras->perPage() }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $camera->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $camera->building->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $camera->nvr->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">CH {{ $camera->channel_no }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $camera->location ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($camera->status === 'online')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700"><i class="fas fa-circle text-[6px]"></i> Online</span>
                            @elseif($camera->status === 'offline')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700"><i class="fas fa-circle text-[6px]"></i> Offline</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"><i class="fas fa-circle text-[6px]"></i> Maintenance</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('cameras.edit', $camera) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('cameras.destroy', $camera) }}" onsubmit="return confirm('Are you sure you want to delete this camera?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">
                            <i class="fas fa-camera text-2xl mb-2 block opacity-30"></i>
                            No cameras found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cameras->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $cameras->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
