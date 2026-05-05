@extends('layouts.app')

@section('title', 'Plate Detection Logs')
@section('page-title', 'Plate Detection Logs')

@section('content')
<div>
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-car text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ number_format($todayCount) }}</p>
                    <p class="text-sm text-slate-500">Detections Today</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-id-card text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ number_format($uniquePlates) }}</p>
                    <p class="text-sm text-slate-500">Unique Plates Today</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form method="GET" action="{{ route('ai.detections.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Plate Number</label>
                <input type="text" name="plate_number" value="{{ request('plate_number') }}" placeholder="e.g. B 1234 XYZ"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Building</label>
                <select name="building_id" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $building)
                    <option value="{{ $building->id }}" {{ request('building_id') == $building->id ? 'selected' : '' }}>{{ $building->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Camera</label>
                <select name="camera_id" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Cameras</option>
                    @foreach($cameras as $camera)
                    <option value="{{ $camera->id }}" {{ request('camera_id') == $camera->id ? 'selected' : '' }}>{{ $camera->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                <a href="{{ route('ai.detections.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Detection Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Plate Number</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Camera</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Building</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Confidence</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Direction</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Watchlist</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detections as $detection)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="text-sm text-slate-800">{{ $detection->detected_at->format('d M Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $detection->detected_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-mono font-bold bg-slate-100 text-slate-800 tracking-wider">
                                {{ $detection->plate_number }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $detection->camera?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $detection->camera?->building?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $detection->confidence >= 90 ? 'bg-green-100 text-green-700' : ($detection->confidence >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ number_format($detection->confidence, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($detection->direction === 'in')
                                <span class="text-green-600"><i class="fas fa-arrow-right"></i> IN</span>
                            @elseif($detection->direction === 'out')
                                <span class="text-red-600"><i class="fas fa-arrow-left"></i> OUT</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($detection->isOnWatchlist())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> MATCH
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('ai.detections.show', $detection) }}" class="text-blue-500 hover:text-blue-700 text-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="fas fa-car-slash text-3xl mb-2 block"></i>
                            No plate detections found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($detections->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $detections->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
