@extends('layouts.app')

@section('title', 'Add to Watchlist')
@section('page-title', 'Add to Watchlist')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('ai.watchlist.index') }}" class="text-sm text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Watchlist
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-800">Add Plate to Watchlist</h3>
        </div>

        <form method="POST" action="{{ route('ai.watchlist.store') }}" class="p-5 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Plate Number <span class="text-red-500">*</span></label>
                <input type="text" name="plate_number" value="{{ old('plate_number') }}" required
                       placeholder="e.g. B 1234 XYZ"
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
                    <option value="{{ $value }}" {{ old('alert_level', 'medium') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Reason</label>
                <input type="text" name="reason" value="{{ old('reason') }}"
                       placeholder="e.g. Stolen vehicle, Suspicious activity"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle Owner</label>
                    <input type="text" name="vehicle_owner" value="{{ old('vehicle_owner') }}"
                           placeholder="Owner name"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle Description</label>
                    <input type="text" name="vehicle_description" value="{{ old('vehicle_description') }}"
                           placeholder="e.g. Red Toyota Avanza"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" placeholder="Additional notes..."
                          class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="notify_telegram" value="1" id="notify_telegram"
                       {{ old('notify_telegram') ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                <label for="notify_telegram" class="text-sm text-slate-700">
                    <i class="fab fa-telegram text-blue-500 mr-1"></i>
                    Send Telegram notification when this plate is detected
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('ai.watchlist.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-plus mr-1"></i> Add to Watchlist
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
