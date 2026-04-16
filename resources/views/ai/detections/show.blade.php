@extends('layouts.app')

@section('title', 'Detection Detail')
@section('page-title', 'Detection Detail')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('ai.detections.index') }}" class="text-sm text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Detection Logs
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Detection Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Plate Detection</h3>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="inline-flex items-center px-4 py-2 rounded-lg text-2xl font-mono font-bold bg-slate-100 text-slate-800 tracking-widest border-2 border-slate-300">
                            {{ $detection->plate_number }}
                        </span>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium
                                {{ $detection->confidence >= 90 ? 'bg-green-100 text-green-700' : ($detection->confidence >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ number_format($detection->confidence, 1) }}% confidence
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Detected At</p>
                            <p class="font-medium text-slate-800">{{ $detection->detected_at->format('d M Y H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Camera</p>
                            <p class="font-medium text-slate-800">{{ $detection->camera?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Building</p>
                            <p class="font-medium text-slate-800">{{ $detection->camera?->building?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Direction</p>
                            <p class="font-medium text-slate-800">
                                @if($detection->direction === 'in')
                                    <span class="text-green-600"><i class="fas fa-arrow-right"></i> IN</span>
                                @elseif($detection->direction === 'out')
                                    <span class="text-red-600"><i class="fas fa-arrow-left"></i> OUT</span>
                                @else
                                    Unknown
                                @endif
                            </p>
                        </div>
                        @if($detection->vehicle_type)
                        <div>
                            <p class="text-slate-500">Vehicle Type</p>
                            <p class="font-medium text-slate-800">{{ ucfirst($detection->vehicle_type) }}</p>
                        </div>
                        @endif
                        @if($detection->vehicle_color)
                        <div>
                            <p class="text-slate-500">Vehicle Color</p>
                            <p class="font-medium text-slate-800">{{ ucfirst($detection->vehicle_color) }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Snapshot -->
            @if($detection->snapshot_path)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Detection Snapshot</h3>
                </div>
                <div class="p-5">
                    <img src="{{ Storage::url($detection->snapshot_path) }}" alt="Detection snapshot"
                         class="w-full rounded-lg border border-slate-200">
                </div>
            </div>
            @endif

            <!-- Related Detections -->
            @if($relatedDetections->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Other Detections of This Plate</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Time</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Camera</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-600">Confidence</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($relatedDetections as $related)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-2 text-slate-700">{{ $related->detected_at->format('d M Y H:i:s') }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $related->camera?->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ number_format($related->confidence, 1) }}%</td>
                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('ai.detections.show', $related) }}" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Watchlist Status -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Watchlist Status</h3>
                </div>
                <div class="p-5">
                    @if($isOnWatchlist && $watchlistEntry)
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i> ON WATCHLIST
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $watchlistEntry->getAlertLevelBadge() }}">
                                {{ ucfirst($watchlistEntry->alert_level) }}
                            </span>
                        </div>
                        <div class="space-y-2 text-sm">
                            @if($watchlistEntry->reason)
                            <div>
                                <p class="text-slate-500">Reason</p>
                                <p class="font-medium text-slate-800">{{ $watchlistEntry->reason }}</p>
                            </div>
                            @endif
                            @if($watchlistEntry->vehicle_owner)
                            <div>
                                <p class="text-slate-500">Owner</p>
                                <p class="font-medium text-slate-800">{{ $watchlistEntry->vehicle_owner }}</p>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                            <p class="text-sm text-slate-500">Not on watchlist</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Incidents -->
            @if($detection->incidents->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Related Incidents</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($detection->incidents as $incident)
                    <a href="{{ route('ai.incidents.show', $incident) }}" class="block px-5 py-3 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $incident->getSeverityBadge() }}">
                                {{ ucfirst($incident->severity) }}
                            </span>
                            <span class="text-sm text-slate-700 truncate">{{ $incident->title }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $incident->occurred_at->format('d M Y H:i') }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
