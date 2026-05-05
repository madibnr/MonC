<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Models\Nvr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CameraController extends Controller
{
    /**
     * Display a listing of cameras with filters.
     */
    public function index(Request $request): View
    {
        $query = Camera::with(['nvr', 'building']);

        // Filter by building
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->input('building_id'));
        }

        // Filter by NVR
        if ($request->filled('nvr_id')) {
            $query->where('nvr_id', $request->input('nvr_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $cameras = $query->orderBy('building_id')
            ->orderBy('nvr_id')
            ->orderBy('channel_no')
            ->paginate(25)
            ->withQueryString();

        $buildings = Building::orderBy('name')->get();
        $nvrs = Nvr::orderBy('name')->get();

        return view('cameras.index', compact('cameras', 'buildings', 'nvrs'));
    }

    /**
     * Show the form for creating a new camera.
     */
    public function create(): View
    {
        $buildings = Building::active()->orderBy('name')->get();
        $nvrs = Nvr::active()->orderBy('name')->get();

        return view('cameras.create', compact('buildings', 'nvrs'));
    }

    /**
     * Store a newly created camera.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nvr_id' => ['required', 'exists:nvrs,id'],
            'building_id' => ['required', 'exists:buildings,id'],
            'channel_no' => ['required', 'integer'],
            'name' => ['required', 'max:255'],
            'location' => ['nullable'],
            'description' => ['nullable'],
            'stream_url' => ['nullable'],
            'sub_stream_url' => ['nullable'],
            'is_active' => ['boolean'],
        ]);

        $camera = Camera::create($validated);

        ActivityLog::log('camera_created', "Camera '{$camera->name}' created", [
            'camera_id' => $camera->id,
        ]);

        return redirect()
            ->route('cameras.show', $camera)
            ->with('success', 'Camera created successfully.');
    }

    /**
     * Display the specified camera.
     */
    public function show(Camera $camera): View
    {
        $camera->load(['nvr', 'building']);

        // Get previous and next camera for navigation
        $previousCamera = Camera::where('id', '<', $camera->id)
            ->orderBy('id', 'desc')
            ->first();

        $nextCamera = Camera::where('id', '>', $camera->id)
            ->orderBy('id', 'asc')
            ->first();

        return view('cameras.show', compact('camera', 'previousCamera', 'nextCamera'));
    }

    /**
     * Show the form for editing the specified camera.
     */
    public function edit(Camera $camera): View
    {
        $buildings = Building::active()->orderBy('name')->get();
        $nvrs = Nvr::active()->orderBy('name')->get();

        // Get previous and next camera for navigation
        $previousCamera = Camera::where('id', '<', $camera->id)
            ->orderBy('id', 'desc')
            ->first();

        $nextCamera = Camera::where('id', '>', $camera->id)
            ->orderBy('id', 'asc')
            ->first();

        return view('cameras.edit', compact('camera', 'buildings', 'nvrs', 'previousCamera', 'nextCamera'));
    }

    /**
     * Update the specified camera.
     */
    public function update(Request $request, Camera $camera): RedirectResponse
    {
        $validated = $request->validate([
            'nvr_id' => ['required', 'exists:nvrs,id'],
            'building_id' => ['required', 'exists:buildings,id'],
            'channel_no' => ['required', 'integer'],
            'name' => ['required', 'max:255'],
            'location' => ['nullable'],
            'description' => ['nullable'],
            'stream_url' => ['nullable'],
            'sub_stream_url' => ['nullable'],
            'is_active' => ['boolean'],
        ]);

        $camera->update($validated);

        ActivityLog::log('camera_updated', "Camera '{$camera->name}' updated", [
            'camera_id' => $camera->id,
        ]);

        return redirect()
            ->route('cameras.show', $camera)
            ->with('success', 'Camera updated successfully.');
    }

    /**
     * Remove the specified camera.
     */
    public function destroy(Camera $camera): RedirectResponse
    {
        $name = $camera->name;

        $camera->permissions()->delete();
        $camera->streamSessions()->delete();
        $camera->delete();

        ActivityLog::log('camera_deleted', "Camera '{$name}' deleted");

        return redirect()
            ->route('cameras.index')
            ->with('success', 'Camera deleted successfully.');
    }

    /**
     * Check camera status manually using go2rtc (no FFmpeg required).
     */
    public function checkStatus(Camera $camera)
    {
        try {
            $go2rtcApiUrl = rtrim(config('monc.go2rtc.api_url', 'http://127.0.0.1:1984'), '/');
            $streamUrl = $camera->getSubStreamUrl();
            $streamName = "probe_camera_{$camera->id}";

            // Register stream in go2rtc
            $putUrl = "{$go2rtcApiUrl}/api/streams?name={$streamName}&src=" . urlencode($streamUrl);
            $ch = curl_init($putUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);

            // Wait for go2rtc to connect
            usleep(2000000); // 2 seconds

            // Check if stream has producers
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("{$go2rtcApiUrl}/api/streams");
            $streams = $response->json() ?? [];

            $isOnline = false;
            if (isset($streams[$streamName])) {
                $producers = $streams[$streamName]['producers'] ?? [];
                $isOnline = ! empty($producers);
            }

            // Cleanup probe stream
            \Illuminate\Support\Facades\Http::timeout(3)->delete("{$go2rtcApiUrl}/api/streams?name={$streamName}");

            $previousStatus = $camera->status;
            $camera->update([
                'status' => $isOnline ? 'online' : 'offline',
                'last_seen_at' => $isOnline ? now() : $camera->last_seen_at,
            ]);

            ActivityLog::log('camera_status_checked', "Camera '{$camera->name}' status checked: {$camera->status}", [
                'camera_id' => $camera->id,
                'previous_status' => $previousStatus,
                'current_status' => $camera->status,
            ]);

            return redirect()
                ->route('cameras.show', $camera)
                ->with('success', "Camera status updated: {$camera->status}");
        } catch (\Exception $e) {
            return redirect()
                ->route('cameras.show', $camera)
                ->with('error', 'Failed to check camera status: ' . $e->getMessage());
        }
    }
}
