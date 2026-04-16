<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaybackController extends Controller
{
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

        return view('playback.index', compact('cameras'));
    }

    /**
     * Build an RTSP playback URL for Hikvision ISAPI and return session info.
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
        $startDatetime = str_replace(['-', ':'], '', $date).'T'.str_replace(':', '', $startTime).'Z';
        $endDatetime = str_replace(['-', ':'], '', $date).'T'.str_replace(':', '', $endTime).'Z';

        // Build RTSP playback URL for Hikvision
        $playbackUrl = sprintf(
            'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
            $nvr->username,
            $nvr->password,
            $nvr->ip_address,
            $nvr->port ?? 554,
            $camera->channel_no,
            $startDatetime,
            $endDatetime
        );

        ActivityLog::log('playback_requested', "Playback requested for camera '{$camera->name}'", [
            'camera_id' => $camera->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return response()->json([
            'success' => true,
            'playback_url' => $playbackUrl,
            'camera_name' => $camera->name,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'camera' => [
                'id' => $camera->id,
                'name' => $camera->name,
                'channel_no' => $camera->channel_no,
            ],
            'nvr' => [
                'id' => $nvr->id,
                'name' => $nvr->name,
                'ip_address' => $nvr->ip_address,
            ],
        ]);
    }
}
