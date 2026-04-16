@extends('layouts.app')
@section('title', 'User Access Control')
@section('page-title', 'User Access Control')
@section('content')
<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg">
        <i class="fas fa-info-circle mr-1"></i> Manage which cameras each user can access. Superadmin users have access to all cameras by default.
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cameras Assigned</th>
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
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if($user->isSuperadmin())
                                <span class="text-xs text-slate-400">All (Superadmin)</span>
                            @else
                                {{ $user->camera_permissions_count ?? $user->cameraPermissions->count() }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(!$user->isSuperadmin())
                            <a href="{{ route('user-access.edit', $user) }}" class="inline-flex items-center gap-1 text-sm text-blue-500 hover:text-blue-700">
                                <i class="fas fa-key"></i> Manage Access
                            </a>
                            @else
                            <span class="text-xs text-slate-400">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No users found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
