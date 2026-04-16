<?php

namespace App\Http\Controllers;

use App\Models\AiIncident;
use App\Models\Building;
use Illuminate\Http\Request;

class AiIncidentController extends Controller
{
    /**
     * Display incident timeline with filters.
     */
    public function index(Request $request)
    {
        $query = AiIncident::with(['camera', 'camera.building', 'watchlistPlate', 'acknowledgedByUser'])
            ->orderByDesc('occurred_at');

        // Filter by incident type
        if ($request->filled('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->whereHas('camera', function ($q) use ($request) {
                $q->where('building_id', $request->building_id);
            });
        }

        // Filter by acknowledgment status
        if ($request->filled('status')) {
            if ($request->status === 'unacknowledged') {
                $query->unacknowledged();
            } elseif ($request->status === 'acknowledged') {
                $query->acknowledged();
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to.' 23:59:59');
        }

        // Filter by plate number
        if ($request->filled('plate_number')) {
            $query->where('plate_number', 'like', "%{$request->plate_number}%");
        }

        $incidents = $query->paginate(20)->withQueryString();

        $buildings = Building::active()->orderBy('name')->get();

        $stats = [
            'total_today' => AiIncident::today()->count(),
            'unacknowledged' => AiIncident::unacknowledged()->count(),
            'critical_unack' => AiIncident::unacknowledged()->bySeverity('critical')->count(),
            'high_unack' => AiIncident::unacknowledged()->bySeverity('high')->count(),
        ];

        return view('ai.incidents.index', compact('incidents', 'buildings', 'stats'));
    }

    /**
     * Show incident detail.
     */
    public function show(AiIncident $incident)
    {
        $incident->load([
            'camera',
            'camera.building',
            'plateDetectionLog',
            'watchlistPlate',
            'acknowledgedByUser',
        ]);

        return view('ai.incidents.show', compact('incident'));
    }

    /**
     * Acknowledge an incident.
     */
    public function acknowledge(Request $request, AiIncident $incident)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $incident->update([
            'is_acknowledged' => true,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
            'resolution_notes' => $request->resolution_notes,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Incident acknowledged successfully.',
            ]);
        }

        return back()->with('success', 'Incident acknowledged successfully.');
    }

    /**
     * Bulk acknowledge incidents.
     */
    public function bulkAcknowledge(Request $request)
    {
        $request->validate([
            'incident_ids' => 'required|array',
            'incident_ids.*' => 'exists:ai_incidents,id',
        ]);

        AiIncident::whereIn('id', $request->incident_ids)
            ->where('is_acknowledged', false)
            ->update([
                'is_acknowledged' => true,
                'acknowledged_by' => auth()->id(),
                'acknowledged_at' => now(),
            ]);

        $count = count($request->incident_ids);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} incident(s) acknowledged.",
            ]);
        }

        return back()->with('success', "{$count} incident(s) acknowledged.");
    }
}
