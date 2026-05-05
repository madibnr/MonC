<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\RecordingSegment;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RecordingService
{
    protected string $ffmpegPath;
    protected string $basePath;
    protected int $segmentDuration;

    public function __construct()
    {
        $this->ffmpegPath = config('monc.ffmpeg_path', 'ffmpeg');
        $this->basePath = storage_path('app/recordings');
        $this->segmentDuration = (int) config('monc.recording.segment_duration', 30);
    }

    /**
     * Record a single segment for a camera.
     * Returns the RecordingSegment model on success, null on failure.
     */
    public function recordSegment(Camera $camera): ?RecordingSegment
    {
        $rtspUrl = $camera->getSubStreamUrl();
        if (! $rtspUrl) {
            Log::error("Recording: No RTSP URL for camera {$camera->id}");
            return null;
        }

        $now = now();
        $dir = $this->getSegmentDir($camera->id, $now);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = $now->format('His') . '.mp4';
        $filePath = $dir . '/' . $fileName;
        $relativePath = $this->getRelativePath($camera->id, $now, $fileName);

        // Create DB record first (status = recording)
        $segment = RecordingSegment::create([
            'camera_id'        => $camera->id,
            'start_time'       => $now,
            'file_path'        => $relativePath,
            'status'           => 'recording',
            'type'             => 'continuous',
            'duration_seconds' => $this->segmentDuration,
        ]);

        try {
            $command = [
                $this->ffmpegPath,
                '-rtsp_transport', 'tcp',
                '-i', $rtspUrl,
                '-t', (string) $this->segmentDuration,
                '-c:v', 'copy',
                '-c:a', 'copy',
                '-movflags', '+faststart',
                '-y',
                $filePath,
            ];

            $process = new Process($command);
            $process->setTimeout($this->segmentDuration + 30);
            $process->run();

            if ($process->isSuccessful() && file_exists($filePath) && filesize($filePath) > 1024) {
                $fileSize = filesize($filePath);
                $duration = $this->probeDuration($filePath);

                $segment->update([
                    'status'           => 'completed',
                    'end_time'         => $now->copy()->addSeconds($duration ?: $this->segmentDuration),
                    'duration_seconds' => $duration ?: $this->segmentDuration,
                    'file_size'        => $fileSize,
                ]);

                return $segment;
            }

            // Failed
            $err = substr($process->getErrorOutput(), -500);
            Log::warning("Recording: FFmpeg failed for camera {$camera->id}: {$err}");

            $segment->update(['status' => 'failed']);
            if (file_exists($filePath)) @unlink($filePath);

            return null;
        } catch (\Exception $e) {
            Log::error("Recording: Exception for camera {$camera->id}: {$e->getMessage()}");
            $segment->update(['status' => 'failed']);
            if (file_exists($filePath)) @unlink($filePath);

            return null;
        }
    }

    /**
     * Probe actual duration of an MP4 file using ffprobe.
     */
    protected function probeDuration(string $filePath): ?int
    {
        $ffprobe = config('monc.ffprobe_path', 'ffprobe');
        try {
            $process = new Process([
                $ffprobe, '-v', 'quiet',
                '-show_entries', 'format=duration',
                '-of', 'csv=p=0',
                $filePath,
            ]);
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                return (int) round((float) trim($process->getOutput()));
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }

    /**
     * Clean up old segments beyond retention period.
     */
    public function cleanupOldSegments(int $retentionDays = 30): int
    {
        $cutoff = now()->subDays($retentionDays);

        $segments = RecordingSegment::where('start_time', '<', $cutoff)
            ->where('status', 'completed')
            ->get();

        $count = 0;
        foreach ($segments as $segment) {
            $fullPath = $segment->getFullPath();
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $segment->delete();
            $count++;
        }

        // Also clean up failed/orphaned segments older than 1 day
        RecordingSegment::whereIn('status', ['failed', 'orphaned'])
            ->where('created_at', '<', now()->subDay())
            ->delete();

        return $count;
    }

    /**
     * Mark stale "recording" segments as orphaned.
     */
    public function cleanupStaleRecordings(): int
    {
        // If a segment has been "recording" for more than 5 minutes, it's stale
        return RecordingSegment::where('status', 'recording')
            ->where('created_at', '<', now()->subMinutes(5))
            ->update(['status' => 'orphaned']);
    }

    // ── Path helpers ────────────────────────────────────────────

    protected function getSegmentDir(int $cameraId, $time): string
    {
        return $this->basePath . '/' . $cameraId . '/' . $time->format('Y/m/d');
    }

    protected function getRelativePath(int $cameraId, $time, string $fileName): string
    {
        return 'recordings/' . $cameraId . '/' . $time->format('Y/m/d') . '/' . $fileName;
    }
}
