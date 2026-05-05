@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Module</label>
                <select name="module" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>{{ ucfirst($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Action</label>
                <select name="action_type" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    @foreach($actionTypes as $act)
                    <option value="{{ $act }}" {{ request('action_type') == $act ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">User</label>
                <select name="user_id" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
            </div>
            <a href="{{ route('audit-logs.index') }}" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2">Clear</a>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Module</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Camera</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-800">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-medium rounded-full bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $log->action_type) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-50 text-blue-700">{{ $log->module }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $log->camera?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400"><i class="fas fa-clipboard-list text-2xl mb-2 block opacity-30"></i>No audit logs found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">{{ $logs->appends(request()->query())->links() }}</div>
        @endif
    </div>
</div>
@endsection
