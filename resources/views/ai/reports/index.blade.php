@extends('layouts.app')

@section('title', 'AI Camera Summary')
@section('page-title', 'AI Camera Summary')

@section('content')
<div>
    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total AI Cameras</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalAiCameras }}</p>
                    <p class="text-xs text-slate-400 mt-1">of {{ $totalCamerasWithSettings }} configured</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-microchip text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Active Detections Today</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($detectionsToday) }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $uniquePlatesToday }} unique plates</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-car text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Last Detection</p>
                    <p class="text-lg font-bold text-slate-800 mt-1">
                        {{ $lastDetection ? $lastDetection->detected_at->format('H:i:s') : 'N/A' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $lastDetection ? $lastDetection->detected_at->diffForHumans() : 'No detections yet' }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Most Detected Plate</p>
                    @if($mostDetectedPlate)
                    <p class="text-lg font-bold font-mono text-slate-800 mt-1 tracking-wider">{{ $mostDetectedPlate->plate_number }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $mostDetectedPlate->detection_count }}x today</p>
                    @else
                    <p class="text-lg font-bold text-slate-400 mt-1">N/A</p>
                    @endif
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-trophy text-orange-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-triangle-exclamation text-red-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $incidentsToday }}</p>
                    <p class="text-xs text-slate-500">Incidents Today</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $unacknowledgedIncidents }}</p>
                    <p class="text-xs text-slate-500">Unacknowledged Incidents</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $aiServiceHealthy ? 'bg-green-50' : 'bg-red-50' }}">
                    <i class="fas fa-server {{ $aiServiceHealthy ? 'text-green-500' : 'text-red-500' }}"></i>
                </div>
                <div>
                    <p class="text-xl font-bold {{ $aiServiceHealthy ? 'text-green-600' : 'text-red-600' }}">
                        {{ $aiServiceHealthy ? 'Online' : 'Offline' }}
                    </p>
                    <p class="text-xs text-slate-500">AI Service Status</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Hourly Detection Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Hourly Detections (Last 24h)</h3>
            </div>
            <div class="p-5">
                <div class="flex items-end gap-1 h-40">
                    @php $maxHourly = max(1, max($hourlyChart)); @endphp
                    @for($h = 0; $h < 24; $h++)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-blue-500 rounded-t transition-all hover:bg-blue-600 relative group"
                             style="height: {{ ($hourlyChart[$h] / $maxHourly) * 100 }}%">
                            <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                {{ $hourlyChart[$h] }}
                            </div>
                        </div>
                        <span class="text-[9px] text-slate-400">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Detection by Building -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Detections by Building (Today)</h3>
            </div>
            <div class="p-5">
                @forelse($detectionsByBuilding as $buildingData)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <span class="text-sm text-slate-700">{{ $buildingData->building_name }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-32 bg-slate-100 rounded-full h-2">
                            @php $maxBuilding = $detectionsByBuilding->max('detection_count') ?: 1; @endphp
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($buildingData->detection_count / $maxBuilding) * 100 }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-800 w-10 text-right">{{ $buildingData->detection_count }}</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-slate-400 text-center py-4">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Cameras -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Top Detection Cameras (Today)</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($detectionsByCamera as $camData)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold">
                            {{ $loop->iteration }}
                        </span>
                        <span class="text-sm text-slate-700">{{ $camData->camera?->name ?? 'Camera #' . $camData->camera_id }}</span>
                    </div>
                    <span class="text-sm font-bold text-slate-800">{{ $camData->detection_count }}</span>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-slate-400">No data available</div>
                @endforelse
            </div>
        </div>

        <!-- AI Camera Types -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">AI Cameras by Type</h3>
            </div>
            <div class="p-5">
                @forelse($camerasByType as $type => $count)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <span class="text-sm text-slate-700">{{ \App\Models\AiCameraSetting::AI_TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        {{ $count }} camera(s)
                    </span>
                </div>
                @empty
                <p class="text-sm text-slate-400 text-center py-4">No AI cameras configured</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Detections -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">Recent Detections</h3>
            <a href="{{ route('ai.detections.index') }}" class="text-sm text-blue-500 hover:text-blue-700">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Time</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Plate</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Camera</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Building</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-slate-600">Confidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentDetections as $detection)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2 text-slate-700">{{ $detection->detected_at->format('d M H:i:s') }}</td>
                        <td class="px-4 py-2">
                            <span class="font-mono font-bold text-slate-800 tracking-wider">{{ $detection->plate_number }}</span>
                        </td>
                        <td class="px-4 py-2 text-slate-700">{{ $detection->camera?->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $detection->camera?->building?->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $detection->confidence >= 90 ? 'bg-green-100 text-green-700' : ($detection->confidence >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ number_format($detection->confidence, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-400">No detections yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
