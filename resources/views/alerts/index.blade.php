@extends('layouts.app')
@section('title', 'Alerts')
@section('page-title', 'Alerts')

@section('content')
<div class="space-y-4">
    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Unresolved</p>
            <p class="text-2xl font-bold text-slate-800">{{ $unresolvedCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-red-200 p-4">
            <p class="text-xs text-red-500">Critical</p>
            <p class="text-2xl font-bold text-red-600">{{ $criticalCount }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('alerts.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ !request('status') ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</a>
        <a href="{{ route('alerts.index', ['status' => 'unresolved']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('status') == 'unresolved' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Unresolved</a>
        <a href="{{ route('alerts.index', ['status' => 'resolved']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('status') == 'resolved' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Resolved</a>
        <span class="mx-2 text-slate-300">|</span>
        <a href="{{ route('alerts.index', array_merge(request()->query(), ['severity' => 'critical'])) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('severity') == 'critical' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Critical</a>
        <a href="{{ route('alerts.index', array_merge(request()->query(), ['severity' => 'warning'])) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('severity') == 'warning' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Warning</a>
    </div>

    <!-- Alert List -->
    <div class="space-y-3">
        @forelse($alerts as $alert)
        <div class="bg-white rounded-xl shadow-sm border {{ $alert->severity === 'critical' ? 'border-red-200' : ($alert->severity === 'warning' ? 'border-yellow-200' : 'border-slate-200') }} p-4" x-data="{ showResolve: false }">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $alert->severity === 'critical' ? 'bg-red-100' : ($alert->severity === 'warning' ? 'bg-yellow-100' : 'bg-blue-100') }}">
                    <i class="fas {{ $alert->getTypeIcon() }} {{ $alert->severity === 'critical' ? 'text-red-500' : ($alert->severity === 'warning' ? 'text-yellow-500' : 'text-blue-500') }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-sm font-semibold text-slate-800">{{ $alert->title }}</h4>
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-700' : ($alert->severity === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">{{ ucfirst($alert->severity) }}</span>
                        @if($alert->is_resolved)
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-green-100 text-green-700">Resolved</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-600">{{ $alert->message }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span><i class="fas fa-clock mr-1"></i>{{ $alert->created_at->diffForHumans() }}</span>
                        <span><i class="fas fa-tag mr-1"></i>{{ str_replace('_', ' ', $alert->type) }}</span>
                        @if($alert->is_resolved)
                        <span><i class="fas fa-check mr-1"></i>Resolved {{ $alert->resolved_at?->diffForHumans() }} by {{ $alert->resolver?->name ?? 'System' }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if(!$alert->is_resolved)
                    <button @click="showResolve = !showResolve" class="text-sm text-green-500 hover:text-green-700 px-3 py-1 border border-green-200 rounded-lg hover:bg-green-50 transition-colors">
                        <i class="fas fa-check mr-1"></i>Resolve
                    </button>
                    @endif
                </div>
            </div>
            <!-- Resolve Form -->
            <div x-show="showResolve" x-cloak class="mt-3 pt-3 border-t border-slate-200">
                <form method="POST" action="{{ route('alerts.resolve', $alert) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Resolution Notes</label>
                        <input type="text" name="resolution_notes" placeholder="How was this resolved?" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Resolve</button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-400">
            <i class="fas fa-bell-slash text-3xl mb-3 block opacity-30"></i>
            <p class="text-sm">No alerts found</p>
        </div>
        @endforelse
    </div>

    @if($alerts->hasPages())
    <div class="mt-4">{{ $alerts->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection
