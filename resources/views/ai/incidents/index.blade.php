@extends('layouts.app')

@section('title', 'Incident Timeline')
@section('page-title', 'Incident Timeline')

@section('content')
<div x-data="incidentManager()">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-triangle-exclamation text-blue-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['total_today'] }}</p>
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
                    <p class="text-xl font-bold text-slate-800">{{ $stats['unacknowledged'] }}</p>
                    <p class="text-xs text-slate-500">Unacknowledged</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['critical_unack'] }}</p>
                    <p class="text-xs text-slate-500">Critical Pending</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['high_unack'] }}</p>
                    <p class="text-xs text-slate-500">High Pending</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form method="GET" action="{{ route('ai.incidents.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Incident Type</label>
                <select name="incident_type" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Types</option>
                    @foreach(\App\Models\AiIncident::INCIDENT_TYPES as $value => $label)
                    <option value="{{ $value }}" {{ request('incident_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Severity</label>
                <select name="severity" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                </select>
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
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All</option>
                    <option value="unacknowledged" {{ request('status') === 'unacknowledged' ? 'selected' : '' }}>Unacknowledged</option>
                    <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
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
                <a href="{{ route('ai.incidents.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div x-show="selectedIds.length > 0" x-cloak class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-blue-700">
            <span x-text="selectedIds.length"></span> incident(s) selected
        </span>
        <button @click="bulkAcknowledge()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-check-double mr-1"></i> Acknowledge Selected
        </button>
    </div>

    <!-- Timeline -->
    <div class="space-y-3">
        @forelse($incidents as $incident)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow
                    {{ !$incident->is_acknowledged ? 'border-l-4' : '' }}
                    {{ !$incident->is_acknowledged && $incident->severity === 'critical' ? 'border-l-red-500' : '' }}
                    {{ !$incident->is_acknowledged && $incident->severity === 'high' ? 'border-l-orange-500' : '' }}
                    {{ !$incident->is_acknowledged && $incident->severity === 'medium' ? 'border-l-yellow-500' : '' }}
                    {{ !$incident->is_acknowledged && $incident->severity === 'low' ? 'border-l-blue-500' : '' }}">
            <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1">
                        <!-- Checkbox -->
                        @if(!$incident->is_acknowledged)
                        <input type="checkbox" :value="{{ $incident->id }}"
                               x-model="selectedIds"
                               class="mt-1 w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        @endif

                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                            {{ $incident->severity === 'critical' ? 'bg-red-100' : ($incident->severity === 'high' ? 'bg-orange-100' : ($incident->severity === 'medium' ? 'bg-yellow-100' : 'bg-blue-100')) }}">
                            <i class="fas {{ $incident->incident_type === 'watchlist_hit' ? 'fa-exclamation-triangle' : 'fa-car' }}
                                {{ $incident->severity === 'critical' ? 'text-red-500' : ($incident->severity === 'high' ? 'text-orange-500' : ($incident->severity === 'medium' ? 'text-yellow-500' : 'text-blue-500')) }}"></i>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('ai.incidents.show', $incident) }}" class="text-sm font-semibold text-slate-800 hover:text-blue-600">
                                    {{ $incident->title }}
                                </a>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $incident->getSeverityBadge() }}">
                                    {{ ucfirst($incident->severity) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                    {{ $incident->getIncidentTypeLabel() }}
                                </span>
                                @if($incident->is_acknowledged)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">
                                    <i class="fas fa-check mr-1"></i> Acknowledged
                                </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1">{{ $incident->description }}</p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                                <span><i class="fas fa-clock mr-1"></i> {{ $incident->occurred_at->format('d M Y H:i:s') }}</span>
                                <span><i class="fas fa-camera mr-1"></i> {{ $incident->camera?->name ?? '-' }}</span>
                                <span><i class="fas fa-building mr-1"></i> {{ $incident->camera?->building?->name ?? '-' }}</span>
                                @if($incident->plate_number)
                                <span class="font-mono font-bold text-slate-600"><i class="fas fa-car mr-1"></i> {{ $incident->plate_number }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(!$incident->is_acknowledged)
                        <form method="POST" action="{{ route('ai.incidents.acknowledge', $incident) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition-colors"
                                    title="Acknowledge">
                                <i class="fas fa-check mr-1"></i> Ack
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('ai.incidents.show', $incident) }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-medium rounded-lg transition-colors">
                            <i class="fas fa-eye mr-1"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
            <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
            <p class="text-slate-500">No incidents found.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($incidents->hasPages())
    <div class="mt-6">
        {{ $incidents->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function incidentManager() {
    return {
        selectedIds: [],

        async bulkAcknowledge() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`Acknowledge ${this.selectedIds.length} incident(s)?`)) return;

            try {
                const response = await fetch('{{ route("ai.incidents.bulk-acknowledge") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ incident_ids: this.selectedIds }),
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                alert('Failed to acknowledge incidents.');
            }
        }
    };
}
</script>
@endsection
