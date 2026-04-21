<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\Go2rtcStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        protected int $cameraId,
        protected ?int $userId = null
    ) {}

    public function handle(Go2rtcStreamService $service): void
    {
        $camera = Camera::find($this->cameraId);

        if (! $camera || ! $camera->is_active) {
            Log::warning("StartStreamJob: Camera {$this->cameraId} not found or inactive.");

            return;
        }

        $session = $service->startStream($camera, $this->userId);

        if ($session) {
            Log::info("StartStreamJob: Stream started for camera {$this->cameraId}, session {$session->id}");
        } else {
            Log::error("StartStreamJob: Failed to start stream for camera {$this->cameraId}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("StartStreamJob failed for camera {$this->cameraId}: {$exception->getMessage()}");
    }
}
