<?php

namespace App\Jobs;

use App\Models\AiCameraSetting;
use App\Services\AiAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessPlateRecognitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function handle(AiAnalyticsService $aiService): void
    {
        // Only process cameras with AI enabled and type = plate_recognition
        $enabledSettings = AiCameraSetting::enabled()
            ->plateRecognition()
            ->with(['camera', 'camera.building', 'camera.nvr'])
            ->get();

        if ($enabledSettings->isEmpty()) {
            Log::debug('ProcessPlateRecognitionJob: No AI-enabled cameras for plate recognition.');

            return;
        }

        // Check if AI service is reachable before processing
        if (! $aiService->isServiceHealthy()) {
            Log::warning('ProcessPlateRecognitionJob: AI microservice is not reachable. Skipping cycle.');

            return;
        }

        $processedCount = 0;
        $detectionCount = 0;

        foreach ($enabledSettings as $setting) {
            $camera = $setting->camera;

            // Skip if camera is not active or online
            if (! $camera || ! $camera->is_active || $camera->status !== 'online') {
                continue;
            }

            // Check detection interval using cache lock
            // This ensures we respect the per-camera interval setting
            $cacheKey = "ai_last_processed_camera_{$camera->id}";
            $lastProcessed = Cache::get($cacheKey);

            if ($lastProcessed) {
                $secondsSinceLastProcess = now()->diffInSeconds($lastProcessed);
                if ($secondsSinceLastProcess < $setting->detection_interval_seconds) {
                    continue; // Skip - not enough time has passed
                }
            }

            try {
                // Mark as processing
                Cache::put($cacheKey, now(), $setting->detection_interval_seconds + 60);

                // Process camera
                $results = $aiService->processCamera($camera);
                $processedCount++;
                $detectionCount += count($results);

                if (! empty($results)) {
                    Log::info("ProcessPlateRecognitionJob: Camera {$camera->id} ({$camera->name}) detected ".count($results).' plate(s).');
                }
            } catch (\Exception $e) {
                Log::error("ProcessPlateRecognitionJob: Error processing camera {$camera->id}", [
                    'error' => $e->getMessage(),
                    'camera_name' => $camera->name,
                ]);
            }
        }

        if ($processedCount > 0) {
            Log::info("ProcessPlateRecognitionJob: Processed {$processedCount} cameras, found {$detectionCount} plates.");
        }
    }
}
