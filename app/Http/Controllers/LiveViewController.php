<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Services\Go2rtcStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LiveViewController extends Controller
{
    public function __construct(
        protected Go2rtcStreamService $streamService
    ) {}

    /**
     * Show the live monitoring page.
     */
    public function index(Request $request): View
    {
        $currentLayout = (int) $request->input('layout', 4);
        $allowedLayouts = [1, 4, 9, 16, 32, 64];

        if (! in_array($currentLayout, $allowedLayouts)) {
            $currentLayout = 4;
        }

        $user = Auth::user();

        // Superadmin sees all cameras; others see only permitted cameras
        if ($user->isSuperadmin()) {
            $cameras = Camera::with(['nvr', 'building', 'aiSetting'])
                ->active()
                ->orderBy('building_id')
                ->orderBy('nvr_id')
                ->orderBy('channel_no')
                ->get();
        } else {
            $cameraIds = $user->cameraPermissions()
                ->where('can_live_view', true)
                ->pluck('camera_id');

            $cameras = Camera::with(['nvr', 'building', 'aiSetting'])
                ->whereIn('id', $cameraIds)
                ->active()
                ->orderBy('building_id')
                ->orderBy('nvr_id')
                ->orderBy('channel_no')
                ->get();
        }

        $buildings = Building::orderBy('name')->get();
        $go2rtcApiUrl = $this->streamService->getApiUrl();

        // Don't pre-register streams during page load — it blocks rendering
        // when go2rtc is not yet running (2s timeout × N cameras = very slow).
        // Instead, streams are registered via AJAX after the page loads and
        // go2rtc is confirmed online (see preRegisterStreams endpoint).

        return view('live.index', compact('cameras', 'buildings', 'currentLayout', 'go2rtcApiUrl'));
    }

    /**
     * Start a stream for a camera via go2rtc and return connection URLs.
     */
    public function stream(Request $request, Camera $camera): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canLiveView($camera->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this camera.',
            ], 403);
        }

        $streamType = $request->input('stream_type', 'sub');
        if (! in_array($streamType, ['main', 'sub'])) {
            $streamType = 'sub';
        }

        try {
            $session = $this->streamService->startStream($camera, $user->id, $streamType);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start stream. Check camera configuration or go2rtc status.',
                ], 500);
            }

            $streamName = $session->stream_path;

            ActivityLog::log('stream_started', "Started live stream for camera '{$camera->name}'", [
                'camera_id' => $camera->id,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'camera_id' => $camera->id,
                'stream_name' => $streamName,
                'mse_url' => $this->streamService->getMseWsUrl($streamName),
                'webrtc_url' => $this->streamService->getWebRtcUrl($streamName),
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start stream: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop a stream for a camera.
     */
    public function stopStream(Camera $camera): JsonResponse
    {
        try {
            $this->streamService->stopStream($camera->id);

            ActivityLog::log('stream_stopped', "Stopped live stream for camera '{$camera->name}'", [
                'camera_id' => $camera->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stream stopped successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop stream: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a stream is active for a camera.
     */
    public function streamStatus(Camera $camera): JsonResponse
    {
        $isActive = $this->streamService->isStreamActive($camera->id);
        $streamUrl = $this->streamService->getStreamUrl($camera->id);

        return response()->json([
            'camera_id' => $camera->id,
            'is_active' => $isActive,
            'stream_url' => $streamUrl,
        ]);
    }

    /**
     * Ensure go2rtc is running (auto-start if needed).
     */
    public function ensureGo2rtc(): JsonResponse
    {
        $started = $this->streamService->ensureRunning();

        return response()->json([
            'success' => $started,
            'message' => $started ? 'go2rtc is running.' : 'Failed to start go2rtc.',
        ]);
    }

    /**
     * Pre-register all camera sub-streams in go2rtc (called via AJAX after page load).
     * This avoids blocking page render when go2rtc is still starting up.
     */
    public function preRegisterStreams(): JsonResponse
    {
        $user = Auth::user();

        if ($user->isSuperadmin()) {
            $cameras = Camera::with('nvr')->active()->get();
        } else {
            $cameraIds = $user->cameraPermissions()
                ->where('can_live_view', true)
                ->pluck('camera_id');

            $cameras = Camera::with('nvr')
                ->whereIn('id', $cameraIds)
                ->active()
                ->get();
        }

        $registered = 0;
        foreach ($cameras as $camera) {
            try {
                // Register both sub and main streams so they're ready instantly
                $this->streamService->ensureStreamRegistered($camera, 'sub');
                $this->streamService->ensureStreamRegistered($camera, 'main');
                $registered++;
            } catch (\Exception $e) {
                // Skip failed registrations silently
            }
        }

        return response()->json([
            'success' => true,
            'registered' => $registered,
            'total' => $cameras->count(),
        ]);
    }
}
