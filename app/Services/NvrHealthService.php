<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Nvr;
use App\Models\NvrHealthLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NvrHealthService
{
    /**
     * Check health of a single NVR via Hikvision ISAPI.
     */
    public function checkHealth(Nvr $nvr): ?NvrHealthLog
    {
        $healthData = [
            'nvr_id' => $nvr->id,
            'overall_status' => 'unreachable',
        ];

        // First check if NVR is reachable
        if (! $this->isReachable($nvr)) {
            $log = NvrHealthLog::create($healthData);

            // Check if NVR was previously online
            if ($nvr->status === 'online') {
                $nvr->update(['status' => 'offline']);
                Alert::nvrDisconnected($nvr);
                $this->dispatchAlert(Alert::latest()->first());
            }

            return $log;
        }

        // Try to get system status via ISAPI
        try {
            // Get HDD status (try multiple known Hikvision endpoints)
            $hddData = $this->getIsapiData($nvr, '/ISAPI/ContentMgmt/Storage')
                    ?? $this->getIsapiData($nvr, '/ISAPI/ContentMgmt/Storage/hdd');
            // Get system status
            $systemData = $this->getIsapiData($nvr, '/ISAPI/System/status');
            // Get device info
            $deviceData = $this->getIsapiData($nvr, '/ISAPI/System/deviceInfo');

            // Parse HDD info
            if ($hddData) {
                $healthData['hdd_total_bytes'] = $this->parseHddValue($hddData, 'capacity');
                $healthData['hdd_free_bytes'] = $this->parseHddValue($hddData, 'freeSpace');
                if ($healthData['hdd_total_bytes'] && $healthData['hdd_free_bytes']) {
                    $healthData['hdd_used_bytes'] = $healthData['hdd_total_bytes'] - $healthData['hdd_free_bytes'];
                    $healthData['hdd_usage_percent'] = $healthData['hdd_total_bytes'] > 0
                        ? (int) round(($healthData['hdd_used_bytes'] / $healthData['hdd_total_bytes']) * 100)
                        : 0;
                    $healthData['hdd_status'] = match (true) {
                        $healthData['hdd_usage_percent'] >= 95 => 'critical',
                        $healthData['hdd_usage_percent'] >= 90 => 'warning',
                        default => 'ok',
                    };
                }
            }

            // Parse system status
            if ($systemData) {
                $healthData['cpu_usage_percent'] = $this->parseXmlValue($systemData, 'cpuUtilization');
                $healthData['memory_usage_percent'] = $this->parseXmlValue($systemData, 'memoryUsage');
            }

            // Parse device info
            if ($deviceData) {
                $healthData['firmware_version'] = $this->parseXmlString($deviceData, 'firmwareVersion');
            }

            // Determine recording status (check if channels are recording)
            $recordingChannels = $this->getRecordingChannels($nvr);
            $healthData['is_recording'] = $recordingChannels > 0;
            $healthData['recording_channels'] = $recordingChannels;

            // Determine overall status
            $healthData['overall_status'] = $this->determineOverallStatus($healthData);
            $healthData['raw_data'] = [
                'hdd_raw' => $hddData ? substr($hddData, 0, 2000) : null,
                'system_raw' => $systemData ? substr($systemData, 0, 2000) : null,
            ];

            // Update NVR status
            $nvr->update([
                'status' => 'online',
                'last_seen_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::warning("NVR health check partial failure for {$nvr->name}: {$e->getMessage()}");
            $healthData['overall_status'] = 'warning';
        }

        $log = NvrHealthLog::create($healthData);

        // Generate alerts if needed
        $this->checkAndAlert($nvr, $healthData);

        return $log;
    }

    /**
     * Check health of all active NVRs.
     */
    public function checkAllNvrs(): array
    {
        $nvrs = Nvr::active()->get();
        $results = [];

        foreach ($nvrs as $nvr) {
            $results[$nvr->id] = $this->checkHealth($nvr);
        }

        return $results;
    }

    /**
     * Check if NVR is reachable via socket.
     */
    protected function isReachable(Nvr $nvr): bool
    {
        $connection = @fsockopen($nvr->ip_address, 80, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);

            return true;
        }

        // Try RTSP port
        $connection = @fsockopen($nvr->ip_address, $nvr->port ?? 554, $errno, $errstr, 3);
        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    /**
     * Get data from Hikvision ISAPI endpoint.
     * Hikvision NVRs use digest authentication (not basic auth).
     */
    protected function getIsapiData(Nvr $nvr, string $endpoint): ?string
    {
        $url = "http://{$nvr->ip_address}{$endpoint}";

        try {
            // Use cURL directly for digest auth support
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$nvr->username}:{$nvr->password}");

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && $body) {
                return $body;
            }

            Log::debug("ISAPI {$nvr->name}{$endpoint}: HTTP {$httpCode}" . ($error ? " — {$error}" : ''));
        } catch (\Exception $e) {
            Log::debug("ISAPI request failed for {$nvr->name}{$endpoint}: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Parse HDD capacity value from ISAPI XML response.
     */
    protected function parseHddValue(?string $xml, string $field): ?int
    {
        if (! $xml) {
            return null;
        }
        if (preg_match("/<{$field}>(\\d+)<\\/{$field}>/i", $xml, $matches)) {
            return (int) $matches[1] * 1024 * 1024; // Convert MB to bytes
        }

        return null;
    }

    /**
     * Parse integer value from XML.
     */
    protected function parseXmlValue(?string $xml, string $field): ?int
    {
        if (! $xml) {
            return null;
        }
        if (preg_match("/<{$field}>(\\d+)<\\/{$field}>/i", $xml, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Parse string value from XML.
     */
    protected function parseXmlString(?string $xml, string $field): ?string
    {
        if (! $xml) {
            return null;
        }
        if (preg_match("/<{$field}>([^<]+)<\\/{$field}>/i", $xml, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get number of recording channels.
     */
    protected function getRecordingChannels(Nvr $nvr): int
    {
        // Try multiple known Hikvision recording status endpoints
        $data = $this->getIsapiData($nvr, '/ISAPI/ContentMgmt/record/control/manual/status')
             ?? $this->getIsapiData($nvr, '/ISAPI/ContentMgmt/record/status');

        if (! $data) {
            // Fallback: if NVR is reachable and has cameras, assume recording
            return $nvr->cameras()->active()->count();
        }

        // Count recording channels from response
        preg_match_all('/recording/i', $data, $matches);

        return count($matches[0] ?? []);
    }

    /**
     * Determine overall health status.
     */
    protected function determineOverallStatus(array $data): string
    {
        if (($data['hdd_usage_percent'] ?? 0) >= 95) {
            return 'critical';
        }
        if (($data['cpu_usage_percent'] ?? 0) >= 90) {
            return 'critical';
        }
        if (($data['hdd_usage_percent'] ?? 0) >= 90) {
            return 'warning';
        }
        if (($data['cpu_usage_percent'] ?? 0) >= 80) {
            return 'warning';
        }
        if (($data['memory_usage_percent'] ?? 0) >= 90) {
            return 'warning';
        }
        if (! ($data['is_recording'] ?? false)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Check health data and generate alerts if needed.
     */
    protected function checkAndAlert(Nvr $nvr, array $data): void
    {
        // HDD critical alert
        if (($data['hdd_usage_percent'] ?? 0) >= 90) {
            // Check if there's already an unresolved alert for this
            $existing = Alert::where('type', 'hdd_critical')
                ->where('source_type', 'nvr')
                ->where('source_id', $nvr->id)
                ->unresolved()
                ->where('created_at', '>=', now()->subHours(6))
                ->exists();

            if (! $existing) {
                $alert = Alert::hddCritical($nvr, $data['hdd_usage_percent']);
                $this->dispatchAlert($alert);
            }
        }

        // Recording failed alert
        if (! ($data['is_recording'] ?? false) && $nvr->cameras()->active()->count() > 0) {
            $existing = Alert::where('type', 'recording_failed')
                ->where('source_type', 'nvr')
                ->where('source_id', $nvr->id)
                ->unresolved()
                ->where('created_at', '>=', now()->subHours(1))
                ->exists();

            if (! $existing) {
                // Create alert for each camera that should be recording
                $cameras = $nvr->cameras()->active()->get();
                foreach ($cameras->take(1) as $camera) { // Only one alert per NVR
                    $alert = Alert::recordingFailed($camera, 'NVR not recording');
                    $this->dispatchAlert($alert);
                }
            }
        }
    }

    /**
     * Dispatch alert through AlertService.
     */
    protected function dispatchAlert(Alert $alert): void
    {
        try {
            app(AlertService::class)->dispatch($alert);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch alert #{$alert->id}: {$e->getMessage()}");
        }
    }
}
