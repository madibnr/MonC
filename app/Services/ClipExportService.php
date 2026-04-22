<?php

namespace App\Services;

use App\Models\ClipExport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClipExportService
{
    protected string $outputBasePath;

    protected string $go2rtcApiUrl;

    public function __construct()
    {
        $this->outputBasePath = storage_path('app/public/exports');
        $this->go2rtcApiUrl = rtrim(config('monc.go2rtc.api_url', 'http://127.0.0.1:1984'), '/');
    }

    /**
     * Export a clip using go2rtc's built-in MP4 streaming endpoint.
     * No FFmpeg required — go2rtc handles RTSP → MP4 conversion natively.
     */
    public function exportClip(ClipExport $export): bool
    {
        $camera = $export->camera()->with(['nvr', 'building'])->first();

        if (! $camera || ! $camera->nvr) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'Camera or NVR not found.',
            ]);

            return false;
        }

        $nvr = $camera->nvr;

        // ── Build output path ───────────────────────────────────────
        $dateDir = $export->clip_date->format('Y-m-d');
        $outputDir = $this->outputBasePath . "/{$dateDir}";
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $fileName = sprintf(
            'export_%s_ch%d_%s_%s-%s.mp4',
            $camera->building->code ?? 'CAM',
            $camera->channel_no,
            $export->clip_date->format('Ymd'),
            str_replace(':', '', $export->start_time),
            str_replace(':', '', $export->end_time)
        );
        $outputPath = $outputDir . '/' . $fileName;

        // ── Build Hikvision RTSP playback URL ───────────────────────
        $startDt = $export->clip_date->format('Ymd') . 'T' . str_replace(':', '', $export->start_time) . '00Z';
        $endDt   = $export->clip_date->format('Ymd') . 'T' . str_replace(':', '', $export->end_time) . '00Z';

        $rtspUrl = sprintf(
            'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
            $nvr->username,
            $nvr->password,
            $nvr->ip_address,
            $nvr->port ?? 554,
            $camera->channel_no,
            $startDt,
            $endDt
        );

        // ── Register playback stream in go2rtc ──────────────────────
        $streamName = 'export_' . $export->id . '_' . time();

        $export->update(['status' => 'processing', 'progress' => 5]);

        try {
            // 1. Ensure go2rtc is running
            $apiCheck = Http::timeout(3)->get("{$this->go2rtcApiUrl}/api");
            if (! $apiCheck->successful()) {
                throw new \RuntimeException('go2rtc is not running.');
            }

            // 2. Register the RTSP playback URL as a stream
            $putUrl = "{$this->go2rtcApiUrl}/api/streams?name={$streamName}&src=" . urlencode($rtspUrl);
            $ch = curl_init($putUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);

            sleep(2); // give go2rtc time to connect to the RTSP source

            $export->update(['progress' => 10]);

            Log::info("Export #{$export->id}: stream '{$streamName}' registered, starting MP4 download…");

            // 3. Download the MP4 via go2rtc /api/stream.mp4
            //    This endpoint streams an fMP4 until the source ends.
            //    For Hikvision playback URLs the NVR closes the connection
            //    once the requested time range has been fully sent, so the
            //    download will finish automatically.
            $mp4Url = "{$this->go2rtcApiUrl}/api/stream.mp4?src={$streamName}";

            $this->downloadMp4($mp4Url, $outputPath, $export);

            // 4. Verify the file was written
            if (! file_exists($outputPath) || filesize($outputPath) < 1024) {
                throw new \RuntimeException('Downloaded file is empty or too small.');
            }

            $fileSize = filesize($outputPath);
            $relativePath = "exports/{$dateDir}/{$fileName}";

            $export->update([
                'status'       => 'completed',
                'progress'     => 100,
                'file_path'    => $relativePath,
                'file_name'    => $fileName,
                'file_size'    => $fileSize,
                'completed_at' => now(),
                'pid'          => null,
            ]);

            Log::info("Export #{$export->id}: completed — {$fileName} ({$fileSize} bytes)");

            return true;
        } catch (\Exception $e) {
            Log::error("Export #{$export->id} failed: {$e->getMessage()}");

            // Clean up partial file
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $export->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'pid'           => null,
            ]);

            return false;
        } finally {
            // Always clean up the temporary stream from go2rtc
            try {
                Http::timeout(5)->delete("{$this->go2rtcApiUrl}/api/streams?name={$streamName}");
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    /**
     * Stream the MP4 from go2rtc to a local file.
     *
     * go2rtc's /api/stream.mp4 streams indefinitely for live sources.
     * For NVR playback the data stops flowing once the time range ends,
     * but the HTTP connection stays open.  We detect "end of data" by
     * setting CURLOPT_LOW_SPEED_LIMIT: if fewer than 1 KB/s arrives
     * for 15 consecutive seconds we consider the recording finished.
     */
    protected function downloadMp4(string $url, string $outputPath, ClipExport $export): void
    {
        // Calculate expected duration for the overall hard timeout
        $start = strtotime($export->start_time);
        $end   = strtotime($export->end_time);
        $durationSec = max(60, $end - $start);
        $hardTimeout = $durationSec * 3 + 120; // generous hard cap

        $fp = fopen($outputPath, 'wb');
        if (! $fp) {
            throw new \RuntimeException("Cannot open output file: {$outputPath}");
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, $hardTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024);

        // ── Key: detect end-of-playback ─────────────────────────────
        // If the transfer speed drops below 1 KB/s for 15 seconds
        // the NVR has finished sending the requested time range.
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1024);   // bytes/s
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 15);       // seconds

        // Progress callback — update the database periodically
        $lastUpdate = time();
        $startedAt  = time();
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION,
            function ($resource, $dlTotal, $dlNow) use ($export, &$lastUpdate, $startedAt, $durationSec) {
                $now = time();
                if ($now - $lastUpdate >= 5 && $dlNow > 0) {
                    $lastUpdate = $now;
                    $elapsed = $now - $startedAt;
                    // Estimate progress based on elapsed vs expected duration
                    $progress = min(90, 10 + (int) (80 * min(1, $elapsed / max(1, $durationSec))));
                    $export->update(['progress' => $progress]);
                }

                return 0; // 0 = continue, non-zero = abort
            }
        );

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        // CURLE_OPERATION_TIMEDOUT (28) from LOW_SPEED is expected and OK
        // as long as we actually received data.
        if (! $success) {
            if (file_exists($outputPath) && filesize($outputPath) > 1024) {
                Log::info("Export #{$export->id}: cURL finished (errno {$curlErrno}: {$error}) — file is valid, continuing.");

                return;
            }

            throw new \RuntimeException("Download failed (errno {$curlErrno}): {$error}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("go2rtc returned HTTP {$httpCode}");
        }
    }

    // ── Lifecycle helpers ───────────────────────────────────────────

    /**
     * Cancel a running export.
     */
    public function cancelExport(ClipExport $export): bool
    {
        if ($export->pid) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /PID {$export->pid} /F 2>NUL");
            } else {
                if (function_exists('posix_kill')) {
                    posix_kill($export->pid, 15);
                }
            }
        }

        $export->update([
            'status'        => 'failed',
            'error_message' => 'Export cancelled by user.',
            'pid'           => null,
        ]);

        if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
            Storage::disk('public')->delete($export->file_path);
        }

        return true;
    }

    /**
     * Delete an export and its file.
     */
    public function deleteExport(ClipExport $export): bool
    {
        if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
            Storage::disk('public')->delete($export->file_path);
        }

        $export->delete();

        return true;
    }
}
