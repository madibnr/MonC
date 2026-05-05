<?php

namespace App\Http\Controllers;

use App\Models\AiCameraSetting;
use App\Models\AiIncident;
use App\Models\PlateDetectionLog;
use App\Services\AiAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiReportController extends Controller
{
    /**
     * Display AI Camera Summary report.
     */
    public function index(Request $request, AiAnalyticsService $aiService)
    {
        // Total AI cameras
        $totalAiCameras = AiCameraSetting::enabled()->count();
        $totalCamerasWithSettings = AiCameraSetting::count();

        // Active detections today
        $detectionsToday = PlateDetectionLog::today()->count();
        $uniquePlatesToday = PlateDetectionLog::today()
            ->distinct('plate_number_normalized')
            ->count('plate_number_normalized');

        // Last detection time
        $lastDetection = PlateDetectionLog::orderByDesc('detected_at')->first();

        // Most detected plate today
        $mostDetectedPlate = PlateDetectionLog::today()
            ->select('plate_number', 'plate_number_normalized', DB::raw('COUNT(*) as detection_count'))
            ->groupBy('plate_number', 'plate_number_normalized')
            ->orderByDesc('detection_count')
            ->first();

        // Incidents today
        $incidentsToday = AiIncident::today()->count();
        $unacknowledgedIncidents = AiIncident::unacknowledged()->count();

        // Detection by camera (top 10)
        $detectionsByCamera = PlateDetectionLog::today()
            ->select('camera_id', DB::raw('COUNT(*) as detection_count'))
            ->groupBy('camera_id')
            ->orderByDesc('detection_count')
            ->limit(10)
            ->with('camera')
            ->get();

        // Hourly detection chart data (last 24 hours)
        $hourlyDetections = PlateDetectionLog::where('detected_at', '>=', now()->subHours(24))
            ->select(DB::raw('HOUR(detected_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $hourlyChart = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyChart[$i] = $hourlyDetections[$i] ?? 0;
        }

        // Detection by building
        $detectionsByBuilding = PlateDetectionLog::today()
            ->join('cameras', 'plate_detection_logs.camera_id', '=', 'cameras.id')
            ->join('buildings', 'cameras.building_id', '=', 'buildings.id')
            ->select('buildings.name as building_name', DB::raw('COUNT(*) as detection_count'))
            ->groupBy('buildings.name')
            ->orderByDesc('detection_count')
            ->get();

        // AI cameras by type
        $camerasByType = AiCameraSetting::enabled()
            ->select('ai_type', DB::raw('COUNT(*) as count'))
            ->groupBy('ai_type')
            ->pluck('count', 'ai_type')
            ->toArray();

        // Recent detections (last 10)
        $recentDetections = PlateDetectionLog::with(['camera', 'camera.building'])
            ->orderByDesc('detected_at')
            ->limit(10)
            ->get();

        // AI service health
        $aiServiceHealthy = $aiService->isServiceHealthy();

        return view('ai.reports.index', compact(
            'totalAiCameras',
            'totalCamerasWithSettings',
            'detectionsToday',
            'uniquePlatesToday',
            'lastDetection',
            'mostDetectedPlate',
            'incidentsToday',
            'unacknowledgedIncidents',
            'detectionsByCamera',
            'hourlyChart',
            'detectionsByBuilding',
            'camerasByType',
            'recentDetections',
            'aiServiceHealthy'
        ));
    }
}
