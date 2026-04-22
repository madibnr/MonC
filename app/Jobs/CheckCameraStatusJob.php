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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckCameraStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    protected string $go2rtcApiUrl;

    public function handle(): void
    {
        $this->go2rtcApiUrl = rtrim(config('monc.go2rtc.api_url', 'http://127.0.0.1:1984'), '/');

        $nvrs = Nvr::active()->with('cameras')->get();

        foreach ($nvrs as $nvr) {
            // Check NVR reachability via RTSP port
            $nvrOnline = $this->checkNvrReachable($nvr);

            if (! $nvrOnline) {
                // Mark all cameras as offline
                $nvr->cameras()->where('status', '!=', 'maintenance')->update(['status' => 'offline']);

                if ($nvr->status === 'online') {
                    $nvr->update(['status' => 'offline']);

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

            // Check individual cameras via go2rtc probe
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

    /**
     * Check NVR reachability via RTSP port socket.
     */
    protected function checkNvrReachable(Nvr $nvr): bool
    {
        $connection = @fsockopen($nvr->ip_address, $nvr->port ?? 554, $errno, $errstr, 3);
        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    /**
     * Check camera stream by temporarily registering it in go2rtc
     * and verifying the stream has active producers.
     * No FFmpeg/ffprobe required.
     */
    protected function checkCameraStream(Camera $camera): bool
    {
        $streamUrl = $camera->getSubStreamUrl();
        if (! $streamUrl) {
            return false;
        }

        $streamName = "probe_camera_{$camera->id}";

        try {
            // Register stream in go2rtc
            $putUrl = "{$this->go2rtcApiUrl}/api/streams?name={$streamName}&src=" . urlencode($streamUrl);
            $ch = curl_init($putUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);

            // Wait briefly for go2rtc to connect
            usleep(1500000); // 1.5 seconds

            // Check if stream has producers (connected to RTSP source)
            $response = Http::timeout(5)->get("{$this->go2rtcApiUrl}/api/streams");
            $streams = $response->json() ?? [];

            $isOnline = false;
            if (isset($streams[$streamName])) {
                $producers = $streams[$streamName]['producers'] ?? [];
                // If producers exist, the stream source is reachable
                $isOnline = ! empty($producers);
            }

            // Cleanup: remove the probe stream
            Http::timeout(3)->delete("{$this->go2rtcApiUrl}/api/streams?name={$streamName}");

            return $isOnline;
        } catch (\Exception $e) {
            // Cleanup on error
            try {
                Http::timeout(3)->delete("{$this->go2rtcApiUrl}/api/streams?name={$streamName}");
            } catch (\Exception $e2) {
                // ignore
            }

            return false;
        }
    }
}
