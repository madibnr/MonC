@extends('layouts.app')
@section('title', 'Manage Camera Access')
@section('page-title', 'Manage Camera Access')
@section('content')
<div class="space-y-6">
    <!-- User Info -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-slate-500"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-800">{{ $user->name }}</h3>
                <p class="text-sm text-slate-500">{{ $user->email }} &middot; {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('user-access.update', $user) }}">
        @csrf @method('PUT')
        
        @foreach($cameras as $buildingName => $buildingCameras)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ expanded: true }">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                <div class="flex items-center gap-3">
                    <i class="fas fa-building text-slate-400"></i>
                    <h4 class="text-sm font-semibold text-slate-800">{{ $buildingName }}</h4>
                    <span class="text-xs text-slate-400">({{ count($buildingCameras) }} cameras)</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="event.stopPropagation(); toggleBuilding(this, '{{ Str::slug($buildingName) }}')" class="text-xs text-blue-500 hover:text-blue-700">Select All</button>
                    <i class="fas fa-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': expanded }"></i>
                </div>
            </div>
            <div x-show="expanded" x-collapse>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="bg-slate-50/50">
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Camera</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Location</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Live View</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Playback</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Export</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($buildingCameras as $camera)
                            @php $perm = $permissions[$camera->id] ?? null; @endphp
                            <tr class="hover:bg-slate-50 building-{{ Str::slug($buildingName) }}">
                                <td class="px-4 py-2 text-sm font-medium text-slate-800">{{ $camera->name }}</td>
                                <td class="px-4 py-2 text-sm text-slate-600">{{ $camera->location ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="permissions[{{ $camera->id }}][can_live_view]" value="1" {{ $perm && $perm->can_live_view ? 'checked' : '' }} class="w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500 cb-live">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="permissions[{{ $camera->id }}][can_playback]" value="1" {{ $perm && $perm->can_playback ? 'checked' : '' }} class="w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500 cb-playback">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="permissions[{{ $camera->id }}][can_export]" value="1" {{ $perm && $perm->can_export ? 'checked' : '' }} class="w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500 cb-export">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-save mr-1"></i> Save Permissions
            </button>
            <a href="{{ route('user-access.index') }}" class="text-sm text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function toggleBuilding(btn, slug) {
    const rows = document.querySelectorAll('.building-' + slug);
    const checkboxes = [];
    rows.forEach(row => {
        row.querySelectorAll('input[type="checkbox"]').forEach(cb => checkboxes.push(cb));
    });
    const allChecked = checkboxes.every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Select All' : 'Deselect All';
}
</script>
@endsection
