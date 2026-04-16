@extends('layouts.app')

@section('title', 'AI Camera Assignment')
@section('page-title', 'AI Camera Assignment')

@section('content')
<div x-data="aiCameraManager()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-camera text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['total_cameras'] }}</p>
                    <p class="text-sm text-slate-500">Total Cameras</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-microchip text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['ai_enabled'] }}</p>
                    <p class="text-sm text-slate-500">AI Enabled</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-car text-purple-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['plate_recognition'] }}</p>
                    <p class="text-sm text-slate-500">Plate Recognition</p>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Service Status -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-server text-slate-400"></i>
                <span class="text-sm font-medium text-slate-700">AI Microservice Status</span>
                <span x-show="serviceHealthy === null" class="text-xs text-slate-400">
                    <i class="fas fa-spinner fa-spin"></i> Checking...
                </span>
                <span x-show="serviceHealthy === true" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Connected
                </span>
                <span x-show="serviceHealthy === false" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Disconnected
                </span>
            </div>
            <button @click="checkHealth()" class="text-sm text-blue-500 hover:text-blue-700">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Camera Assignment by Building -->
    <form method="POST" action="{{ route('ai.cameras.update') }}" id="aiCameraForm">
        @csrf
        @method('PUT')

        @forelse($buildings as $building)
            @if($building->cameras->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-4 overflow-hidden">
                <!-- Building Header -->
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between cursor-pointer"
                     @click="toggleBuilding({{ $building->id }})">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-building text-slate-400"></i>
                        <h3 class="text-base font-semibold text-slate-800">{{ $building->name }}</h3>
                        <span class="text-xs text-slate-500">({{ $building->code }})</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-600">
                            {{ $building->cameras->count() }} cameras
                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-slate-400 transition-transform"
                       :class="{ 'rotate-180': openBuildings.includes({{ $building->id }}) }"></i>
                </div>

                <!-- Camera List -->
                <div x-show="openBuildings.includes({{ $building->id }})" x-collapse>
                    <div class="divide-y divide-slate-100">
                        @foreach($building->cameras as $camera)
                        @php
                            $setting = $camera->aiSetting;
                            $isEnabled = $setting ? $setting->ai_enabled : false;
                            $aiType = $setting ? $setting->ai_type : 'plate_recognition';
                            $interval = $setting ? $setting->detection_interval_seconds : 5;
                            $confidence = $setting ? $setting->confidence_threshold : 85;
                        @endphp
                        <div class="px-5 py-4 hover:bg-slate-50/50 transition-colors"
                             x-data="{ expanded: false }">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <!-- AI Toggle -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="cameras[{{ $loop->parent->index }}_{{ $loop->index }}][camera_id]" value="{{ $camera->id }}">
                                        <input type="hidden" name="cameras[{{ $loop->parent->index }}_{{ $loop->index }}][ai_enabled]" :value="cameraSettings[{{ $camera->id }}]?.enabled ? 1 : 0">
                                        <input type="hidden" name="cameras[{{ $loop->parent->index }}_{{ $loop->index }}][ai_type]" :value="cameraSettings[{{ $camera->id }}]?.type || 'plate_recognition'">
                                        <input type="hidden" name="cameras[{{ $loop->parent->index }}_{{ $loop->index }}][detection_interval_seconds]" :value="cameraSettings[{{ $camera->id }}]?.interval || 5">
                                        <input type="hidden" name="cameras[{{ $loop->parent->index }}_{{ $loop->index }}][confidence_threshold]" :value="cameraSettings[{{ $camera->id }}]?.confidence || 85">

                                        <div class="w-11 h-6 rounded-full transition-colors cursor-pointer flex items-center px-0.5"
                                             :class="cameraSettings[{{ $camera->id }}]?.enabled ? 'bg-green-500' : 'bg-slate-300'"
                                             @click="toggleCamera({{ $camera->id }})">
                                            <div class="w-5 h-5 bg-white rounded-full shadow-sm transition-transform"
                                                 :class="cameraSettings[{{ $camera->id }}]?.enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                                        </div>
                                    </label>

                                    <!-- Camera Info -->
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-slate-800">{{ $camera->name }}</span>
                                            <span class="w-2 h-2 rounded-full {{ $camera->status === 'online' ? 'bg-green-500' : ($camera->status === 'maintenance' ? 'bg-yellow-500' : 'bg-slate-400') }}"></span>
                                            <span x-show="cameraSettings[{{ $camera->id }}]?.enabled" x-cloak
                                                  class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 uppercase">
                                                AI Active
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $camera->location ?? 'No location set' }}</p>
                                    </div>
                                </div>

                                <!-- Expand Settings -->
                                <button type="button" @click="expanded = !expanded"
                                        class="text-sm text-slate-400 hover:text-slate-600 p-1">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>

                            <!-- Expanded Settings -->
                            <div x-show="expanded" x-collapse class="mt-3 ml-15 pl-4 border-l-2 border-slate-200">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-2">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">AI Type</label>
                                        <select class="w-full text-sm border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none"
                                                x-model="cameraSettings[{{ $camera->id }}].type">
                                            @foreach(\App\Models\AiCameraSetting::AI_TYPES as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Detection Interval (seconds)</label>
                                        <input type="number" min="1" max="300"
                                               class="w-full text-sm border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none"
                                               x-model.number="cameraSettings[{{ $camera->id }}].interval">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Confidence Threshold (%)</label>
                                        <input type="number" min="1" max="100"
                                               class="w-full text-sm border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none"
                                               x-model.number="cameraSettings[{{ $camera->id }}].confidence">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
                <i class="fas fa-camera-slash text-4xl text-slate-300 mb-3"></i>
                <p class="text-slate-500">No buildings or cameras found.</p>
            </div>
        @endforelse

        <!-- Save Button -->
        <div class="sticky bottom-4 flex justify-end mt-6">
            <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                <i class="fas fa-save"></i>
                Save AI Camera Settings
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function aiCameraManager() {
    return {
        serviceHealthy: null,
        openBuildings: [{{ $buildings->pluck('id')->implode(',') }}],
        cameraSettings: {
            @foreach($buildings as $building)
                @foreach($building->cameras as $camera)
                    @php
                        $setting = $camera->aiSetting;
                    @endphp
                    {{ $camera->id }}: {
                        enabled: {{ ($setting && $setting->ai_enabled) ? 'true' : 'false' }},
                        type: '{{ $setting ? $setting->ai_type : 'plate_recognition' }}',
                        interval: {{ $setting ? $setting->detection_interval_seconds : 5 }},
                        confidence: {{ $setting ? $setting->confidence_threshold : 85 }},
                    },
                @endforeach
            @endforeach
        },

        init() {
            this.checkHealth();
        },

        toggleBuilding(id) {
            const idx = this.openBuildings.indexOf(id);
            if (idx > -1) {
                this.openBuildings.splice(idx, 1);
            } else {
                this.openBuildings.push(id);
            }
        },

        toggleCamera(cameraId) {
            if (!this.cameraSettings[cameraId]) {
                this.cameraSettings[cameraId] = {
                    enabled: false,
                    type: 'plate_recognition',
                    interval: 5,
                    confidence: 85,
                };
            }
            this.cameraSettings[cameraId].enabled = !this.cameraSettings[cameraId].enabled;
        },

        async checkHealth() {
            this.serviceHealthy = null;
            try {
                const response = await fetch('{{ route("ai.cameras.health-check") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.serviceHealthy = data.healthy;
            } catch (e) {
                this.serviceHealthy = false;
            }
        }
    };
}
</script>
@endsection
