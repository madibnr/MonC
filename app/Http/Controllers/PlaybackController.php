<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Services\Go2rtcStreamService;
use App\Services\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaybackController extends Controller
{
    public function __construct(
        protected PlaybackService $playbackService,
        protected Go2rtcStreamService $streamService,
    ) {}

    /**
     * Show the playback page.
     */
    public function index(): View
    {
        $user = Auth::user();

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

        // Group: Building → NVR → cameras
        $camerasGrouped = $cameras->groupBy(fn ($c) => $c->building->name ?? 'Unknown')
            ->map(fn ($cams) => $cams->groupBy(fn ($c) => $c->nvr->name ?? 'NVR'));

        $go2rtcApiUrl = $this->streamService->getApiUrl();

        return view('playback.index', compact('camerasGrouped', 'cameras', 'go2rtcApiUrl'));
    }

    // ── API Endpoints ───────────────────────────────────────────

    /**
     * GET /api/playback/timeline
     * Returns timeline blocks for a camera on a date.
     */
    public function timeline(Request $request): JsonResponse
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'date'      => 'required|date',
        ]);

        $blocks = $this->playbackService->buildTimeline(
            (int) $request->camera_id,
            $request->date
        );

        $summary = $this->playbackService->getRecordingSummary(
            (int) $request->camera_id,
            $request->date
        );

        return response()->json([
            'blocks'  => $blocks,
            'summary' => $summary,
        ]);
    }

    /**
     * GET /api/playback/segments
     * Returns raw segment list for a camera on a date.
     */
    public function segments(Request $request): JsonResponse
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'date'      => 'required|date',
        ]);

        $segments = $this->playbackService->getSegmentsForDate(
            (int) $request->camera_id,
            $request->date
        );

        return response()->json([
            'segments' => $segments->map(fn ($s) => [
                'id'         => $s->id,
                'start_time' => $s->start_time->toIso8601String(),
                'end_time'   => $s->end_time->toIso8601String(),
                'duration'   => $s->duration_seconds,
                'file_size'  => $s->file_size,
                'url'        => $s->getPublicUrl(),
            ]),
        ]);
    }

    /**
     * GET /api/playback/stream
     * Resolve the correct segment for a timestamp and return stream URL.
     */
    public function stream(Request $request): JsonResponse
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'timestamp' => 'required|date',
        ]);

        $segment = $this->playbackService->resolveSegmentByTime(
            (int) $request->camera_id,
            $request->timestamp
        );

        if (! $segment) {
            return response()->json([
                'success' => false,
                'message' => 'No recording found for this time.',
            ], 404);
        }

        $seekOffset = $this->playbackService->calculateSeekOffset($segment, $request->timestamp);
        $nextSegment = $this->playbackService->getNextSegment($segment);

        return response()->json([
            'success' => true,
            'segment' => [
                'id'         => $segment->id,
                'start_time' => $segment->start_time->toIso8601String(),
                'end_time'   => $segment->end_time->toIso8601String(),
                'duration'   => $segment->duration_seconds,
                'url'        => $segment->getPublicUrl(),
                'seek'       => round($seekOffset, 2),
            ],
            'next_segment' => $nextSegment ? [
                'id'         => $nextSegment->id,
                'start_time' => $nextSegment->start_time->toIso8601String(),
                'url'        => $nextSegment->getPublicUrl(),
            ] : null,
        ]);
    }

    /**
     * POST /playback/play (legacy go2rtc-based playback for NVR direct access)
     * Kept for backward compatibility when no local segments exist.
     */
    public function play(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id'  => ['required', 'exists:cameras,id'],
            'date'       => ['required', 'date'],
            'start_time' => ['required'],
            'end_time'   => ['required'],
        ]);

        $camera = Camera::with('nvr')->findOrFail($validated['camera_id']);
        $user = Auth::user();

        if (! $user->canPlayback($camera->id)) {
            return response()->json(['success' => false, 'message' => 'No permission.'], 403);
        }

        $nvr = $camera->nvr;
        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        $startFmt = str_replace(['-', ':'], '', $date) . 'T' . str_replace(':', '', $startTime) . '00Z';
        $endFmt   = str_replace(['-', ':'], '', $date) . 'T' . str_replace(':', '', $endTime) . '00Z';

        $rtspUrl = sprintf(
            'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
            $nvr->username, $nvr->password,
            $nvr->ip_address, $nvr->port ?? 554,
            $camera->channel_no,
            $startFmt, $endFmt
        );

        $streamName = $this->streamService->startPlaybackStream($camera->id, $rtspUrl);

        if (! $streamName) {
            return response()->json(['success' => false, 'message' => 'Failed to start playback stream.'], 500);
        }

        ActivityLog::log('playback_requested', "Playback for camera '{$camera->name}'", [
            'camera_id' => $camera->id, 'date' => $date,
        ]);

        return response()->json([
            'success'     => true,
            'stream_name' => $streamName,
            'camera_name' => $camera->name,
            'date'        => $date,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
        ]);
    }

    /**
     * DELETE /playback/stop
     */
    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate(['camera_id' => 'required|exists:cameras,id']);
        $this->streamService->stopPlaybackStream($validated['camera_id']);

        return response()->json(['success' => true]);
    }
}
