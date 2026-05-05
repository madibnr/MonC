<?php

use App\Http\Controllers\AiCameraController;
use App\Http\Controllers\AiIncidentController;
use App\Http\Controllers\AiReportController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CameraController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthMonitorController;
use App\Http\Controllers\LiveViewController;
use App\Http\Controllers\NvrController;
use App\Http\Controllers\PlateDetectionController;
use App\Http\Controllers\PlaybackController;
use App\Http\Controllers\SettingsController;

use App\Http\Controllers\UserAccessController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Redirect root to dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'auth.monc'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Live Monitoring
    |----------------------------------------------------------------------
    */
    Route::prefix('live')->name('live.')->group(function () {
        Route::get('/', [LiveViewController::class, 'index'])->name('index');
        Route::post('/go2rtc-start', [LiveViewController::class, 'ensureGo2rtc'])->name('go2rtc.start');
        Route::post('/pre-register-streams', [LiveViewController::class, 'preRegisterStreams'])->name('pre-register');
        Route::post('/stream/{camera}', [LiveViewController::class, 'stream'])->name('stream.start');
        Route::delete('/stream/{camera}', [LiveViewController::class, 'stopStream'])->name('stream.stop');
        Route::get('/stream/{camera}/status', [LiveViewController::class, 'streamStatus'])->name('stream.status');
    });

    /*
    |----------------------------------------------------------------------
    | Playback
    |----------------------------------------------------------------------
    */
    Route::prefix('playback')->name('playback.')->group(function () {
        Route::get('/', [PlaybackController::class, 'index'])->name('index');
        Route::post('/play', [PlaybackController::class, 'play'])->name('play');
        Route::delete('/stop', [PlaybackController::class, 'stop'])->name('stop');

        // Segment-based playback API
        Route::get('/api/timeline', [PlaybackController::class, 'timeline'])->name('api.timeline');
        Route::get('/api/segments', [PlaybackController::class, 'segments'])->name('api.segments');
        Route::get('/api/stream', [PlaybackController::class, 'stream'])->name('api.stream');
    });

    /*
    |----------------------------------------------------------------------
    | Camera Management (Admin IT + Superadmin)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin_it'])->group(function () {
        Route::resource('cameras', CameraController::class);
        Route::post('/cameras/{camera}/check-status', [CameraController::class, 'checkStatus'])->name('cameras.check-status');
    });

    /*
    |----------------------------------------------------------------------
    | NVR Management (Admin IT + Superadmin)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin_it'])->group(function () {
        Route::resource('nvrs', NvrController::class);
        Route::post('/nvrs/{nvr}/check-status', [NvrController::class, 'checkStatus'])->name('nvrs.check-status');
    });

    /*
    |----------------------------------------------------------------------
    | Building Management (Admin IT + Superadmin)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin_it'])->group(function () {
        Route::resource('buildings', BuildingController::class);
    });

    /*
    |----------------------------------------------------------------------
    | User Management (Superadmin only)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
    });

    /*
    |----------------------------------------------------------------------
    | User Access / Camera Permissions (Superadmin only)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin'])->prefix('user-access')->name('user-access.')->group(function () {
        Route::get('/', [UserAccessController::class, 'index'])->name('index');
        Route::get('/{user}/edit', [UserAccessController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserAccessController::class, 'update'])->name('update');
        Route::post('/bulk-assign', [UserAccessController::class, 'bulkAssign'])->name('bulk-assign');
    });

    /*
    |----------------------------------------------------------------------
    | Settings
    |----------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password');
    });

    /*
    |----------------------------------------------------------------------
    | Alerts
    |----------------------------------------------------------------------
    */
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::get('/unread-count', [AlertController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [AlertController::class, 'recent'])->name('recent');
        Route::post('/{alert}/read', [AlertController::class, 'markRead'])->name('mark-read');
        Route::post('/mark-all-read', [AlertController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/{alert}/resolve', [AlertController::class, 'resolve'])->name('resolve');
        Route::get('/subscriptions', [AlertController::class, 'subscriptions'])->name('subscriptions');
        Route::put('/subscriptions', [AlertController::class, 'updateSubscriptions'])->name('subscriptions.update');
    });

    /*
    |----------------------------------------------------------------------
    | Health Monitoring (Admin IT + Superadmin)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin_it'])->prefix('health')->name('health.')->group(function () {
        Route::get('/', [HealthMonitorController::class, 'index'])->name('index');
        Route::get('/storage', [HealthMonitorController::class, 'storage'])->name('storage');
        Route::post('/check-nvr/{nvr}', [HealthMonitorController::class, 'checkNvr'])->name('check-nvr');
        Route::get('/data', [HealthMonitorController::class, 'healthData'])->name('data');
    });

    /*
    |----------------------------------------------------------------------
    | Audit Logs (Superadmin + Auditor)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,auditor'])->prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{log}', [AuditLogController::class, 'show'])->name('show');
    });

    /*
    |----------------------------------------------------------------------
    | AI Analytics (Superadmin only) - DISABLED
    |----------------------------------------------------------------------
    */
    /*
    Route::middleware(['role:superadmin'])->prefix('ai')->name('ai.')->group(function () {
        // AI Camera Assignment
        Route::get('/cameras', [AiCameraSettingController::class, 'index'])->name('cameras.index');
        Route::get('/cameras/{camera}/edit', [AiCameraSettingController::class, 'edit'])->name('cameras.edit');
        Route::put('/cameras/{camera}', [AiCameraSettingController::class, 'update'])->name('cameras.update');
        Route::post('/cameras/bulk-update', [AiCameraSettingController::class, 'bulkUpdate'])->name('cameras.bulk-update');
        Route::get('/cameras/{camera}/test', [AiCameraSettingController::class, 'test'])->name('cameras.test');
        Route::post('/cameras/{camera}/test', [AiCameraSettingController::class, 'runTest'])->name('cameras.run-test');

        // Plate Detection Logs
        Route::prefix('detections')->name('detections.')->group(function () {
            Route::get('/', [PlateDetectionController::class, 'index'])->name('index');
            Route::get('/{detection}', [PlateDetectionController::class, 'show'])->name('show');
        });

        // Watchlist
        Route::prefix('watchlist')->name('watchlist.')->group(function () {
            Route::get('/', [WatchlistController::class, 'index'])->name('index');
            Route::get('/create', [WatchlistController::class, 'create'])->name('create');
            Route::post('/', [WatchlistController::class, 'store'])->name('store');
            Route::get('/{watchlist}/edit', [WatchlistController::class, 'edit'])->name('edit');
            Route::put('/{watchlist}', [WatchlistController::class, 'update'])->name('update');
            Route::delete('/{watchlist}', [WatchlistController::class, 'destroy'])->name('destroy');
            Route::patch('/{watchlist}/toggle-active', [WatchlistController::class, 'toggleActive'])->name('toggle-active');
        });

        // AI Incidents
        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [AiIncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [AiIncidentController::class, 'show'])->name('show');
            Route::patch('/{incident}/resolve', [AiIncidentController::class, 'resolve'])->name('resolve');
        });

        // AI Reports
        Route::get('/reports', [AiReportController::class, 'index'])->name('reports.index');
    });
    */

    /*
    |----------------------------------------------------------------------
    | AI Analytics (Superadmin only) - DISABLED (duplicate)
    |----------------------------------------------------------------------
    */
    /*
    Route::middleware(['role:superadmin'])->prefix('ai')->name('ai.')->group(function () {

        // AI Camera Assignment
        Route::prefix('cameras')->name('cameras.')->group(function () {
            Route::get('/', [AiCameraController::class, 'index'])->name('index');
            Route::put('/', [AiCameraController::class, 'update'])->name('update');
            Route::post('/{camera}/toggle', [AiCameraController::class, 'toggle'])->name('toggle');
            Route::put('/{camera}/settings', [AiCameraController::class, 'updateSingle'])->name('update-single');
            Route::get('/health-check', [AiCameraController::class, 'healthCheck'])->name('health-check');
        });

        // Plate Detection Logs
        Route::prefix('detections')->name('detections.')->group(function () {
            Route::get('/', [PlateDetectionController::class, 'index'])->name('index');
            Route::get('/{detection}', [PlateDetectionController::class, 'show'])->name('show');
        });

        // Watchlist
        Route::prefix('watchlist')->name('watchlist.')->group(function () {
            Route::get('/', [WatchlistController::class, 'index'])->name('index');
            Route::get('/create', [WatchlistController::class, 'create'])->name('create');
            Route::post('/', [WatchlistController::class, 'store'])->name('store');
            Route::get('/{watchlist}/edit', [WatchlistController::class, 'edit'])->name('edit');
            Route::put('/{watchlist}', [WatchlistController::class, 'update'])->name('update');
            Route::delete('/{watchlist}', [WatchlistController::class, 'destroy'])->name('destroy');
            Route::patch('/{watchlist}/toggle-active', [WatchlistController::class, 'toggleActive'])->name('toggle-active');
        });

        // Incident Timeline
        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [AiIncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [AiIncidentController::class, 'show'])->name('show');
            Route::post('/{incident}/acknowledge', [AiIncidentController::class, 'acknowledge'])->name('acknowledge');
            Route::post('/bulk-acknowledge', [AiIncidentController::class, 'bulkAcknowledge'])->name('bulk-acknowledge');
        });

        // AI Reports
        Route::get('/reports', [AiReportController::class, 'index'])->name('reports.index');
    });
    */
});
