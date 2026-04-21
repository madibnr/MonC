<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\StreamSession;
use Illuminate\Support\Facades\Log;

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
     *
     * @param  string  $streamType  'sub' for sub-stream (default), 'main' for main stream
     */
    public function startStream(Camera $camera, ?int $userId = null, string $streamType = 'sub'): ?StreamSession
    {
        // Check if stream is already active with the same type
        $existingSession = StreamSession::where('camera_id', $camera->id)
            ->where('status', 'active')
            ->first();

        if ($existingSession && $this->isProcessRunning($existingSession->pid)) {
            // If requesting different stream type, stop existing and start new
            $existingType = $existingSession->stream_path;
            $expectedPath = $streamType === 'main'
                ? "streams/camera_{$camera->id}_main/index.m3u8"
                : "streams/camera_{$camera->id}/index.m3u8";

            if ($existingType === $expectedPath) {
                return $existingSession;
            }

            // Stop existing stream to switch type
            $this->stopStream($camera->id);
        }

        // Clean up any stale sessions for this camera
        $this->cleanupStaleSessions($camera->id);

        // Create output directory (separate dirs for main vs sub)
        $dirSuffix = $streamType === 'main' ? '_main' : '';
        $outputDir = $this->outputBasePath."/camera_{$camera->id}{$dirSuffix}";
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir.'/index.m3u8';
        $streamUrl = $streamType === 'main'
            ? $camera->getMainStreamUrl()
            : $camera->getSubStreamUrl();

        if (! $streamUrl) {
            Log::error("No stream URL available for camera {$camera->id} ({$streamType})");

            return null;
        }

        // Build FFmpeg command line string
        $cmdLine = '"'.$this->ffmpegPath.'"'
            .' -rtsp_transport tcp'
            .' -i "'.$streamUrl.'"'
            .' -c:v copy'
            .' -tag:v hvc1'
            .' -c:a aac'
            .' -f hls'
            .' -hls_time 2'
            .' -hls_list_size 10'
            .' -hls_flags delete_segments+append_list'
            .' -hls_allow_cache 0'
            .' -hls_segment_filename "'.$outputDir.'/segment_%03d.ts"'
            .' -y'
            .' "'.$outputPath.'"';

        // Create session record
        $streamPath = $streamType === 'main'
            ? "streams/camera_{$camera->id}_main/index.m3u8"
            : "streams/camera_{$camera->id}/index.m3u8";

        $session = StreamSession::create([
            'camera_id' => $camera->id,
            'user_id' => $userId,
            'stream_path' => $streamPath,
            'status' => 'starting',
            'started_at' => now(),
        ]);

        try {
            // Start FFmpeg as a detached background process
            $pid = $this->startBackgroundProcess($cmdLine);

            if ($pid) {
                $session->update([
                    'pid' => $pid,
                    'status' => 'active',
                ]);

                Log::info("Stream started for camera {$camera->id}, PID: {$pid}");

                return $session;
            } else {
                $session->update([
                    'status' => 'error',
                    'error_message' => 'Failed to spawn FFmpeg process',
                    'stopped_at' => now(),
                ]);

                Log::error("Failed to spawn FFmpeg process for camera {$camera->id}");

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
     * Start a process in the background (fully detached from PHP).
     * Returns the PID on success, or null on failure.
     */
    protected function startBackgroundProcess(string $cmdLine): ?int
    {
        $logFile = storage_path('logs/ffmpeg_stream.log');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows: use proc_open with start /B and create_process_group
            $descriptors = [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', 'NUL', 'w'],
                2 => ['file', $logFile, 'w'],
            ];

            $options = [
                'create_process_group' => true,
                'create_new_console' => false,
                'suppress_errors' => true,
            ];

            $process = proc_open('start "" /B '.$cmdLine, $descriptors, $pipes, null, null, $options);

            if (is_resource($process)) {
                // Wait for FFmpeg to actually start
                sleep(3);

                // Find the FFmpeg PID via tasklist
                $pid = $this->findFfmpegPid();

                // Close the proc resource (does NOT kill detached process)
                proc_close($process);

                return $pid;
            }

            return null;
        }

        // On Linux/Mac, use nohup
        $fullCmd = "nohup {$cmdLine} > /dev/null 2>&1 & echo $!";
        $output = [];
        exec($fullCmd, $output);
        $pid = (int) ($output[0] ?? 0);

        return $pid > 0 ? $pid : null;
    }

    /**
     * Find the PID of a running ffmpeg.exe process.
     */
    protected function findFfmpegPid(): ?int
    {
        $output = [];
        exec('tasklist /FI "IMAGENAME eq ffmpeg.exe" /FO CSV /NH 2>NUL', $output);

        foreach ($output as $line) {
            if (preg_match('/"ffmpeg\.exe","(\d+)"/', $line, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
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
     * Clean up HLS files for a camera (both sub and main stream dirs)
     */
    protected function cleanupStreamFiles(int $cameraId): void
    {
        $dirs = [
            $this->outputBasePath."/camera_{$cameraId}",
            $this->outputBasePath."/camera_{$cameraId}_main",
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir.'/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
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
