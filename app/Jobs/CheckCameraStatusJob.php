<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Camera;
use App\Models\Nvr;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CheckCameraStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        $nvrs = Nvr::active()->with('cameras')->get();

        foreach ($nvrs as $nvr) {
            // Check NVR reachability
            $nvrOnline = $this->checkNvrReachable($nvr);

            if (! $nvrOnline) {
                // Mark all cameras as offline
                $nvr->cameras()->where('status', '!=', 'maintenance')->update(['status' => 'offline']);

                if ($nvr->status === 'online') {
                    $nvr->update(['status' => 'offline']);

                    // Check for existing unresolved alert
                    $existing = Alert::where('type', 'nvr_disconnected')
                        ->where('source_id', $nvr->id)
                        ->unresolved()
                        ->where('created_at', '>=', now()->subHours(1))
                        ->exists();

                    if (! $existing) {
                        $alert = Alert::nvrDisconnected($nvr);
                        app(AlertService::class)->dispatch($alert);
                    }
                }

                continue;
            }

            // NVR is online
            $nvr->update(['status' => 'online', 'last_seen_at' => now()]);

            // Check individual cameras via RTSP probe
            foreach ($nvr->cameras as $camera) {
                if ($camera->status === 'maintenance' || ! $camera->is_active) {
                    continue;
                }

                $cameraOnline = $this->checkCameraStream($camera);
                $previousStatus = $camera->status;

                $camera->update([
                    'status' => $cameraOnline ? 'online' : 'offline',
                    'last_seen_at' => $cameraOnline ? now() : $camera->last_seen_at,
                ]);

                // Alert if camera went offline
                if ($previousStatus === 'online' && ! $cameraOnline) {
                    $existing = Alert::where('type', 'camera_offline')
                        ->where('source_id', $camera->id)
                        ->unresolved()
                        ->where('created_at', '>=', now()->subHours(1))
                        ->exists();

                    if (! $existing) {
                        $alert = Alert::cameraOffline($camera);
                        app(AlertService::class)->dispatch($alert);
                    }
                }

                // Auto-resolve if camera came back online
                if ($previousStatus === 'offline' && $cameraOnline) {
                    Alert::where('type', 'camera_offline')
                        ->where('source_id', $camera->id)
                        ->unresolved()
                        ->update([
                            'is_resolved' => true,
                            'resolved_at' => now(),
                            'resolution_notes' => 'Auto-resolved: Camera came back online.',
                        ]);
                }
            }
        }

        Log::info('CheckCameraStatusJob: Camera status check completed.');
    }

    protected function checkNvrReachable(Nvr $nvr): bool
    {
        $connection = @fsockopen($nvr->ip_address, $nvr->port ?? 554, $errno, $errstr, 3);
        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    protected function checkCameraStream(Camera $camera): bool
    {
        $ffprobe = config('monc.ffprobe_path', 'ffprobe');
        $streamUrl = $camera->getSubStreamUrl();

        $command = [
            $ffprobe,
            '-rtsp_transport', 'tcp',
            '-i', $streamUrl,
            '-timeout', '5000000', // 5 seconds in microseconds
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_streams',
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(10);
            $process->run();

            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
