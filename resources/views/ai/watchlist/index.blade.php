@extends('layouts.app')

@section('title', 'Watchlist')
@section('page-title', 'Watchlist')

@section('content')
<div>
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-list text-blue-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['total'] }}</p>
                    <p class="text-xs text-slate-500">Total Entries</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['active'] }}</p>
                    <p class="text-xs text-slate-500">Active</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['critical'] }}</p>
                    <p class="text-xs text-slate-500">Critical</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-500"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800">{{ $stats['high'] }}</p>
                    <p class="text-xs text-slate-500">High Priority</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <form method="GET" action="{{ route('ai.watchlist.index') }}" class="flex flex-wrap items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plate, owner, reason..."
                       class="text-sm border border-slate-300 rounded-lg px-3 py-2 w-64 focus:ring-2 focus:ring-blue-500 outline-none">
                <select name="alert_level" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Levels</option>
                    @foreach(\App\Models\WatchlistPlate::ALERT_LEVELS as $value => $label)
                    <option value="{{ $value }}" {{ request('alert_level') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
            </form>
            <a href="{{ route('ai.watchlist.create') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-plus"></i> Add to Watchlist
            </a>
        </div>
    </div>

    <!-- Watchlist Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Plate Number</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Alert Level</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Owner</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Telegram</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Added By</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($watchlist as $entry)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-mono font-bold bg-slate-100 text-slate-800 tracking-wider">
                                {{ $entry->plate_number }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->getAlertLevelBadge() }}">
                                {{ ucfirst($entry->alert_level) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate">{{ $entry->reason ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $entry->vehicle_owner ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($entry->notify_telegram)
                                <i class="fab fa-telegram text-blue-500"></i>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $entry->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">
                            {{ $entry->createdByUser?->name ?? '-' }}
                            <div class="text-xs text-slate-400">{{ $entry->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('ai.watchlist.edit', $entry) }}" class="text-blue-500 hover:text-blue-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('ai.watchlist.destroy', $entry) }}" class="inline"
                                      onsubmit="return confirm('Remove {{ $entry->plate_number }} from watchlist?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="fas fa-list-check text-3xl mb-2 block"></i>
                            No watchlist entries found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($watchlist->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $watchlist->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
