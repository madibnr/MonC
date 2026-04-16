<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessClipExportJob;
use App\Models\AuditLog;
use App\Models\Camera;
use App\Models\ClipExport;
use App\Services\ClipExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClipExportController extends Controller
{
    public function __construct(
        protected ClipExportService $exportService
    ) {}

    /**
     * Show export page with form and export history.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get cameras user can export
        if ($user->isSuperadmin()) {
            $cameras = Camera::with(['nvr', 'building'])
                ->active()
                ->orderBy('building_id')
                ->orderBy('channel_no')
                ->get();
        } else {
            $cameraIds = $user->cameraPermissions()
                ->where('can_export', true)
                ->pluck('camera_id');

            $cameras = Camera::with(['nvr', 'building'])
                ->whereIn('id', $cameraIds)
                ->active()
                ->orderBy('building_id')
                ->orderBy('channel_no')
                ->get();
        }

        $camerasGrouped = $cameras->groupBy(fn ($c) => $c->building->name ?? 'Unknown');

        // Export history
        $exports = ClipExport::with(['camera.building', 'user'])
            ->when(! $user->isSuperadmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(20);

        return view('exports.index', compact('camerasGrouped', 'exports'));
    }

    /**
     * Create a new export request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'exists:cameras,id'],
            'clip_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
        ]);

        $user = Auth::user();
        $camera = Camera::findOrFail($validated['camera_id']);

        // Check export permission
        if (! $user->canExport($camera->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to export from this camera.',
            ], 403);
        }

        $export = ClipExport::create([
            'user_id' => $user->id,
            'camera_id' => $camera->id,
            'clip_date' => $validated['clip_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'pending',
        ]);

        // Dispatch job
        ProcessClipExportJob::dispatch($export->id);

        AuditLog::logExport(
            "Export requested for camera '{$camera->name}' ({$validated['clip_date']} {$validated['start_time']}-{$validated['end_time']})",
            $camera->id,
            ['export_id' => $export->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Export job queued successfully.',
            'export_id' => $export->id,
        ]);
    }

    /**
     * Check export status (AJAX polling).
     */
    public function status(ClipExport $clipExport): JsonResponse
    {
        return response()->json([
            'id' => $clipExport->id,
            'status' => $clipExport->status,
            'progress' => $clipExport->progress,
            'file_name' => $clipExport->file_name,
            'file_size' => $clipExport->getFileSizeFormatted(),
            'download_url' => $clipExport->getDownloadUrl(),
            'error_message' => $clipExport->error_message,
        ]);
    }

    /**
     * Download exported clip.
     */
    public function download(ClipExport $clipExport)
    {
        if ($clipExport->status !== 'completed' || ! $clipExport->file_path) {
            abort(404, 'Export not ready for download.');
        }

        $fullPath = storage_path('app/public/'.$clipExport->file_path);
        if (! file_exists($fullPath)) {
            abort(404, 'Export file not found.');
        }

        AuditLog::logExport(
            "Downloaded export: {$clipExport->file_name}",
            $clipExport->camera_id,
            ['export_id' => $clipExport->id]
        );

        return response()->download($fullPath, $clipExport->file_name);
    }

    /**
     * Delete an export.
     */
    public function destroy(ClipExport $clipExport): RedirectResponse
    {
        $this->exportService->deleteExport($clipExport);

        return redirect()->route('exports.index')
            ->with('success', 'Export deleted successfully.');
    }
}
