@extends('layouts.app')
@section('title', 'Storage Monitor')
@section('page-title', 'Storage Monitor')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">NVR storage status across all buildings</p>
        <a href="{{ route('health.index') }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i>Back to Health Monitor</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($nvrs as $nvr)
        @php $health = $nvr->latestHealth; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">{{ $nvr->name }}</h3>
                    <p class="text-xs text-slate-400">{{ $nvr->building->name ?? '-' }} &middot; {{ $nvr->ip_address }}</p>
                </div>
                @if($nvr->status === 'online')
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Online</span>
                @else
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Offline</span>
                @endif
            </div>

            @if($health)
            <!-- HDD Usage Gauge -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-slate-600">HDD Usage</span>
                    <span class="font-semibold {{ ($health->hdd_usage_percent ?? 0) >= 90 ? 'text-red-600' : (($health->hdd_usage_percent ?? 0) >= 70 ? 'text-yellow-600' : 'text-green-600') }}">{{ $health->hdd_usage_percent ?? 0 }}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all {{ ($health->hdd_usage_percent ?? 0) >= 90 ? 'bg-red-500' : (($health->hdd_usage_percent ?? 0) >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $health->hdd_usage_percent ?? 0 }}%"></div>
                </div>
            </div>

            <!-- Storage Details -->
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="text-center p-2 bg-slate-50 rounded-lg">
                    <p class="text-[10px] text-slate-500">Total</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $health->getHddTotalFormatted() }}</p>
                </div>
                <div class="text-center p-2 bg-slate-50 rounded-lg">
                    <p class="text-[10px] text-slate-500">Used</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $health->getHddUsedFormatted() }}</p>
                </div>
                <div class="text-center p-2 bg-slate-50 rounded-lg">
                    <p class="text-[10px] text-slate-500">Free</p>
                    <p class="text-sm font-semibold text-green-600">{{ $health->getHddFreeFormatted() }}</p>
                </div>
            </div>

            <!-- Other Metrics -->
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Recording</span>
                    @if($health->is_recording)
                    <span class="text-green-600 font-medium"><i class="fas fa-circle text-[6px] mr-1"></i>{{ $health->recording_channels }} ch</span>
                    @else
                    <span class="text-red-600 font-medium"><i class="fas fa-circle text-[6px] mr-1"></i>Stopped</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Bandwidth</span>
                    <span class="text-slate-800 font-medium">{{ $health->getBandwidthFormatted() }}</span>
                </div>
                @if($health->cpu_usage_percent !== null)
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">CPU</span>
                    <span class="text-slate-800 font-medium">{{ $health->cpu_usage_percent }}%</span>
                </div>
                @endif
                @if($health->memory_usage_percent !== null)
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Memory</span>
                    <span class="text-slate-800 font-medium">{{ $health->memory_usage_percent }}%</span>
                </div>
                @endif
                @if($health->firmware_version)
                <div class="flex items-center justify-between col-span-2">
                    <span class="text-slate-500">Firmware</span>
                    <span class="text-slate-800 font-mono text-xs">{{ $health->firmware_version }}</span>
                </div>
                @endif
            </div>

            <div class="mt-3 pt-3 border-t border-slate-200 text-[10px] text-slate-400">
                Last checked: {{ $health->created_at->diffForHumans() }}
            </div>
            @else
            <div class="text-center py-6 text-slate-400">
                <i class="fas fa-hdd text-2xl mb-2 block opacity-30"></i>
                <p class="text-sm">No health data available</p>
                <p class="text-xs">Health check has not been run yet</p>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
