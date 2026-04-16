<?php

namespace App\Jobs;

use App\Services\FFmpegStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StopStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(
        protected int $cameraId
    ) {}

    public function handle(FFmpegStreamService $service): void
    {
        $service->stopStream($this->cameraId);
        Log::info("StopStreamJob: Stream stopped for camera {$this->cameraId}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("StopStreamJob failed for camera {$this->cameraId}: {$exception->getMessage()}");
    }
}
