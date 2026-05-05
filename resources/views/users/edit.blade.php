@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-6"><i class="fas fa-user-edit text-slate-400 mr-2"></i>Edit User: {{ $user->name }}</h3>
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="admin_it" {{ old('role', $user->role) == 'admin_it' ? 'selected' : '' }}>Admin IT</option>
                        <option value="auditor" {{ old('role', $user->role) == 'auditor' ? 'selected' : '' }}>Auditor</option>
                        <option value="superadmin" {{ old('role', $user->role) == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-200">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors"><i class="fas fa-save mr-1"></i> Update User</button>
                <a href="{{ route('users.index') }}" class="text-sm text-slate-600 hover:text-slate-800">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
