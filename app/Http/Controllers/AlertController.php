<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\AuditLog;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService
    ) {}

    /**
     * Show alerts list.
     */
    public function index(Request $request): View
    {
        $query = Alert::latest();

        if ($request->filled('type')) {
            $query->byType($request->type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->input('status') === 'unresolved') {
            $query->unresolved();
        } elseif ($request->input('status') === 'resolved') {
            $query->resolved();
        }

        $alerts = $query->paginate(30);
        $unresolvedCount = Alert::unresolved()->count();
        $criticalCount = Alert::unresolved()->critical()->count();

        return view('alerts.index', compact('alerts', 'unresolvedCount', 'criticalCount'));
    }

    /**
     * Get unread alerts count (for header badge via AJAX).
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->alertService->getUnreadCount(),
            'critical' => Alert::unresolved()->critical()->count(),
        ]);
    }

    /**
     * Get recent alerts (for dropdown via AJAX).
     */
    public function recent(): JsonResponse
    {
        $alerts = Alert::unresolved()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'severity' => $a->severity,
                'title' => $a->title,
                'message' => \Str::limit($a->message, 80),
                'icon' => $a->getTypeIcon(),
                'color' => $a->getSeverityColor(),
                'time' => $a->created_at->diffForHumans(),
                'is_read' => $a->is_read,
            ]);

        return response()->json(['alerts' => $alerts]);
    }

    /**
     * Mark alert as read.
     */
    public function markRead(Alert $alert): JsonResponse
    {
        $alert->markRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all alerts as read.
     */
    public function markAllRead(): JsonResponse
    {
        Alert::unread()->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Resolve an alert.
     */
    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        $notes = $request->input('resolution_notes');
        $alert->resolve(Auth::id(), $notes);

        AuditLog::logSystem('alert_resolved', "Alert resolved: {$alert->title}", [
            'alert_id' => $alert->id,
        ]);

        return redirect()->route('alerts.index')
            ->with('success', 'Alert resolved successfully.');
    }

    /**
     * Show alert subscriptions management.
     */
    public function subscriptions(): View
    {
        $user = Auth::user();
        $subscriptions = $user->alertSubscriptions()->get()->groupBy('alert_type');

        $alertTypes = [
            'all' => 'All Alerts',
            'camera_offline' => 'Camera Offline',
            'nvr_disconnected' => 'NVR Disconnected',
            'hdd_critical' => 'HDD Critical',
            'recording_failed' => 'Recording Failed',
            'stream_error' => 'Stream Error',
        ];

        $channels = ['web' => 'Web Notification', 'email' => 'Email', 'telegram' => 'Telegram'];

        return view('alerts.subscriptions', compact('subscriptions', 'alertTypes', 'channels'));
    }

    /**
     * Update alert subscriptions.
     */
    public function updateSubscriptions(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $subs = $request->input('subscriptions', []);

        // Delete all existing
        $user->alertSubscriptions()->delete();

        // Create new
        foreach ($subs as $type => $channels) {
            foreach ($channels as $channel => $active) {
                if ($active) {
                    AlertSubscription::create([
                        'user_id' => $user->id,
                        'alert_type' => $type,
                        'channel' => $channel,
                        'is_active' => true,
                    ]);
                }
            }
        }

        AuditLog::logSettingsChange('Alert subscriptions updated');

        return redirect()->route('alerts.subscriptions')
            ->with('success', 'Alert subscriptions updated.');
    }
}
