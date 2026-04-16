<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\ClipExport;
use App\Services\ClipExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClipExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 3600; // 1 hour

    public function __construct(
        protected int $exportId
    ) {}

    public function handle(ClipExportService $service): void
    {
        $export = ClipExport::find($this->exportId);

        if (! $export || $export->status === 'completed') {
            return;
        }

        $success = $service->exportClip($export);

        if ($success) {
            AuditLog::record(
                'export',
                'export',
                "Clip export completed: {$export->file_name}",
                $export->camera_id,
                ['export_id' => $export->id, 'file_size' => $export->file_size]
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessClipExportJob failed for export #{$this->exportId}: {$exception->getMessage()}");

        $export = ClipExport::find($this->exportId);
        if ($export) {
            $export->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
