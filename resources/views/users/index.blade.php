@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('users.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ !request('role') ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">All</a>
            <a href="{{ route('users.index', ['role' => 'superadmin']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('role') == 'superadmin' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Superadmin</a>
            <a href="{{ route('users.index', ['role' => 'admin_it']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('role') == 'admin_it' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Admin IT</a>
            <a href="{{ route('users.index', ['role' => 'operator']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('role') == 'operator' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Operator</a>
            <a href="{{ route('users.index', ['role' => 'auditor']) }}" class="px-3 py-1.5 text-xs font-medium rounded-lg {{ request('role') == 'auditor' ? 'bg-yellow-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Auditor</a>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Add User
        </a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Created</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @php $roleColors = ['superadmin' => 'bg-red-100 text-red-700', 'admin_it' => 'bg-blue-100 text-blue-700', 'operator' => 'bg-green-100 text-green-700', 'auditor' => 'bg-yellow-100 text-yellow-700']; @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $roleColors[$user->role] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <i class="fas fa-circle text-[6px]"></i> {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="{{ route('user-access.edit', $user) }}" class="text-emerald-500 hover:text-emerald-700 text-sm" title="Camera Access"><i class="fas fa-key"></i></a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="{{ $user->is_active ? 'text-yellow-500 hover:text-yellow-700' : 'text-green-500 hover:text-green-700' }} text-sm" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400"><i class="fas fa-users text-2xl mb-2 block opacity-30"></i>No users found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
