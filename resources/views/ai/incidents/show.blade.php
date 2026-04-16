@extends('layouts.app')

@section('title', 'Incident Detail')
@section('page-title', 'Incident Detail')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('ai.incidents.index') }}" class="text-sm text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Incident Timeline
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Incident Info -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-800">Incident Information</h3>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $incident->getSeverityBadge() }}">
                            {{ ucfirst($incident->severity) }}
                        </span>
                        @if($incident->is_acknowledged)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <i class="fas fa-check mr-1"></i> Acknowledged
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                        @endif
                    </div>
                </div>
                <div class="p-5">
                    <h2 class="text-lg font-bold text-slate-800 mb-2">{{ $incident->title }}</h2>
                    <p class="text-sm text-slate-600 mb-4">{{ $incident->description }}</p>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Incident Type</p>
                            <p class="font-medium text-slate-800">{{ $incident->getIncidentTypeLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Occurred At</p>
                            <p class="font-medium text-slate-800">{{ $incident->occurred_at->format('d M Y H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Camera</p>
                            <p class="font-medium text-slate-800">{{ $incident->camera?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Building</p>
                            <p class="font-medium text-slate-800">{{ $incident->camera?->building?->name ?? '-' }}</p>
                        </div>
                        @if($incident->plate_number)
                        <div>
                            <p class="text-slate-500">Plate Number</p>
                            <p class="font-mono font-bold text-lg text-slate-800 tracking-wider">{{ $incident->plate_number }}</p>
                        </div>
                        @endif
                        @if($incident->is_acknowledged)
                        <div>
                            <p class="text-slate-500">Acknowledged By</p>
                            <p class="font-medium text-slate-800">{{ $incident->acknowledgedByUser?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Acknowledged At</p>
                            <p class="font-medium text-slate-800">{{ $incident->acknowledged_at?->format('d M Y H:i:s') }}</p>
                        </div>
                        @if($incident->resolution_notes)
                        <div class="col-span-2">
                            <p class="text-slate-500">Resolution Notes</p>
                            <p class="font-medium text-slate-800">{{ $incident->resolution_notes }}</p>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Snapshot -->
            @if($incident->snapshot_path)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Incident Snapshot</h3>
                </div>
                <div class="p-5">
                    <img src="{{ Storage::url($incident->snapshot_path) }}" alt="Incident snapshot"
                         class="w-full rounded-lg border border-slate-200">
                </div>
            </div>
            @endif

            <!-- Detection Details -->
            @if($incident->plateDetectionLog)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Detection Details</h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Confidence</p>
                            <p class="font-medium text-slate-800">{{ number_format($incident->plateDetectionLog->confidence, 1) }}%</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Direction</p>
                            <p class="font-medium text-slate-800">{{ ucfirst($incident->plateDetectionLog->direction) }}</p>
                        </div>
                        @if($incident->plateDetectionLog->vehicle_type)
                        <div>
                            <p class="text-slate-500">Vehicle Type</p>
                            <p class="font-medium text-slate-800">{{ ucfirst($incident->plateDetectionLog->vehicle_type) }}</p>
                        </div>
                        @endif
                        @if($incident->plateDetectionLog->vehicle_color)
                        <div>
                            <p class="text-slate-500">Vehicle Color</p>
                            <p class="font-medium text-slate-800">{{ ucfirst($incident->plateDetectionLog->vehicle_color) }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('ai.detections.show', $incident->plateDetectionLog) }}" class="text-sm text-blue-500 hover:text-blue-700">
                            <i class="fas fa-external-link-alt mr-1"></i> View Full Detection Record
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Acknowledge Form -->
            @if(!$incident->is_acknowledged)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Acknowledge Incident</h3>
                </div>
                <form method="POST" action="{{ route('ai.incidents.acknowledge', $incident) }}" class="p-5">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Resolution Notes</label>
                        <textarea name="resolution_notes" rows="3" placeholder="Optional notes..."
                                  class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-check mr-1"></i> Acknowledge
                    </button>
                </form>
            </div>
            @endif

            <!-- Watchlist Info -->
            @if($incident->watchlistPlate)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Watchlist Entry</h3>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Alert Level</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $incident->watchlistPlate->getAlertLevelBadge() }}">
                            {{ ucfirst($incident->watchlistPlate->alert_level) }}
                        </span>
                    </div>
                    @if($incident->watchlistPlate->reason)
                    <div>
                        <p class="text-slate-500">Reason</p>
                        <p class="font-medium text-slate-800">{{ $incident->watchlistPlate->reason }}</p>
                    </div>
                    @endif
                    @if($incident->watchlistPlate->vehicle_owner)
                    <div>
                        <p class="text-slate-500">Vehicle Owner</p>
                        <p class="font-medium text-slate-800">{{ $incident->watchlistPlate->vehicle_owner }}</p>
                    </div>
                    @endif
                    @if($incident->watchlistPlate->vehicle_description)
                    <div>
                        <p class="text-slate-500">Vehicle Description</p>
                        <p class="font-medium text-slate-800">{{ $incident->watchlistPlate->vehicle_description }}</p>
                    </div>
                    @endif
                    <div class="pt-2">
                        <a href="{{ route('ai.watchlist.edit', $incident->watchlistPlate) }}" class="text-sm text-blue-500 hover:text-blue-700">
                            <i class="fas fa-edit mr-1"></i> Edit Watchlist Entry
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if($incident->metadata)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800">Metadata</h3>
                </div>
                <div class="p-5">
                    <pre class="text-xs text-slate-600 bg-slate-50 rounded-lg p-3 overflow-x-auto">{{ json_encode($incident->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
