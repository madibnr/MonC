<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\StreamSession;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FFmpegStreamService
{
    protected string $outputBasePath;

    protected string $ffmpegPath;

    public function __construct()
    {
        $this->outputBasePath = storage_path('app/public/streams');
        $this->ffmpegPath = config('monc.ffmpeg_path', 'ffmpeg');
    }

    /**
     * Start HLS stream for a camera
     */
    public function startStream(Camera $camera, ?int $userId = null): ?StreamSession
    {
        // Check if stream is already active
        $existingSession = StreamSession::where('camera_id', $camera->id)
            ->where('status', 'active')
            ->first();

        if ($existingSession && $this->isProcessRunning($existingSession->pid)) {
            return $existingSession;
        }

        // Clean up any stale sessions for this camera
        $this->cleanupStaleSessions($camera->id);

        // Create output directory
        $outputDir = $this->getOutputDir($camera->id);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir.'/index.m3u8';
        $streamUrl = $camera->getSubStreamUrl();

        if (! $streamUrl) {
            Log::error("No stream URL available for camera {$camera->id}");

            return null;
        }

        // Build FFmpeg command
        $command = [
            $this->ffmpegPath,
            '-rtsp_transport', 'tcp',
            '-i', $streamUrl,
            '-c:v', 'copy',
            '-c:a', 'aac',
            '-f', 'hls',
            '-hls_time', '2',
            '-hls_list_size', '10',
            '-hls_flags', 'delete_segments+append_list',
            '-hls_allow_cache', '0',
            '-hls_segment_filename', $outputDir.'/segment_%03d.ts',
            '-y',
            $outputPath,
        ];

        // Create session record
        $session = StreamSession::create([
            'camera_id' => $camera->id,
            'user_id' => $userId,
            'stream_path' => "streams/camera_{$camera->id}/index.m3u8",
            'status' => 'starting',
            'started_at' => now(),
        ]);

        try {
            // Start FFmpeg process
            $process = new Process($command);
            $process->setOptions(['create_new_console' => true]);
            $process->setTimeout(null);
            $process->start();

            // Wait briefly to check if process started successfully
            usleep(500000); // 0.5 seconds

            if ($process->isRunning()) {
                $session->update([
                    'pid' => $process->getPid(),
                    'status' => 'active',
                ]);

                Log::info("Stream started for camera {$camera->id}, PID: {$process->getPid()}");

                return $session;
            } else {
                $errorOutput = $process->getErrorOutput();
                $session->update([
                    'status' => 'error',
                    'error_message' => $errorOutput,
                    'stopped_at' => now(),
                ]);

                Log::error("Failed to start stream for camera {$camera->id}: {$errorOutput}");

                return null;
            }
        } catch (\Exception $e) {
            $session->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'stopped_at' => now(),
            ]);

            Log::error("Exception starting stream for camera {$camera->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Stop HLS stream for a camera
     */
    public function stopStream(int $cameraId): bool
    {
        $sessions = StreamSession::where('camera_id', $cameraId)
            ->whereIn('status', ['active', 'starting'])
            ->get();

        foreach ($sessions as $session) {
            $this->killProcess($session->pid);
            $session->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
        }

        // Clean up HLS files
        $this->cleanupStreamFiles($cameraId);

        return true;
    }

    /**
     * Check if a stream is currently active for a camera
     */
    public function isStreamActive(int $cameraId): bool
    {
        $session = StreamSession::where('camera_id', $cameraId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (! $session) {
            return false;
        }

        if (! $this->isProcessRunning($session->pid)) {
            $session->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get the HLS stream URL for a camera
     */
    public function getStreamUrl(int $cameraId): ?string
    {
        if ($this->isStreamActive($cameraId)) {
            return asset("storage/streams/camera_{$cameraId}/index.m3u8");
        }

        return null;
    }

    /**
     * Get output directory for a camera stream
     */
    protected function getOutputDir(int $cameraId): string
    {
        return $this->outputBasePath."/camera_{$cameraId}";
    }

    /**
     * Check if a process is running by PID
     */
    protected function isProcessRunning(?int $pid): bool
    {
        if (! $pid) {
            return false;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            exec("tasklist /FI \"PID eq {$pid}\" 2>NUL", $output);
            foreach ($output as $line) {
                if (strpos($line, (string) $pid) !== false) {
                    return true;
                }
            }

            return false;
        }

        return posix_kill($pid, 0);
    }

    /**
     * Kill a process by PID
     */
    protected function killProcess(?int $pid): bool
    {
        if (! $pid) {
            return false;
        }

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /PID {$pid} /F 2>NUL");
            } else {
                posix_kill($pid, 15); // SIGTERM
                usleep(100000);
                if ($this->isProcessRunning($pid)) {
                    posix_kill($pid, 9); // SIGKILL
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to kill process {$pid}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Clean up stale stream sessions for a camera
     */
    public function cleanupStaleSessions(?int $cameraId = null): void
    {
        $query = StreamSession::whereIn('status', ['active', 'starting']);

        if ($cameraId) {
            $query->where('camera_id', $cameraId);
        }

        $sessions = $query->get();

        foreach ($sessions as $session) {
            if (! $this->isProcessRunning($session->pid)) {
                $session->update([
                    'status' => 'stopped',
                    'stopped_at' => now(),
                ]);
            }
        }
    }

    /**
     * Clean up HLS files for a camera
     */
    protected function cleanupStreamFiles(int $cameraId): void
    {
        $dir = $this->getOutputDir($cameraId);
        if (is_dir($dir)) {
            $files = glob($dir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Stop all active streams
     */
    public function stopAllStreams(): void
    {
        $sessions = StreamSession::whereIn('status', ['active', 'starting'])->get();

        foreach ($sessions as $session) {
            $this->killProcess($session->pid);
            $session->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
            $this->cleanupStreamFiles($session->camera_id);
        }
    }
}
