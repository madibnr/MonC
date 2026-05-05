@extends('layouts.app')
@section('title', 'NVR Management')
@section('page-title', 'NVR Management')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm text-slate-500">{{ $nvrs->count() }} NVR(s) registered</h3>
        <a href="{{ route('nvrs.create') }}" class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Add NVR
        </a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Building</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Channels</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Last Seen</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($nvrs as $nvr)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $nvr->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $nvr->building->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 font-mono">{{ $nvr->ip_address }}:{{ $nvr->port }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $nvr->model ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $nvr->cameras_count ?? $nvr->cameras->count() }}/{{ $nvr->total_channels }}</td>
                        <td class="px-4 py-3">
                            @if($nvr->status === 'online')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700"><i class="fas fa-circle text-[6px]"></i> Online</span>
                            @elseif($nvr->status === 'offline')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700"><i class="fas fa-circle text-[6px]"></i> Offline</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"><i class="fas fa-circle text-[6px]"></i> Maintenance</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $nvr->last_seen_at ? $nvr->last_seen_at->diffForHumans() : 'Never' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button onclick="checkNvrStatus({{ $nvr->id }})" class="text-emerald-500 hover:text-emerald-700 text-sm" title="Check Status"><i class="fas fa-heartbeat"></i></button>
                                <a href="{{ route('nvrs.edit', $nvr) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('nvrs.destroy', $nvr) }}" onsubmit="return confirm('Delete this NVR and all its cameras?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-slate-400"><i class="fas fa-server text-2xl mb-2 block opacity-30"></i>No NVRs found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
async function checkNvrStatus(nvrId) {
    try {
        const res = await fetch(`/nvrs/${nvrId}/check-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
        const data = await res.json();
        alert(data.message || (data.online ? 'NVR is Online' : 'NVR is Offline'));
    } catch(e) { alert('Failed to check status'); }
}
</script>
@endsection
