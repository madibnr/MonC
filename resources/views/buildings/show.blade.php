@extends('layouts.app')
@section('title', 'Building Details')
@section('page-title', 'Building Details')
@section('content')
<div class="max-w-4xl space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-semibold text-slate-800"><i class="fas fa-building text-slate-400 mr-2"></i>{{ $building->name }}</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('buildings.edit', $building) }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-edit mr-1"></i>Edit</a>
                <a href="{{ route('buildings.index') }}" class="text-sm text-slate-500 hover:text-slate-700"><i class="fas fa-arrow-left mr-1"></i>Back</a>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><span class="text-xs text-slate-500 block">Code</span><span class="text-sm font-medium text-slate-800 font-mono">{{ $building->code }}</span></div>
            <div><span class="text-xs text-slate-500 block">Address</span><span class="text-sm font-medium text-slate-800">{{ $building->address ?? '-' }}</span></div>
            <div><span class="text-xs text-slate-500 block">NVRs</span><span class="text-sm font-medium text-slate-800">{{ $building->nvrs->count() }}</span></div>
            <div><span class="text-xs text-slate-500 block">Active</span><span class="text-sm font-medium {{ $building->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $building->is_active ? 'Yes' : 'No' }}</span></div>
        </div>
        @if($building->description)
        <div class="mt-4 pt-4 border-t border-slate-200">
            <span class="text-xs text-slate-500 block mb-1">Description</span>
            <p class="text-sm text-slate-700">{{ $building->description }}</p>
        </div>
        @endif
    </div>
    @foreach($building->nvrs as $nvr)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800"><i class="fas fa-server text-slate-400 mr-2"></i>{{ $nvr->name }} <span class="text-xs text-slate-400 font-mono ml-2">{{ $nvr->ip_address }}</span></h3>
            <a href="{{ route('nvrs.show', $nvr) }}" class="text-xs text-blue-500 hover:text-blue-700">View Details</a>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-4 sm:grid-cols-8 md:grid-cols-16 gap-1">
                @foreach($nvr->cameras->sortBy('channel_no') as $camera)
                <div class="text-center p-1" title="{{ $camera->name }}">
                    <div class="w-8 h-8 mx-auto rounded flex items-center justify-center text-xs font-mono {{ $camera->status === 'online' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400' }}">
                        {{ $camera->channel_no }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
