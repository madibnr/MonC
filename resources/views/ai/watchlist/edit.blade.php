@extends('layouts.app')

@section('title', 'Edit Watchlist Entry')
@section('page-title', 'Edit Watchlist Entry')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('ai.watchlist.index') }}" class="text-sm text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Watchlist
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-800">Edit Watchlist Entry: {{ $watchlist->plate_number }}</h3>
        </div>

        <form method="POST" action="{{ route('ai.watchlist.update', $watchlist) }}" class="p-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Plate Number <span class="text-red-500">*</span></label>
                <input type="text" name="plate_number" value="{{ old('plate_number', $watchlist->plate_number) }}" required
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none font-mono uppercase tracking-wider @error('plate_number') border-red-500 @enderror">
                @error('plate_number')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Alert Level <span class="text-red-500">*</span></label>
                <select name="alert_level" required
                        class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach(\App\Models\WatchlistPlate::ALERT_LEVELS as $value => $label)
                    <option value="{{ $value }}" {{ old('alert_level', $watchlist->alert_level) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
                <input type="text" name="reason" value="{{ old('reason', $watchlist->reason) }}"
                       placeholder="e.g. Stolen vehicle, Suspicious activity"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle Owner</label>
                    <input type="text" name="vehicle_owner" value="{{ old('vehicle_owner', $watchlist->vehicle_owner) }}"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle Description</label>
                    <input type="text" name="vehicle_description" value="{{ old('vehicle_description', $watchlist->vehicle_description) }}"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none">{{ old('notes', $watchlist->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $watchlist->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="is_active" class="text-sm text-slate-700">Active</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="notify_telegram" value="1" id="notify_telegram"
                           {{ old('notify_telegram', $watchlist->notify_telegram) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="notify_telegram" class="text-sm text-slate-700">
                        <i class="fab fa-telegram text-blue-500 mr-1"></i> Telegram Notification
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('ai.watchlist.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
