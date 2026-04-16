<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Snapshot;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SnapshotService
{
    protected string $ffmpegPath;

    protected string $outputBasePath;

    public function __construct()
    {
        $this->ffmpegPath = config('monc.ffmpeg_path', 'ffmpeg');
        $this->outputBasePath = storage_path('app/public/snapshots');
    }

    /**
     * Capture a snapshot from a camera's live stream.
     */
    public function capture(Camera $camera, int $userId, ?string $notes = null): ?Snapshot
    {
        $camera->load('nvr', 'building');

        if (! $camera->nvr) {
            Log::error("Snapshot: NVR not found for camera {$camera->id}");

            return null;
        }

        // Create output directory
        $dateDir = now()->format('Y-m-d');
        $outputDir = $this->outputBasePath."/{$dateDir}";
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Generate filename
        $fileName = sprintf(
            'snap_%s_ch%d_%s.jpg',
            $camera->building->code ?? 'CAM',
            $camera->channel_no,
            now()->format('Ymd_His')
        );
        $outputPath = $outputDir.'/'.$fileName;

        // Use sub-stream URL for snapshot
        $streamUrl = $camera->getSubStreamUrl();

        // FFmpeg command to capture single frame
        $command = [
            $this->ffmpegPath,
            '-rtsp_transport', 'tcp',
            '-i', $streamUrl,
            '-frames:v', '1',
            '-q:v', '2',
            '-y',
            $outputPath,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(15); // 15 second timeout
            $process->run();

            if ($process->isSuccessful() && file_exists($outputPath)) {
                $fileSize = filesize($outputPath);
                $relativePath = "snapshots/{$dateDir}/{$fileName}";

                $snapshot = Snapshot::create([
                    'user_id' => $userId,
                    'camera_id' => $camera->id,
                    'file_path' => $relativePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'mime_type' => 'image/jpeg',
                    'notes' => $notes,
                ]);

                Log::info("Snapshot captured: {$fileName} for camera {$camera->name}");

                return $snapshot;
            } else {
                $error = $process->getErrorOutput();
                Log::error("Snapshot failed for camera {$camera->id}: {$error}");

                return null;
            }
        } catch (\Exception $e) {
            Log::error("Snapshot exception for camera {$camera->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Capture snapshot from an active HLS stream (already running).
     * This is faster since the stream is already active.
     */
    public function captureFromHls(Camera $camera, int $userId, ?string $notes = null): ?Snapshot
    {
        $hlsPath = storage_path("app/public/streams/camera_{$camera->id}/index.m3u8");

        if (! file_exists($hlsPath)) {
            // Fall back to RTSP capture
            return $this->capture($camera, $userId, $notes);
        }

        $camera->load('building');

        $dateDir = now()->format('Y-m-d');
        $outputDir = $this->outputBasePath."/{$dateDir}";
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $fileName = sprintf(
            'snap_%s_ch%d_%s.jpg',
            $camera->building->code ?? 'CAM',
            $camera->channel_no,
            now()->format('Ymd_His')
        );
        $outputPath = $outputDir.'/'.$fileName;

        $command = [
            $this->ffmpegPath,
            '-i', $hlsPath,
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
                $fileSize = filesize($outputPath);
                $relativePath = "snapshots/{$dateDir}/{$fileName}";

                $snapshot = Snapshot::create([
                    'user_id' => $userId,
                    'camera_id' => $camera->id,
                    'file_path' => $relativePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'mime_type' => 'image/jpeg',
                    'notes' => $notes,
                ]);

                Log::info("HLS Snapshot captured: {$fileName} for camera {$camera->name}");

                return $snapshot;
            }
        } catch (\Exception $e) {
            Log::error("HLS Snapshot exception for camera {$camera->id}: {$e->getMessage()}");
        }

        // Fall back to RTSP
        return $this->capture($camera, $userId, $notes);
    }

    /**
     * Delete a snapshot and its file.
     */
    public function deleteSnapshot(Snapshot $snapshot): bool
    {
        $fullPath = storage_path('app/public/'.$snapshot->file_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $snapshot->delete();

        return true;
    }
}
