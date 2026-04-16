<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Camera;
use App\Models\Snapshot;
use App\Services\SnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SnapshotController extends Controller
{
    public function __construct(
        protected SnapshotService $snapshotService
    ) {}

    /**
     * Show snapshot gallery.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Snapshot::with(['camera.building', 'user'])->latest();

        if (! $user->isSuperadmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('camera_id')) {
            $query->where('camera_id', $request->camera_id);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $snapshots = $query->paginate(24);

        return view('snapshots.index', compact('snapshots'));
    }

    /**
     * Capture a snapshot from a camera.
     */
    public function capture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camera_id' => ['required', 'exists:cameras,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'from_hls' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $camera = Camera::findOrFail($validated['camera_id']);

        // Check live view permission (snapshot requires live view access)
        if (! $user->canLiveView($camera->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to capture from this camera.',
            ], 403);
        }

        // Capture snapshot
        $fromHls = ! empty($validated['from_hls']);
        $snapshot = $fromHls
            ? $this->snapshotService->captureFromHls($camera, $user->id, $validated['notes'] ?? null)
            : $this->snapshotService->capture($camera, $user->id, $validated['notes'] ?? null);

        if (! $snapshot) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to capture snapshot. Camera may be offline.',
            ], 500);
        }

        AuditLog::logSnapshot(
            "Snapshot captured from camera '{$camera->name}'",
            $camera->id,
            ['snapshot_id' => $snapshot->id, 'file_name' => $snapshot->file_name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Snapshot captured successfully.',
            'snapshot' => [
                'id' => $snapshot->id,
                'url' => $snapshot->getUrl(),
                'file_name' => $snapshot->file_name,
                'camera_name' => $camera->name,
                'captured_at' => $snapshot->created_at->format('d M Y H:i:s'),
            ],
        ]);
    }

    /**
     * Download a snapshot.
     */
    public function download(Snapshot $snapshot)
    {
        $fullPath = storage_path('app/public/'.$snapshot->file_path);
        if (! file_exists($fullPath)) {
            abort(404, 'Snapshot file not found.');
        }

        return response()->download($fullPath, $snapshot->file_name);
    }

    /**
     * Delete a snapshot.
     */
    public function destroy(Snapshot $snapshot): RedirectResponse
    {
        $this->snapshotService->deleteSnapshot($snapshot);

        return redirect()->route('snapshots.index')
            ->with('success', 'Snapshot deleted successfully.');
    }
}
