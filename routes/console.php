<?php

use App\Jobs\CheckCameraStatusJob;
use App\Jobs\CheckNvrHealthJob;
use App\Jobs\CleanupStaleStreamsJob;
use App\Jobs\ProcessPlateRecognitionJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes / Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Ensure go2rtc is running (check every minute)
Schedule::command('go2rtc:start')->everyMinute()->withoutOverlapping();

Schedule::job(new CleanupStaleStreamsJob)->everyFiveMinutes();
Schedule::job(new CheckCameraStatusJob)->everyTenMinutes();
Schedule::job(new CheckNvrHealthJob)->everyThirtyMinutes();

/*
|--------------------------------------------------------------------------
| AI Analytics Scheduled Tasks
|--------------------------------------------------------------------------
| ProcessPlateRecognitionJob runs frequently but only processes cameras
| where ai_enabled = true. Per-camera intervals are enforced via cache
| locks inside the job itself.
|--------------------------------------------------------------------------
*/
Schedule::job(new ProcessPlateRecognitionJob)
    ->everyFiveSeconds()
    ->withoutOverlapping();
