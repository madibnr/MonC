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

        return view('cameras.show', compact('camera'));
    }

    /**
     * Show the form for editing the specified camera.
     */
    public function edit(Camera $camera): View
    {
        $buildings = Building::active()->orderBy('name')->get();
        $nvrs = Nvr::active()->orderBy('name')->get();

        return view('cameras.edit', compact('camera', 'buildings', 'nvrs'));
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
}
