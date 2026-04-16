<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCameraAccess
{
    public function handle(Request $request, Closure $next, string $permission = 'can_live_view'): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Superadmin can access everything
        if ($user->isSuperadmin()) {
            return $next($request);
        }

        $cameraId = $request->route('camera') ?? $request->input('camera_id');

        if (! $cameraId) {
            abort(400, 'Camera ID is required.');
        }

        // Resolve camera ID if it's a model instance
        if (is_object($cameraId)) {
            $cameraId = $cameraId->id;
        }

        $method = match ($permission) {
            'can_live_view' => 'canLiveView',
            'can_playback' => 'canPlayback',
            'can_export' => 'canExport',
            default => 'canAccessCamera',
        };

        if (! $user->$method($cameraId)) {
            abort(403, 'You do not have permission to access this camera.');
        }

        return $next($request);
    }
}
