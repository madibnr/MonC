<?php

namespace App\Console\Commands;

use App\Jobs\RecordCameraSegmentJob;
use App\Models\Camera;
use App\Services\RecordingService;
use Illuminate\Console\Command;

class RecordCameras extends Command
{
    protected $signature = 'recording:run {--camera= : Record specific camera ID} {--cleanup : Run cleanup only}';
    protected $description = 'Dispatch recording jobs for all active cameras';

    public function handle(RecordingService $service): int
    {
        if ($this->option('cleanup')) {
            $stale = $service->cleanupStaleRecordings();
            $old = $service->cleanupOldSegments((int) config('monc.recording.retention_days', 30));
            $this->info("Cleanup: {$stale} stale, {$old} old segments removed.");
            return 0;
        }

        if ($cameraId = $this->option('camera')) {
            $camera = Camera::find($cameraId);
            if (! $camera) {
                $this->error("Camera {$cameraId} not found.");
                return 1;
            }
            RecordCameraSegmentJob::dispatch($camera->id);
            $this->info("Recording job dispatched for camera: {$camera->name}");
            return 0;
        }

        // Dispatch for all active online cameras
        $cameras = Camera::active()->online()->get();
        $count = 0;

        foreach ($cameras as $camera) {
            RecordCameraSegmentJob::dispatch($camera->id)->onQueue('recording');
            $count++;
        }

        $this->info("Dispatched {$count} recording jobs.");
        return 0;
    }
}
