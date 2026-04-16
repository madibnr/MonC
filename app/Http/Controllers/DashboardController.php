<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Models\Nvr;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with system statistics.
     */
    public function index(): View
    {
        $totalCameras = Camera::count();
        $onlineCameras = Camera::online()->count();
        $totalNvrs = Nvr::count();
        $onlineNvrs = Nvr::online()->count();
        $totalBuildings = Building::count();
        $totalUsers = User::count();

        $recentLogs = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.index', compact(
            'totalCameras', 'onlineCameras',
            'totalNvrs', 'onlineNvrs',
            'totalBuildings', 'totalUsers',
            'recentLogs'
        ));
    }
}
