@extends('layouts.app')
@section('title', 'Building Management')
@section('page-title', 'Building Management')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm text-slate-500">{{ $buildings->count() }} building(s)</h3>
        <a href="{{ route('buildings.create') }}" class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus"></i> Add Building
        </a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Address</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">NVRs</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cameras</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Active</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($buildings as $building)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $building->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 font-mono">{{ $building->code }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $building->address ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $building->nvrs_count }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $building->cameras_count }}</td>
                        <td class="px-4 py-3"><span class="text-xs font-medium {{ $building->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $building->is_active ? 'Yes' : 'No' }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('buildings.show', $building) }}" class="text-slate-500 hover:text-slate-700 text-sm" title="View"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('buildings.edit', $building) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('buildings.destroy', $building) }}" onsubmit="return confirm('Delete this building and all its NVRs/cameras?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400"><i class="fas fa-building text-2xl mb-2 block opacity-30"></i>No buildings found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
