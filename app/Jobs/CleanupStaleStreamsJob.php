<?php

namespace App\Jobs;

use App\Services\Go2rtcStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupStaleStreamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function handle(Go2rtcStreamService $service): void
    {
        $service->cleanupStaleSessions();
        Log::info('CleanupStaleStreamsJob: Stale streams cleaned up.');
    }
}
