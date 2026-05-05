@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Cameras -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Cameras</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalCameras }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-circle text-[8px]"></i> {{ $onlineCameras }} online
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-camera text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Total NVRs -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total NVRs</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalNvrs }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-circle text-[8px]"></i> {{ $onlineNvrs }} online
                    </p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-server text-emerald-500 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Buildings -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Buildings</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalBuildings }}</p>
                    <p class="text-xs text-slate-400 mt-1">Monitored zones</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-amber-500 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Users -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Users</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalUsers }}</p>
                    <p class="text-xs text-slate-400 mt-1">Active accounts</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-800">
                <i class="fas fa-history text-slate-400 mr-2"></i>Recent Activity
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3 text-sm text-slate-800">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-700">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ Str::limit($log->description, 60) }}</td>
                        <td class="px-6 py-3 text-sm text-slate-500 font-mono">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-400">
                            <i class="fas fa-inbox text-2xl mb-2 block"></i>
                            No activity recorded yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
