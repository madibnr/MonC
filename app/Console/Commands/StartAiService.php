<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class StartAiService extends Command
{
    protected $signature = 'ai:start';
    protected $description = 'Start the AI microservice (Python FastAPI)';

    public function handle(): int
    {
        $serviceUrl = config('monc.ai.service_url', 'http://127.0.0.1:8100');

        // Check if already running
        try {
            $res = Http::timeout(2)->get("{$serviceUrl}/api/health");
            if ($res->successful()) {
                $this->info("AI service is already running at {$serviceUrl}");
                return 0;
            }
        } catch (\Exception $e) {
            // Not running
        }

        $scriptPath = base_path('ai-service');
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $venvPython = "{$scriptPath}\\venv\\Scripts\\python.exe";
            $systemPython = 'python';

            // Use venv python if exists, otherwise system python
            $python = file_exists($venvPython) ? $venvPython : $systemPython;
            $cmd = "start \"MonC AI Service\" /B \"{$python}\" \"{$scriptPath}\\main.py\"";
            exec($cmd);
        } else {
            $venvPython = "{$scriptPath}/venv/bin/python";
            $systemPython = 'python3';
            $python = file_exists($venvPython) ? $venvPython : $systemPython;
            $logFile = storage_path('logs/ai-service.log');
            exec("nohup \"{$python}\" \"{$scriptPath}/main.py\" >> \"{$logFile}\" 2>&1 &");
        }

        $this->info('Starting AI service...');
        sleep(5);

        // Verify
        try {
            $res = Http::timeout(3)->get("{$serviceUrl}/api/health");
            if ($res->successful()) {
                $this->info("AI service started successfully at {$serviceUrl}");
                return 0;
            }
        } catch (\Exception $e) {
            // Still not ready
        }

        $this->warn("AI service may still be loading (model download on first run can take minutes).");
        $this->warn("Check manually: {$serviceUrl}/api/health");
        return 0;
    }
}
