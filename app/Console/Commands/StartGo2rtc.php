<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartGo2rtc extends Command
{
    protected $signature = 'go2rtc:start';
    protected $description = 'Start go2rtc service if not running';

    public function handle()
    {
        $binaryPath = base_path('bin/go2rtc.exe');
        $configPath = base_path('bin/go2rtc.yaml');

        if (!file_exists($binaryPath)) {
            $this->error("go2rtc binary not found at: {$binaryPath}");
            return 1;
        }

        // Check if already running
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get('http://127.0.0.1:1984/api');
            if ($response->successful()) {
                $this->info('go2rtc is already running');
                return 0;
            }
        } catch (\Exception $e) {
            // Not running, start it
        }

        // Start go2rtc
        $this->info('Starting go2rtc...');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start \"\" /B \"{$binaryPath}\" -config \"{$configPath}\"";
            exec($cmd);
        } else {
            $cmd = "nohup \"{$binaryPath}\" -config \"{$configPath}\" >> " . storage_path('logs/go2rtc.log') . " 2>&1 &";
            exec($cmd);
        }

        // Wait for startup
        sleep(3);

        // Verify
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get('http://127.0.0.1:1984/api');
            if ($response->successful()) {
                $this->info('go2rtc started successfully');
                Log::info('go2rtc service started');
                return 0;
            }
        } catch (\Exception $e) {
            $this->error('Failed to start go2rtc: ' . $e->getMessage());
            Log::error('Failed to start go2rtc: ' . $e->getMessage());
            return 1;
        }
    }
}
