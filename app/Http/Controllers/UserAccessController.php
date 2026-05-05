<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Camera;
use App\Models\User;
use App\Models\UserCameraPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserAccessController extends Controller
{
    /**
     * List all users with their role and camera permission count.
     */
    public function index(): View
    {
        $users = User::withCount('cameraPermissions')
            ->orderBy('name')
            ->get();

        return view('user-access.index', compact('users'));
    }

    /**
     * Show user's camera permissions with checkboxes grouped by building.
     */
    public function edit(User $user): View
    {
        // Load all cameras grouped by building name for the view
        $allCameras = Camera::with(['building', 'nvr'])
            ->active()
            ->orderBy('building_id')
            ->orderBy('nvr_id')
            ->orderBy('channel_no')
            ->get();

        $cameras = $allCameras->groupBy(function ($camera) {
            return $camera->building->name ?? 'Unknown';
        });

        // Load existing permissions for this user, keyed by camera_id
        $permissions = $user->cameraPermissions()
            ->get()
            ->keyBy('camera_id');

        return view('user-access.edit', compact('user', 'cameras', 'permissions'));
    }

    /**
     * Save camera permissions for a user.
     * The view sends: permissions[camera_id][can_live_view], permissions[camera_id][can_playback], etc.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $grantedBy = Auth::id();
        $permissionsInput = $request->input('permissions', []);

        // Remove all existing permissions for this user
        $user->cameraPermissions()->delete();

        $count = 0;

        foreach ($permissionsInput as $cameraId => $perm) {
            $canLiveView = ! empty($perm['can_live_view']);
            $canPlayback = ! empty($perm['can_playback']);
            $canExport = ! empty($perm['can_export']);

            // Only create permission if at least one access type is granted
            if ($canLiveView || $canPlayback || $canExport) {
                UserCameraPermission::create([
                    'user_id' => $user->id,
                    'camera_id' => $cameraId,
                    'can_live_view' => $canLiveView,
                    'can_playback' => $canPlayback,
                    'can_export' => $canExport,
                    'granted_by' => $grantedBy,
                ]);
                $count++;
            }
        }

        ActivityLog::log('permissions_updated', "Camera permissions updated for user '{$user->name}'", [
            'target_user_id' => $user->id,
            'permission_count' => $count,
        ]);

        return redirect()
            ->route('user-access.edit', $user)
            ->with('success', "Camera permissions updated successfully. {$count} camera(s) assigned.");
    }

    /**
     * Assign multiple cameras to a user at once.
     */
    public function bulkAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'camera_ids' => ['required', 'array'],
            'camera_ids.*' => ['exists:cameras,id'],
            'can_live_view' => ['boolean'],
            'can_playback' => ['boolean'],
            'can_export' => ['boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $grantedBy = Auth::id();

        $canLiveView = ! empty($validated['can_live_view']);
        $canPlayback = ! empty($validated['can_playback']);
        $canExport = ! empty($validated['can_export']);

        foreach ($validated['camera_ids'] as $cameraId) {
            UserCameraPermission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'camera_id' => $cameraId,
                ],
                [
                    'can_live_view' => $canLiveView,
                    'can_playback' => $canPlayback,
                    'can_export' => $canExport,
                    'granted_by' => $grantedBy,
                ]
            );
        }

        ActivityLog::log('permissions_bulk_assigned', "Bulk camera permissions assigned to user '{$user->name}'", [
            'target_user_id' => $user->id,
            'camera_count' => count($validated['camera_ids']),
        ]);

        return redirect()
            ->route('user-access.edit', $user)
            ->with('success', count($validated['camera_ids']).' camera permissions assigned successfully.');
    }
}
