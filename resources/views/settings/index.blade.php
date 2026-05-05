@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')
@section('content')
<div class="max-w-2xl space-y-6">
    <!-- Profile Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-4"><i class="fas fa-user text-slate-400 mr-2"></i>Profile Settings</h3>
        <form method="POST" action="{{ route('settings.profile') }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors"><i class="fas fa-save mr-1"></i> Update Profile</button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-4"><i class="fas fa-lock text-slate-400 mr-2"></i>Change Password</h3>
        <form method="POST" action="{{ route('settings.password') }}">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                        <input type="password" name="new_password" required minlength="8" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors"><i class="fas fa-key mr-1"></i> Change Password</button>
            </div>
        </form>
    </div>

    <!-- Account Info -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-4"><i class="fas fa-info-circle text-slate-400 mr-2"></i>Account Information</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">Role:</span> <span class="font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span></div>
            <div><span class="text-slate-500">Status:</span> <span class="font-medium {{ auth()->user()->is_active ? 'text-green-600' : 'text-red-600' }}">{{ auth()->user()->is_active ? 'Active' : 'Inactive' }}</span></div>
            <div><span class="text-slate-500">Member since:</span> <span class="font-medium text-slate-800">{{ auth()->user()->created_at->format('d M Y') }}</span></div>
        </div>
    </div>
</div>
@endsection
