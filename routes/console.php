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

// Recording: dispatch segment jobs for all active cameras every 30 seconds
// Only runs if recording is enabled in config
if (config('monc.recording.enabled')) {
    Schedule::command('recording:run')->everyThirtySeconds()->withoutOverlapping();
    Schedule::command('recording:run --cleanup')->dailyAt('03:00');
}

/*
|--------------------------------------------------------------------------
| AI Analytics Scheduled Tasks (DISABLED)
|--------------------------------------------------------------------------
| AI features have been disabled to reduce system load.
| Uncomment the lines below to re-enable plate recognition.
|--------------------------------------------------------------------------
*/
// Schedule::job(new ProcessPlateRecognitionJob)
//     ->everyFiveSeconds()
//     ->withoutOverlapping();
