<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Services\FFmpegStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LiveViewController extends Controller
{
    public function __construct(
        protected FFmpegStreamService $streamService
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

        return view('live.index', compact('cameras', 'buildings', 'currentLayout'));
    }

    /**
     * Start an FFmpeg stream for a camera and return the HLS URL.
     */
    public function stream(Camera $camera): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canLiveView($camera->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this camera.',
            ], 403);
        }

        try {
            $session = $this->streamService->startStream($camera, $user->id);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start stream. Check camera configuration.',
                ], 500);
            }

            ActivityLog::log('stream_started', "Started live stream for camera '{$camera->name}'", [
                'camera_id' => $camera->id,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'camera_id' => $camera->id,
                'stream_url' => asset('storage/'.$session->stream_path),
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
     * Stop an FFmpeg stream for a camera.
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
}
