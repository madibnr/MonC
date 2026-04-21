<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\StreamSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Go2rtcStreamService
{
    protected string $apiUrl;

    protected string $binaryPath;

    protected string $configPath;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('monc.go2rtc.api_url', 'http://127.0.0.1:1984'), '/');
        $this->binaryPath = config('monc.go2rtc.binary_path', base_path('bin/go2rtc.exe'));
        $this->configPath = config('monc.go2rtc.config_path', base_path('bin/go2rtc.yaml'));
    }

    // ── Process Management ──────────────────────────────────────────

    /**
     * Ensure go2rtc process is running. Auto-starts if not.
     */
    public function ensureRunning(): bool
    {
        if ($this->isGo2rtcRunning()) {
            return true;
        }

        return $this->startGo2rtc();
    }

    /**
     * Check if go2rtc API is reachable.
     */
    public function isGo2rtcRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->apiUrl}/api");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Start go2rtc as a background process.
     */
    public function startGo2rtc(): bool
    {
        if (! file_exists($this->binaryPath)) {
            Log::error("go2rtc binary not found at: {$this->binaryPath}");

            return false;
        }

        try {
            $logFile = storage_path('logs/go2rtc.log');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = '"'.$this->binaryPath.'" -config "'.$this->configPath.'"';
                $descriptors = [
                    0 => ['file', 'NUL', 'r'],
                    1 => ['file', $logFile, 'a'],
                    2 => ['file', $logFile, 'a'],
                ];
                $options = [
                    'create_process_group' => true,
                    'create_new_console' => false,
                    'suppress_errors' => true,
                ];

                $process = proc_open('start "" /B '.$cmd, $descriptors, $pipes, null, null, $options);

                if (is_resource($process)) {
                    proc_close($process);
                }
            } else {
                $cmd = "nohup \"{$this->binaryPath}\" -config \"{$this->configPath}\" >> \"{$logFile}\" 2>&1 &";
                exec($cmd);
            }

            // Wait for go2rtc to be ready
            $maxWait = 10;
            for ($i = 0; $i < $maxWait; $i++) {
                sleep(1);
                if ($this->isGo2rtcRunning()) {
                    Log::info('go2rtc started successfully');

                    return true;
                }
            }

            Log::error('go2rtc failed to start within timeout');

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to start go2rtc: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Stop go2rtc process.
     */
    public function stopGo2rtc(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('taskkill /IM go2rtc.exe /F 2>NUL');
        } else {
            exec('pkill -f go2rtc');
        }

        Log::info('go2rtc process stopped');
    }

    // ── Stream Management ───────────────────────────────────────────

    /**
     * Start a stream for a camera via go2rtc API.
     */
    public function startStream(Camera $camera, ?int $userId = null, string $streamType = 'sub'): ?StreamSession
    {
        if (! $this->ensureRunning()) {
            Log::error("go2rtc is not running, cannot start stream for camera {$camera->id}");

            return null;
        }

        // Check for existing active session
        $existingSession = StreamSession::where('camera_id', $camera->id)
            ->where('status', 'active')
            ->first();

        if ($existingSession) {
            $expectedName = $this->getStreamName($camera->id, $streamType);
            if ($existingSession->stream_path === $expectedName) {
                return $existingSession;
            }
            // Different stream type requested, stop existing
            $this->stopStream($camera->id);
        }

        $this->cleanupStaleSessions($camera->id);

        $streamName = $this->getStreamName($camera->id, $streamType);
        $rtspUrl = $streamType === 'main'
            ? $camera->getMainStreamUrl()
            : $camera->getSubStreamUrl();

        if (! $rtspUrl) {
            Log::error("No RTSP URL available for camera {$camera->id} ({$streamType})");

            return null;
        }

        // Create session record
        $session = StreamSession::create([
            'camera_id' => $camera->id,
            'user_id' => $userId,
            'stream_path' => $streamName,
            'status' => 'starting',
            'started_at' => now(),
        ]);

        try {
            // go2rtc v1.9 API: PUT /api/streams?name=NAME&src=RTSP_URL
            // The src must be in the query parameter, not in the body.
            $encodedSrc = urlencode($rtspUrl);
            $putUrl = "{$this->apiUrl}/api/streams?name={$streamName}&src={$encodedSrc}";

            // Use native curl because go2rtc may return 400 but still register the stream
            $ch = curl_init($putUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Verify the stream was registered (go2rtc may return 400 but still succeed)
            sleep(1);
            $checkResponse = Http::timeout(5)->get("{$this->apiUrl}/api/streams");
            $streams = $checkResponse->json() ?? [];

            if (! isset($streams[$streamName])) {
                Log::error("Stream {$streamName} not found in go2rtc after PUT (HTTP {$httpCode})");

                $session->update([
                    'status' => 'error',
                    'error_message' => "Stream not registered in go2rtc (HTTP {$httpCode})",
                    'stopped_at' => now(),
                ]);

                return null;
            }

            $session->update([
                'status' => 'active',
                'pid' => null, // go2rtc manages its own processes
            ]);

            Log::info("Stream started via go2rtc for camera {$camera->id}: {$streamName}");

            return $session;
        } catch (\Exception $e) {
            $session->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'stopped_at' => now(),
            ]);

            Log::error("Failed to start go2rtc stream for camera {$camera->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Stop a stream for a camera.
     */
    public function stopStream(int $cameraId): bool
    {
        $sessions = StreamSession::where('camera_id', $cameraId)
            ->whereIn('status', ['active', 'starting'])
            ->get();

        foreach ($sessions as $session) {
            try {
                // go2rtc v1.9: DELETE /api/streams?name=NAME
                Http::timeout(5)->delete("{$this->apiUrl}/api/streams?name={$session->stream_path}");
            } catch (\Exception $e) {
                Log::warning("Failed to remove go2rtc stream {$session->stream_path}: {$e->getMessage()}");
            }

            $session->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Check if a stream is currently active.
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

        // Verify with go2rtc API
        try {
            $response = Http::timeout(3)->get("{$this->apiUrl}/api/streams");

            if ($response->successful()) {
                $streams = $response->json() ?? [];
                if (isset($streams[$session->stream_path])) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // go2rtc not reachable
        }

        $session->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);

        return false;
    }

    /**
     * Get the stream URL for a camera (MSE WebSocket URL).
     */
    public function getStreamUrl(int $cameraId): ?string
    {
        if ($this->isStreamActive($cameraId)) {
            $streamName = $this->getStreamName($cameraId);

            return "{$this->apiUrl}/api/ws?src={$streamName}";
        }

        return null;
    }

    // ── URL Builders ────────────────────────────────────────────────

    /**
     * Get the MSE (Media Source Extension) WebSocket URL for a stream.
     * This provides low-latency playback via WebSocket.
     */
    public function getMseWsUrl(string $streamName): string
    {
        $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $this->apiUrl);

        return "{$wsUrl}/api/ws?src={$streamName}";
    }

    /**
     * Get the WebRTC API URL for a stream.
     * This provides the lowest latency via peer-to-peer connection.
     */
    public function getWebRtcUrl(string $streamName): string
    {
        return "{$this->apiUrl}/api/webrtc?src={$streamName}";
    }

    /**
     * Get the go2rtc API base URL (for frontend to connect directly).
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Generate a consistent stream name for a camera.
     */
    protected function getStreamName(int $cameraId, string $streamType = 'sub'): string
    {
        $suffix = $streamType === 'main' ? '_main' : '';

        return "camera_{$cameraId}{$suffix}";
    }

    /**
     * Clean up stale stream sessions.
     */
    public function cleanupStaleSessions(?int $cameraId = null): void
    {
        $query = StreamSession::whereIn('status', ['active', 'starting']);

        if ($cameraId) {
            $query->where('camera_id', $cameraId);
        }

        $sessions = $query->get();

        foreach ($sessions as $session) {
            $isActive = false;

            try {
                $response = Http::timeout(3)->get("{$this->apiUrl}/api/streams");
                if ($response->successful()) {
                    $streams = $response->json();
                    $isActive = isset($streams[$session->stream_path]);
                }
            } catch (\Exception $e) {
                // go2rtc not reachable, mark all as stopped
            }

            if (! $isActive) {
                $session->update([
                    'status' => 'stopped',
                    'stopped_at' => now(),
                ]);
            }
        }
    }

    /**
     * Stop all active streams.
     */
    public function stopAllStreams(): void
    {
        $sessions = StreamSession::whereIn('status', ['active', 'starting'])->get();

        foreach ($sessions as $session) {
            try {
                Http::timeout(5)->delete("{$this->apiUrl}/api/streams?name={$session->stream_path}");
            } catch (\Exception $e) {
                // Ignore
            }

            $session->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
        }
    }
}
