<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\ClipExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ClipExportService
{
    protected string $ffmpegPath;

    protected string $outputBasePath;

    public function __construct()
    {
        $this->ffmpegPath = config('monc.ffmpeg_path', 'ffmpeg');
        $this->outputBasePath = storage_path('app/public/exports');
    }

    /**
     * Start exporting a clip from camera recording.
     */
    public function exportClip(ClipExport $export): bool
    {
        $camera = $export->camera()->with('nvr')->first();

        if (! $camera || ! $camera->nvr) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'Camera or NVR not found.',
            ]);

            return false;
        }

        $nvr = $camera->nvr;

        // Build output directory
        $dateDir = $export->clip_date->format('Y-m-d');
        $outputDir = $this->outputBasePath."/{$dateDir}";
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Generate filename
        $fileName = sprintf(
            'export_%s_ch%d_%s_%s-%s.%s',
            $camera->building->code ?? 'CAM',
            $camera->channel_no,
            $export->clip_date->format('Ymd'),
            str_replace(':', '', $export->start_time),
            str_replace(':', '', $export->end_time),
            $export->format
        );
        $outputPath = $outputDir.'/'.$fileName;

        // Build Hikvision RTSP playback URL with time range
        $startDatetime = $export->clip_date->format('Ymd').'T'.str_replace(':', '', $export->start_time).'Z';
        $endDatetime = $export->clip_date->format('Ymd').'T'.str_replace(':', '', $export->end_time).'Z';

        $rtspUrl = sprintf(
            'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
            $nvr->username,
            $nvr->password,
            $nvr->ip_address,
            $nvr->port ?? 554,
            $camera->channel_no,
            $startDatetime,
            $endDatetime
        );

        // Build FFmpeg command
        $command = [
            $this->ffmpegPath,
            '-rtsp_transport', 'tcp',
            '-i', $rtspUrl,
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ];

        $export->update([
            'status' => 'processing',
            'progress' => 10,
        ]);

        try {
            $process = new Process($command);
            $process->setTimeout(3600); // 1 hour max
            $process->start();

            $export->update(['pid' => $process->getPid()]);

            // Wait for process to complete
            $process->wait();

            if ($process->isSuccessful() && file_exists($outputPath)) {
                $fileSize = filesize($outputPath);
                $relativePath = "exports/{$dateDir}/{$fileName}";

                $export->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'file_path' => $relativePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'completed_at' => now(),
                    'pid' => null,
                ]);

                Log::info("Clip export completed: {$fileName} ({$fileSize} bytes)");

                return true;
            } else {
                $errorOutput = $process->getErrorOutput();
                $export->update([
                    'status' => 'failed',
                    'error_message' => substr($errorOutput, 0, 1000),
                    'pid' => null,
                ]);

                Log::error("Clip export failed for export #{$export->id}: {$errorOutput}");

                return false;
            }
        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'pid' => null,
            ]);

            Log::error("Clip export exception for export #{$export->id}: {$e->getMessage()}");

            return false;
        }
    }

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
            'status' => 'failed',
            'error_message' => 'Export cancelled by user.',
            'pid' => null,
        ]);

        // Clean up partial file
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
