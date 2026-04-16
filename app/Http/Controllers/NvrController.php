<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Models\Nvr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NvrController extends Controller
{
    /**
     * Display a listing of NVRs.
     */
    public function index(): View
    {
        $nvrs = Nvr::with('building')
            ->withCount('cameras')
            ->latest()
            ->get();

        return view('nvrs.index', compact('nvrs'));
    }

    /**
     * Show the form for creating a new NVR.
     */
    public function create(): View
    {
        $buildings = Building::active()->orderBy('name')->get();

        return view('nvrs.create', compact('buildings'));
    }

    /**
     * Store a newly created NVR and auto-generate cameras for all channels.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'name' => ['required', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port' => ['integer'],
            'username' => ['required'],
            'password' => ['required'],
            'model' => ['nullable', 'max:255'],
            'total_channels' => ['integer', 'min:1', 'max:128'],
            'description' => ['nullable'],
        ]);

        $nvr = Nvr::create($validated);

        // Auto-generate cameras for all channels
        $totalChannels = $nvr->total_channels ?? 16;
        $cameras = [];

        for ($ch = 1; $ch <= $totalChannels; $ch++) {
            $cameras[] = [
                'nvr_id' => $nvr->id,
                'building_id' => $nvr->building_id,
                'channel_no' => $ch,
                'name' => "{$nvr->name} - CH{$ch}",
                'status' => 'offline',
                'is_active' => true,
                'sort_order' => $ch,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Camera::insert($cameras);

        ActivityLog::log('nvr_created', "NVR '{$nvr->name}' created with {$totalChannels} cameras", [
            'nvr_id' => $nvr->id,
            'total_channels' => $totalChannels,
        ]);

        return redirect()
            ->route('nvrs.show', $nvr)
            ->with('success', "NVR created successfully with {$totalChannels} cameras.");
    }

    /**
     * Display the specified NVR.
     */
    public function show(Nvr $nvr): View
    {
        $nvr->load(['building', 'cameras']);

        return view('nvrs.show', compact('nvr'));
    }

    /**
     * Show the form for editing the specified NVR.
     */
    public function edit(Nvr $nvr): View
    {
        $buildings = Building::active()->orderBy('name')->get();

        return view('nvrs.edit', compact('nvr', 'buildings'));
    }

    /**
     * Update the specified NVR.
     */
    public function update(Request $request, Nvr $nvr): RedirectResponse
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'name' => ['required', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port' => ['integer'],
            'username' => ['required'],
            'password' => ['nullable'],
            'model' => ['nullable', 'max:255'],
            'total_channels' => ['integer', 'min:1', 'max:128'],
            'status' => ['nullable', 'in:online,offline,maintenance'],
            'is_active' => ['nullable'],
            'description' => ['nullable'],
        ]);

        // Remove password from update if not provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['is_active'] = ! empty($validated['is_active']);

        $nvr->update($validated);

        ActivityLog::log('nvr_updated', "NVR '{$nvr->name}' updated", [
            'nvr_id' => $nvr->id,
        ]);

        return redirect()
            ->route('nvrs.show', $nvr)
            ->with('success', 'NVR updated successfully.');
    }

    /**
     * Remove the specified NVR.
     */
    public function destroy(Nvr $nvr): RedirectResponse
    {
        $name = $nvr->name;

        $nvr->cameras()->delete();
        $nvr->delete();

        ActivityLog::log('nvr_deleted', "NVR '{$name}' deleted");

        return redirect()
            ->route('nvrs.index')
            ->with('success', 'NVR deleted successfully.');
    }

    /**
     * Ping the NVR IP to check if it is online.
     */
    public function checkStatus(Nvr $nvr): JsonResponse
    {
        $ip = $nvr->ip_address;

        // Attempt to open a socket connection to the NVR
        $timeout = 3; // seconds
        $connection = @fsockopen($ip, $nvr->port ?? 554, $errno, $errstr, $timeout);

        if ($connection) {
            fclose($connection);
            $status = 'online';
            $nvr->update([
                'status' => 'online',
                'last_seen_at' => now(),
            ]);
        } else {
            $status = 'offline';
            $nvr->update([
                'status' => 'offline',
            ]);
        }

        return response()->json([
            'nvr_id' => $nvr->id,
            'name' => $nvr->name,
            'ip_address' => $ip,
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
