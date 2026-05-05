<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Camera;
use App\Models\PlateDetectionLog;
use Illuminate\Http\Request;

class PlateDetectionController extends Controller
{
    /**
     * Display plate detection logs with filters.
     */
    public function index(Request $request)
    {
        $query = PlateDetectionLog::with(['camera', 'camera.building'])
            ->orderByDesc('detected_at');

        // Filter by camera
        if ($request->filled('camera_id')) {
            $query->where('camera_id', $request->camera_id);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->whereHas('camera', function ($q) use ($request) {
                $q->where('building_id', $request->building_id);
            });
        }

        // Filter by plate number
        if ($request->filled('plate_number')) {
            $normalized = PlateDetectionLog::normalizePlate($request->plate_number);
            $query->where('plate_number_normalized', 'like', "%{$normalized}%");
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('detected_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('detected_at', '<=', $request->date_to.' 23:59:59');
        }

        // Filter by confidence
        if ($request->filled('min_confidence')) {
            $query->where('confidence', '>=', $request->min_confidence);
        }

        // Filter by direction
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        $detections = $query->paginate(25)->withQueryString();

        $buildings = Building::active()->orderBy('name')->get();
        $cameras = Camera::active()
            ->whereHas('aiSetting', fn ($q) => $q->where('ai_enabled', true))
            ->orderBy('name')
            ->get();

        // Stats
        $todayCount = PlateDetectionLog::today()->count();
        $uniquePlates = PlateDetectionLog::today()->distinct('plate_number_normalized')->count('plate_number_normalized');

        return view('ai.detections.index', compact(
            'detections', 'buildings', 'cameras', 'todayCount', 'uniquePlates'
        ));
    }

    /**
     * Show detail of a single detection.
     */
    public function show(PlateDetectionLog $detection)
    {
        $detection->load(['camera', 'camera.building', 'incidents']);

        // Find other detections of the same plate
        $relatedDetections = PlateDetectionLog::where('plate_number_normalized', $detection->plate_number_normalized)
            ->where('id', '!=', $detection->id)
            ->orderByDesc('detected_at')
            ->limit(10)
            ->with('camera')
            ->get();

        $isOnWatchlist = $detection->isOnWatchlist();
        $watchlistEntry = $detection->getWatchlistMatch();

        return view('ai.detections.show', compact(
            'detection', 'relatedDetections', 'isOnWatchlist', 'watchlistEntry'
        ));
    }
}
