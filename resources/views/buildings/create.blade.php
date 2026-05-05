@extends('layouts.app')
@section('title', 'Add Building')
@section('page-title', 'Add Building')
@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h3 class="text-base font-semibold text-slate-800 mb-6"><i class="fas fa-building text-slate-400 mr-2"></i>New Building</h3>
        <form method="POST" action="{{ route('buildings.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Building Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="e.g. GU, GP" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-200">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors"><i class="fas fa-save mr-1"></i> Save Building</button>
                <a href="{{ route('buildings.index') }}" class="text-sm text-slate-600 hover:text-slate-800">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
