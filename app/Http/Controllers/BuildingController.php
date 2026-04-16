<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     */
    public function index(): View
    {
        $buildings = Building::withCount(['nvrs', 'cameras'])
            ->latest()
            ->get();

        return view('buildings.index', compact('buildings'));
    }

    /**
     * Show the form for creating a new building.
     */
    public function create(): View
    {
        return view('buildings.create');
    }

    /**
     * Store a newly created building.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'code' => ['required', 'max:50', 'unique:buildings,code'],
            'address' => ['nullable'],
            'description' => ['nullable'],
        ]);

        $building = Building::create($validated);

        ActivityLog::log('building_created', "Building '{$building->name}' created", [
            'building_id' => $building->id,
        ]);

        return redirect()
            ->route('buildings.show', $building)
            ->with('success', 'Building created successfully.');
    }

    /**
     * Display the specified building.
     */
    public function show(Building $building): View
    {
        $building->load(['nvrs.cameras', 'cameras']);

        return view('buildings.show', compact('building'));
    }

    /**
     * Show the form for editing the specified building.
     */
    public function edit(Building $building): View
    {
        return view('buildings.edit', compact('building'));
    }

    /**
     * Update the specified building.
     */
    public function update(Request $request, Building $building): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'code' => ['required', 'max:50', 'unique:buildings,code,'.$building->id],
            'address' => ['nullable'],
            'description' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = ! empty($validated['is_active']);

        $building->update($validated);

        ActivityLog::log('building_updated', "Building '{$building->name}' updated", [
            'building_id' => $building->id,
        ]);

        return redirect()
            ->route('buildings.show', $building)
            ->with('success', 'Building updated successfully.');
    }

    /**
     * Remove the specified building.
     */
    public function destroy(Building $building): RedirectResponse
    {
        $name = $building->name;

        $building->delete();

        ActivityLog::log('building_deleted', "Building '{$name}' deleted");

        return redirect()
            ->route('buildings.index')
            ->with('success', 'Building deleted successfully.');
    }
}
