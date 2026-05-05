<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\RecordingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecordCameraSegmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120; // segment duration + buffer

    public function __construct(
        protected int $cameraId
    ) {}

    public function handle(RecordingService $service): void
    {
        $camera = Camera::with('nvr')->find($this->cameraId);
        if (! $camera || ! $camera->is_active || $camera->status === 'maintenance') {
            return;
        }

        // Prevent overlapping recordings for the same camera
        $lockKey = "recording_camera_{$this->cameraId}";
        if (! Cache::lock($lockKey, 90)->get()) {
            return;
        }

        try {
            $service->recordSegment($camera);
        } finally {
            Cache::lock($lockKey)->forceRelease();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("RecordCameraSegmentJob failed for camera {$this->cameraId}: {$exception->getMessage()}");
    }
}
