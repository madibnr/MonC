<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Camera;
use App\Services\Go2rtcStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaybackController extends Controller
{
    public function __construct(
        protected Go2rtcStreamService $streamService
    ) {}

    /**
     * Show the playback page with camera selector.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Superadmin sees all cameras; others see only permitted cameras
        if ($user->isSuperadmin()) {
            $cameras = Camera::with(['nvr', 'building'])
                ->active()
                ->orderBy('building_id')
                ->orderBy('nvr_id')
                ->orderBy('channel_no')
                ->get();
        } else {
            $cameraIds = $user->cameraPermissions()
                ->where('can_playback', true)
                ->pluck('camera_id');

            $cameras = Camera::with(['nvr', 'building'])
                ->whereIn('id', $cameraIds)
                ->active()
                ->orderBy('building_id')
                ->orderBy('nvr_id')
                ->orderBy('channel_no')
                ->get();
        }

        // Group cameras by building name for the view's optgroup
        $cameras = $cameras->groupBy(function ($camera) {
            return $camera->building->name ?? 'Unknown';
        });

        $go2rtcApiUrl = $this->streamService->getApiUrl();

        return view('playback.index', compact('cameras', 'go2rtcApiUrl'));
    }

    /**
     * Build an RTSP playback URL, register it in go2rtc, and return stream info.
     */
    public function play(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'exists:cameras,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
        ]);

        $camera = Camera::with('nvr')->findOrFail($validated['camera_id']);
        $user = Auth::user();

        // Check playback permission
        if (! $user->canPlayback($camera->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to playback this camera.',
            ], 403);
        }

        $nvr = $camera->nvr;
        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // Format timestamps for Hikvision ISAPI: YYYYMMDDTHHmmssZ
        $startFormatted = str_replace(['-', ':'], '', $date) . 'T' . str_replace(':', '', $startTime) . '00Z';
        $endFormatted = str_replace(['-', ':'], '', $date) . 'T' . str_replace(':', '', $endTime) . '00Z';

        // Build RTSP playback URL for Hikvision
        $playbackUrl = sprintf(
            'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
            $nvr->username,
            $nvr->password,
            $nvr->ip_address,
            $nvr->port ?? 554,
            $camera->channel_no,
            $startFormatted,
            $endFormatted
        );

        // Register the playback RTSP URL in go2rtc
        $streamName = $this->streamService->startPlaybackStream($camera->id, $playbackUrl);

        if (! $streamName) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start playback stream. Check go2rtc status.',
            ], 500);
        }

        ActivityLog::log('playback_requested', "Playback requested for camera '{$camera->name}'", [
            'camera_id' => $camera->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return response()->json([
            'success' => true,
            'stream_name' => $streamName,
            'camera_name' => $camera->name,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Stop a playback stream.
     */
    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'exists:cameras,id'],
        ]);

        $this->streamService->stopPlaybackStream($validated['camera_id']);

        return response()->json([
            'success' => true,
            'message' => 'Playback stream stopped.',
        ]);
    }
}
