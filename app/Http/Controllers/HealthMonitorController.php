<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Camera;
use App\Models\Nvr;
use App\Models\NvrHealthLog;
use App\Services\NvrHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HealthMonitorController extends Controller
{
    public function __construct(
        protected NvrHealthService $healthService
    ) {}

    /**
     * Camera health monitoring dashboard.
     */
    public function index(): View
    {
        // Overall stats
        $totalCameras = Camera::active()->count();
        $onlineCameras = Camera::active()->online()->count();
        $offlineCameras = Camera::active()->where('status', 'offline')->count();
        $maintenanceCameras = Camera::active()->where('status', 'maintenance')->count();

        $totalNvrs = Nvr::active()->count();
        $onlineNvrs = Nvr::active()->online()->count();
        $offlineNvrs = Nvr::active()->where('status', 'offline')->count();

        // Per-building stats
        $buildings = Building::with(['nvrs' => function ($q) {
            $q->active()->withCount([
                'cameras as total_cameras' => fn ($q) => $q->active(),
                'cameras as online_cameras' => fn ($q) => $q->active()->online(),
                'cameras as offline_cameras' => fn ($q) => $q->active()->where('status', 'offline'),
            ]);
        }])->active()->get();

        $buildingStats = $buildings->map(function ($building) {
            $total = $building->nvrs->sum('total_cameras');
            $online = $building->nvrs->sum('online_cameras');
            $offline = $building->nvrs->sum('offline_cameras');

            return [
                'id' => $building->id,
                'name' => $building->name,
                'code' => $building->code,
                'total_cameras' => $total,
                'online_cameras' => $online,
                'offline_cameras' => $offline,
                'nvr_count' => $building->nvrs->count(),
                'nvrs_online' => $building->nvrs->where('status', 'online')->count(),
            ];
        });

        // Latest NVR health data
        $nvrHealthData = NvrHealthLog::latestPerNvr()
            ->with('nvr.building')
            ->get()
            ->keyBy('nvr_id');

        $nvrs = Nvr::active()->with('building')->get();

        return view('health.index', compact(
            'totalCameras', 'onlineCameras', 'offlineCameras', 'maintenanceCameras',
            'totalNvrs', 'onlineNvrs', 'offlineNvrs',
            'buildingStats', 'nvrHealthData', 'nvrs'
        ));
    }

    /**
     * NVR storage monitoring page.
     */
    public function storage(): View
    {
        $nvrs = Nvr::active()->with(['building', 'latestHealth'])->get();

        return view('health.storage', compact('nvrs'));
    }

    /**
     * Trigger manual health check for an NVR.
     */
    public function checkNvr(Nvr $nvr): JsonResponse
    {
        $log = $this->healthService->checkHealth($nvr);

        return response()->json([
            'success' => true,
            'nvr_id' => $nvr->id,
            'status' => $log->overall_status,
            'hdd_usage' => $log->hdd_usage_percent,
            'is_recording' => $log->is_recording,
            'recording_channels' => $log->recording_channels,
            'bandwidth' => $log->getBandwidthFormatted(),
        ]);
    }

    /**
     * Get health data for AJAX refresh.
     */
    public function healthData(): JsonResponse
    {
        $totalCameras = Camera::active()->count();
        $onlineCameras = Camera::active()->online()->count();
        $offlineCameras = Camera::active()->where('status', 'offline')->count();

        $buildings = Building::with(['cameras' => fn ($q) => $q->active()])->active()->get();

        $buildingData = $buildings->map(fn ($b) => [
            'name' => $b->name,
            'total' => $b->cameras->count(),
            'online' => $b->cameras->where('status', 'online')->count(),
            'offline' => $b->cameras->where('status', 'offline')->count(),
        ]);

        return response()->json([
            'total_cameras' => $totalCameras,
            'online_cameras' => $onlineCameras,
            'offline_cameras' => $offlineCameras,
            'buildings' => $buildingData,
        ]);
    }
}
