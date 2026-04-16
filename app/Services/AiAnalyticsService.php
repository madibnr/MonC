<?php

namespace App\Services;

use App\Models\AiCameraSetting;
use App\Models\AiIncident;
use App\Models\Alert;
use App\Models\Camera;
use App\Models\PlateDetectionLog;
use App\Models\WatchlistPlate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class AiAnalyticsService
{
    protected string $aiServiceUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->aiServiceUrl = config('monc.ai.service_url', 'http://127.0.0.1:8100');
        $this->timeout = config('monc.ai.timeout', 30);
    }

    /**
     * Check if AI processing should run for a given camera.
     * CORE RULE: Only cameras with ai_enabled = true are processed.
     */
    public function shouldProcess(Camera $camera): bool
    {
        $setting = $camera->aiSetting;

        if (! $setting) {
            return false;
        }

        return $setting->ai_enabled === true;
    }

    /**
     * Get all cameras that need AI processing, filtered by type.
     * Returns only cameras where ai_enabled = true.
     */
    public function getEnabledCameras(?string $aiType = null): Collection
    {
        return AiCameraSetting::getEnabledCameras($aiType);
    }

    /**
     * Capture a frame from camera stream for AI processing.
     * Uses FFmpeg to grab a single frame from the sub-stream.
     */
    public function captureFrame(Camera $camera): ?string
    {
        $outputDir = storage_path('app/public/ai_frames');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = "frame_camera_{$camera->id}_".now()->format('YmdHis').'.jpg';
        $outputPath = "{$outputDir}/{$filename}";

        $ffmpeg = config('monc.ffmpeg_path', 'ffmpeg');
        $streamUrl = $camera->getSubStreamUrl();

        $command = [
            $ffmpeg,
            '-rtsp_transport', 'tcp',
            '-i', $streamUrl,
            '-frames:v', '1',
            '-q:v', '2',
            '-y',
            $outputPath,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful() && file_exists($outputPath)) {
                return $outputPath;
            }

            Log::warning("AiAnalyticsService: Failed to capture frame for camera {$camera->id}", [
                'error' => $process->getErrorOutput(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("AiAnalyticsService: Frame capture exception for camera {$camera->id}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send frame to Python AI microservice for plate recognition.
     * Returns detection result or null on failure.
     */
    public function recognizePlate(string $imagePath, int $confidenceThreshold = 85): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->aiServiceUrl}/api/recognize-plate", [
                    'confidence_threshold' => $confidenceThreshold,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data['plates'])) {
                    return $data;
                }

                return null;
            }

            Log::warning('AiAnalyticsService: AI service returned non-success', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('AiAnalyticsService: AI service connection failed', [
                'error' => $e->getMessage(),
                'url' => $this->aiServiceUrl,
            ]);

            return null;
        }
    }

    /**
     * Process a single camera: capture frame, send to AI, log results.
     * This is the main entry point called by the job.
     */
    public function processCamera(Camera $camera): array
    {
        $results = [];
        $setting = $camera->aiSetting;

        if (! $setting || ! $setting->ai_enabled) {
            return $results;
        }

        // Capture frame
        $framePath = $this->captureFrame($camera);
        if (! $framePath) {
            return $results;
        }

        // Send to AI service based on type
        if ($setting->ai_type === AiCameraSetting::TYPE_PLATE_RECOGNITION) {
            $aiResult = $this->recognizePlate($framePath, $setting->confidence_threshold);

            if ($aiResult && ! empty($aiResult['plates'])) {
                foreach ($aiResult['plates'] as $plate) {
                    $detection = $this->logDetection($camera, $plate, $framePath, $aiResult);
                    $results[] = $detection;

                    // Check watchlist
                    $this->checkWatchlist($detection);
                }
            }
        }

        // Cleanup frame file after processing (keep snapshot if detection found)
        if (empty($results) && file_exists($framePath)) {
            @unlink($framePath);
        }

        return $results;
    }

    /**
     * Log a plate detection to the database.
     */
    protected function logDetection(Camera $camera, array $plateData, string $framePath, array $rawResponse): PlateDetectionLog
    {
        // Move frame to permanent storage if plate detected
        $snapshotDir = 'public/ai_detections/'.now()->format('Y/m/d');
        $snapshotFilename = "plate_{$camera->id}_".now()->format('His').'_'.uniqid().'.jpg';

        Storage::makeDirectory($snapshotDir);
        $snapshotPath = "{$snapshotDir}/{$snapshotFilename}";

        if (file_exists($framePath)) {
            Storage::put($snapshotPath, file_get_contents($framePath));
            @unlink($framePath);
        }

        $plateNumber = $plateData['plate_number'] ?? $plateData['text'] ?? 'UNKNOWN';

        return PlateDetectionLog::create([
            'camera_id' => $camera->id,
            'plate_number' => $plateNumber,
            'plate_number_normalized' => PlateDetectionLog::normalizePlate($plateNumber),
            'confidence' => $plateData['confidence'] ?? 0,
            'snapshot_path' => $snapshotPath,
            'vehicle_type' => $plateData['vehicle_type'] ?? null,
            'vehicle_color' => $plateData['vehicle_color'] ?? null,
            'direction' => $plateData['direction'] ?? 'unknown',
            'bounding_box' => $plateData['bounding_box'] ?? null,
            'raw_response' => $rawResponse,
            'detected_at' => now(),
        ]);
    }

    /**
     * Check if a detected plate is on the watchlist and create incident if so.
     */
    protected function checkWatchlist(PlateDetectionLog $detection): ?AiIncident
    {
        $watchlistMatch = $detection->getWatchlistMatch();

        if (! $watchlistMatch) {
            return null;
        }

        // Create incident
        $incident = AiIncident::createWatchlistHit($detection, $watchlistMatch);

        // Create system alert
        $this->createWatchlistAlert($detection, $watchlistMatch);

        Log::warning("AiAnalyticsService: Watchlist hit! Plate {$detection->plate_number} on camera {$detection->camera_id}", [
            'watchlist_id' => $watchlistMatch->id,
            'alert_level' => $watchlistMatch->alert_level,
            'incident_id' => $incident->id,
        ]);

        return $incident;
    }

    /**
     * Create a system alert for watchlist hit.
     */
    protected function createWatchlistAlert(PlateDetectionLog $detection, WatchlistPlate $watchlist): void
    {
        try {
            $alert = Alert::create([
                'type' => 'ai_watchlist_hit',
                'severity' => $watchlist->alert_level,
                'title' => "Watchlist Plate Detected: {$detection->plate_number}",
                'message' => "Plate {$detection->plate_number} was detected on camera {$detection->camera?->name}. Reason: {$watchlist->reason}.",
                'source_type' => 'camera',
                'source_id' => $detection->camera_id,
                'metadata' => [
                    'plate_number' => $detection->plate_number,
                    'confidence' => $detection->confidence,
                    'camera_name' => $detection->camera?->name,
                    'watchlist_reason' => $watchlist->reason,
                    'vehicle_owner' => $watchlist->vehicle_owner,
                ],
            ]);

            // Dispatch alert via AlertService if available
            if ($watchlist->notify_telegram) {
                app(AlertService::class)->dispatch($alert);
            }
        } catch (\Exception $e) {
            Log::error('AiAnalyticsService: Failed to create watchlist alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if AI microservice is reachable.
     */
    public function isServiceHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->aiServiceUrl}/api/health");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
