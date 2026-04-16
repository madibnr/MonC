<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with(['user', 'camera'])->latest();

        // Filters
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }
        if ($request->filled('action_type')) {
            $query->byAction($request->action_type);
        }
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }
        if ($request->filled('camera_id')) {
            $query->byCamera($request->camera_id);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $logs = $query->paginate(50);
        $users = User::orderBy('name')->get();
        $cameras = Camera::orderBy('name')->get();

        $modules = ['auth', 'live', 'playback', 'export', 'snapshot', 'camera', 'nvr', 'building', 'user', 'permission', 'settings', 'alert', 'system'];
        $actionTypes = ['login', 'logout', 'live_view', 'playback', 'export', 'snapshot', 'permission_change', 'settings_change', 'camera_manage', 'nvr_manage', 'building_manage', 'user_manage'];

        return view('audit-logs.index', compact('logs', 'users', 'cameras', 'modules', 'actionTypes'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load(['user', 'camera']);

        return view('audit-logs.show', compact('auditLog'));
    }
}
