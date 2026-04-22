@extends('layouts.app')
@section('title', 'Export Clips')
@section('page-title', 'Export Clips')

@section('content')
<div x-data="exportManager()" x-init="init()">
    <!-- Queue Worker Status Alert -->
    <div x-show="!queueWorkerActive" x-cloak class="mb-4 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-lg mt-0.5"></i>
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-yellow-800 mb-1">Queue Worker Not Running</h4>
                <p class="text-sm text-yellow-700 mb-2">Export jobs are queued but not being processed. The queue worker needs to be running.</p>
                <div class="bg-yellow-100 rounded px-3 py-2 text-xs font-mono text-yellow-900">
                    php artisan queue:work
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Export Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-800 mb-4">
                    <i class="fas fa-file-export text-slate-400 mr-2"></i>New Export
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Camera</label>
                        <select x-model="form.camera_id" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select Camera</option>
                            @foreach($camerasGrouped as $buildingName => $cameras)
                            <optgroup label="{{ $buildingName }}">
                                @foreach($cameras as $camera)
                                <option value="{{ $camera->id }}">{{ $camera->name }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
                        <input type="date" x-model="form.clip_date" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Start Time</label>
                            <input type="time" x-model="form.start_time" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">End Time</label>
                            <input type="time" x-model="form.end_time" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-lg px-3 py-2 text-xs text-slate-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Export will download the recording from NVR as MP4. Processing time depends on clip duration.
                    </div>

                    <button @click="submitExport()" :disabled="isSubmitting || !form.camera_id || !form.clip_date || !form.start_time || !form.end_time"
                            class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                        <span x-text="isSubmitting ? 'Submitting...' : 'Export MP4'"></span>
                    </button>
                    <div x-show="message" x-cloak class="text-sm px-3 py-2 rounded-lg" :class="messageType === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" x-text="message"></div>
                </div>
            </div>
        </div>

        <!-- Export History -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-800"><i class="fas fa-history text-slate-400 mr-2"></i>Export History</h3>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <i class="fas fa-sync-alt" :class="{'fa-spin': isRefreshing}"></i>
                        <span>Auto-refresh: <span x-text="autoRefreshCountdown"></span>s</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="bg-slate-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Camera</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Time Range</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Size</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($exports as $export)
                            <tr class="hover:bg-slate-50" id="export-row-{{ $export->id }}">
                                <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $export->camera->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $export->clip_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ \Carbon\Carbon::parse($export->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($export->end_time)->format('H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if($export->status === 'completed')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700"><i class="fas fa-check text-[8px]"></i> Completed</span>
                                    @elseif($export->status === 'processing')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700"><i class="fas fa-spinner fa-spin text-[8px]"></i> Processing {{ $export->progress }}%</span>
                                    @elseif($export->status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600"><i class="fas fa-clock text-[8px]"></i> Pending</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 cursor-help" title="{{ $export->error_message }}"><i class="fas fa-times text-[8px]"></i> Failed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $export->getFileSizeFormatted() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($export->status === 'completed')
                                        <a href="{{ route('exports.download', $export) }}" class="text-blue-500 hover:text-blue-700 text-sm" title="Download"><i class="fas fa-download"></i></a>
                                        @endif
                                        <form method="POST" action="{{ route('exports.destroy', $export) }}" onsubmit="return confirm('Delete this export?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400"><i class="fas fa-file-video text-2xl mb-2 block opacity-30"></i>No exports yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($exports->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">{{ $exports->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function exportManager() {
    return {
        form: { camera_id: '', clip_date: new Date().toISOString().split('T')[0], start_time: '08:00', end_time: '08:15' },
        isSubmitting: false,
        message: '',
        messageType: 'success',
        queueWorkerActive: true,
        isRefreshing: false,
        autoRefreshCountdown: 10,
        refreshInterval: null,
        countdownInterval: null,

        init() {
            this.checkQueueStatus();
            this.startAutoRefresh();
        },

        async checkQueueStatus() {
            try {
                const res = await fetch('/exports/queue-status', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.queueWorkerActive = data.worker_active;
            } catch(e) {
                console.error('Failed to check queue status:', e);
            }
        },

        startAutoRefresh() {
            this.countdownInterval = setInterval(() => {
                this.autoRefreshCountdown--;
                if (this.autoRefreshCountdown <= 0) {
                    this.autoRefreshCountdown = 10;
                }
            }, 1000);

            this.refreshInterval = setInterval(() => {
                this.refreshPage();
            }, 10000);
        },

        async refreshPage() {
            this.isRefreshing = true;
            await this.checkQueueStatus();
            
            const hasPendingOrProcessing = document.querySelectorAll('.bg-blue-100, .bg-slate-100').length > 0;
            
            if (hasPendingOrProcessing) {
                location.reload();
            }
            
            this.isRefreshing = false;
        },

        async submitExport() {
            this.isSubmitting = true;
            this.message = '';
            try {
                const res = await fetch('/exports', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    this.message = data.message;
                    this.messageType = 'success';
                    await this.checkQueueStatus();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    this.message = data.message || 'Export failed';
                    this.messageType = 'error';
                }
            } catch(e) {
                this.message = 'Failed to submit export request';
                this.messageType = 'error';
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endsection
