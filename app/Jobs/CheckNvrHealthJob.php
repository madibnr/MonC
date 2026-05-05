<?php

namespace App\Jobs;

use App\Services\NvrHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckNvrHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function handle(NvrHealthService $service): void
    {
        $service->checkAllNvrs();
        Log::info('CheckNvrHealthJob: All NVR health checks completed.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CheckNvrHealthJob failed: {$exception->getMessage()}");
    }
}
