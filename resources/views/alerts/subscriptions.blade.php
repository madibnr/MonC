@extends('layouts.app')
@section('title', 'Alert Subscriptions')
@section('page-title', 'Alert Subscriptions')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">Configure which alerts you want to receive and how.</p>
        <a href="{{ route('alerts.index') }}" class="text-sm text-blue-500 hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i>Back to Alerts</a>
    </div>

    <form method="POST" action="{{ route('alerts.subscriptions.update') }}">
        @csrf @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead><tr class="bg-slate-50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Alert Type</th>
                        @foreach($channels as $channelKey => $channelName)
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">
                            @if($channelKey === 'web')<i class="fas fa-bell mr-1"></i>
                            @elseif($channelKey === 'email')<i class="fas fa-envelope mr-1"></i>
                            @else<i class="fab fa-telegram mr-1"></i>@endif
                            {{ $channelName }}
                        </th>
                        @endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($alertTypes as $typeKey => $typeName)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $typeName }}</td>
                            @foreach($channels as $channelKey => $channelName)
                            @php $isActive = isset($subscriptions[$typeKey]) && $subscriptions[$typeKey]->contains('channel', $channelKey); @endphp
                            <td class="px-6 py-4 text-center">
                                <input type="hidden" name="subscriptions[{{ $typeKey }}][{{ $channelKey }}]" value="0">
                                <input type="checkbox" name="subscriptions[{{ $typeKey }}][{{ $channelKey }}]" value="1" {{ $isActive ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500">
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                <i class="fas fa-save mr-1"></i> Save Subscriptions
            </button>
        </div>
    </form>

    <!-- Telegram Setup Info -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <h4 class="text-sm font-semibold text-slate-800 mb-2"><i class="fab fa-telegram text-blue-500 mr-2"></i>Telegram Setup</h4>
        <p class="text-xs text-slate-600">To receive Telegram notifications, set your Telegram Chat ID in Settings. Contact the bot and send /start to get your Chat ID.</p>
    </div>
</div>
@endsection
