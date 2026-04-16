@extends('layouts.app')
@section('title', 'Health Monitor')
@section('page-title', 'Health Monitor')

@section('content')
<div class="space-y-6" x-data="{ refreshing: false }">
    <!-- Camera Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Total Cameras</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $totalCameras }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center"><i class="fas fa-camera text-blue-500"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-green-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-green-600">Online</p>
                    <p class="text-2xl font-bold text-green-700">{{ $onlineCameras }}</p>
                </div>
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center"><i class="fas fa-check-circle text-green-500"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-red-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-red-600">Offline</p>
                    <p class="text-2xl font-bold text-red-700">{{ $offlineCameras }}</p>
                </div>
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center"><i class="fas fa-times-circle text-red-500"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-yellow-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-yellow-600">Maintenance</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $maintenanceCameras }}</p>
                </div>
                <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center"><i class="fas fa-wrench text-yellow-500"></i></div>
            </div>
        </div>
    </div>

    <!-- Per-Building Health -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800"><i class="fas fa-building text-slate-400 mr-2"></i>Building Status</h3>
            <a href="{{ route('health.storage') }}" class="text-xs text-blue-500 hover:text-blue-700"><i class="fas fa-hdd mr-1"></i>Storage Monitor</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
            @foreach($buildingStats as $building)
            <div class="border border-slate-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-800">{{ $building['name'] }}</h4>
                    <span class="text-xs text-slate-400">{{ $building['code'] }}</span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-green-600"><i class="fas fa-circle text-[6px] mr-1"></i>Online: {{ $building['online_cameras'] }}</span>
                        <span class="text-red-600"><i class="fas fa-circle text-[6px] mr-1"></i>Offline: {{ $building['offline_cameras'] }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        @php $pct = $building['total_cameras'] > 0 ? round(($building['online_cameras'] / $building['total_cameras']) * 100) : 0; @endphp
                        <div class="h-2 rounded-full {{ $pct >= 80 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span>{{ $building['total_cameras'] }} cameras</span>
                        <span>{{ $building['nvr_count'] }} NVR(s) ({{ $building['nvrs_online'] }} online)</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- NVR Health Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-800"><i class="fas fa-server text-slate-400 mr-2"></i>NVR Health Status</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">NVR</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Building</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">HDD Usage</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Recording</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Bandwidth</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($nvrs as $nvr)
                    @php $health = $nvrHealthData[$nvr->id] ?? null; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-800">{{ $nvr->name }}</div>
                            <div class="text-xs text-slate-400 font-mono">{{ $nvr->ip_address }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $nvr->building->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($nvr->status === 'online')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700"><i class="fas fa-circle text-[6px]"></i> Online</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700"><i class="fas fa-circle text-[6px]"></i> Offline</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($health && $health->hdd_usage_percent !== null)
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-slate-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $health->hdd_usage_percent >= 90 ? 'bg-red-500' : ($health->hdd_usage_percent >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $health->hdd_usage_percent }}%"></div>
                                </div>
                                <span class="text-xs text-slate-600">{{ $health->hdd_usage_percent }}%</span>
                            </div>
                            @else
                            <span class="text-xs text-slate-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($health)
                                @if($health->is_recording)
                                <span class="text-xs text-green-600"><i class="fas fa-circle text-[6px] mr-1"></i>{{ $health->recording_channels }} ch</span>
                                @else
                                <span class="text-xs text-red-600"><i class="fas fa-circle text-[6px] mr-1"></i>Not recording</span>
                                @endif
                            @else
                            <span class="text-xs text-slate-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $health ? $health->getBandwidthFormatted() : 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <button onclick="checkNvr({{ $nvr->id }})" class="text-blue-500 hover:text-blue-700 text-sm" title="Check Now"><i class="fas fa-sync-alt"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
async function checkNvr(nvrId) {
    try {
        const res = await fetch(`/health/check-nvr/${nvrId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
        const data = await res.json();
        alert(`NVR Status: ${data.status}\nHDD: ${data.hdd_usage ?? 'N/A'}%\nRecording: ${data.is_recording ? 'Yes' : 'No'}`);
        location.reload();
    } catch(e) { alert('Failed to check NVR'); }
}
</script>
@endsection
