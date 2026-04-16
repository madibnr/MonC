<?php

namespace App\Http\Controllers;

use App\Models\AiCameraSetting;
use App\Models\Building;
use App\Models\Camera;
use App\Services\AiAnalyticsService;
use Illuminate\Http\Request;

class AiCameraController extends Controller
{
    /**
     * Display AI Camera Assignment page.
     * Shows all cameras grouped by building with AI toggle.
     */
    public function index()
    {
        $buildings = Building::active()
            ->with(['cameras' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with('aiSetting');
            }])
            ->orderBy('name')
            ->get();

        $stats = [
            'total_cameras' => Camera::active()->count(),
            'ai_enabled' => AiCameraSetting::enabled()->count(),
            'plate_recognition' => AiCameraSetting::enabled()->plateRecognition()->count(),
        ];

        return view('ai.cameras.index', compact('buildings', 'stats'));
    }

    /**
     * Update AI settings for multiple cameras (bulk toggle).
     */
    public function update(Request $request)
    {
        $request->validate([
            'cameras' => 'required|array',
            'cameras.*.camera_id' => 'required|exists:cameras,id',
            'cameras.*.ai_enabled' => 'required|boolean',
            'cameras.*.ai_type' => 'required|string|in:'.implode(',', array_keys(AiCameraSetting::AI_TYPES)),
            'cameras.*.detection_interval_seconds' => 'required|integer|min:1|max:300',
            'cameras.*.confidence_threshold' => 'required|integer|min:1|max:100',
        ]);

        $userId = auth()->id();
        $updatedCount = 0;

        foreach ($request->cameras as $cameraData) {
            AiCameraSetting::updateOrCreate(
                ['camera_id' => $cameraData['camera_id']],
                [
                    'ai_enabled' => $cameraData['ai_enabled'],
                    'ai_type' => $cameraData['ai_type'],
                    'detection_interval_seconds' => $cameraData['detection_interval_seconds'],
                    'confidence_threshold' => $cameraData['confidence_threshold'],
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ]
            );
            $updatedCount++;
        }

        return redirect()->route('ai.cameras.index')
            ->with('success', "AI settings updated for {$updatedCount} camera(s).");
    }

    /**
     * Quick toggle AI for a single camera (AJAX).
     */
    public function toggle(Request $request, Camera $camera)
    {
        $request->validate([
            'ai_enabled' => 'required|boolean',
        ]);

        $setting = AiCameraSetting::updateOrCreate(
            ['camera_id' => $camera->id],
            [
                'ai_enabled' => $request->ai_enabled,
                'ai_type' => $request->input('ai_type', AiCameraSetting::TYPE_PLATE_RECOGNITION),
                'detection_interval_seconds' => $request->input('detection_interval_seconds', config('monc.ai.default_detection_interval', 5)),
                'confidence_threshold' => $request->input('confidence_threshold', config('monc.ai.default_confidence_threshold', 85)),
                'updated_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'ai_enabled' => $setting->ai_enabled,
                'message' => $setting->ai_enabled
                    ? "AI enabled for {$camera->name}"
                    : "AI disabled for {$camera->name}",
            ]);
        }

        return back()->with('success', $setting->ai_enabled
            ? "AI enabled for {$camera->name}"
            : "AI disabled for {$camera->name}");
    }

    /**
     * Update settings for a single camera (AJAX).
     */
    public function updateSingle(Request $request, Camera $camera)
    {
        $request->validate([
            'ai_type' => 'required|string|in:'.implode(',', array_keys(AiCameraSetting::AI_TYPES)),
            'detection_interval_seconds' => 'required|integer|min:1|max:300',
            'confidence_threshold' => 'required|integer|min:1|max:100',
        ]);

        $setting = AiCameraSetting::updateOrCreate(
            ['camera_id' => $camera->id],
            [
                'ai_type' => $request->ai_type,
                'detection_interval_seconds' => $request->detection_interval_seconds,
                'confidence_threshold' => $request->confidence_threshold,
                'updated_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'setting' => $setting,
                'message' => "AI settings updated for {$camera->name}",
            ]);
        }

        return back()->with('success', "AI settings updated for {$camera->name}");
    }

    /**
     * Check AI service health status (AJAX).
     */
    public function healthCheck(AiAnalyticsService $aiService)
    {
        return response()->json([
            'healthy' => $aiService->isServiceHealthy(),
            'service_url' => config('monc.ai.service_url'),
        ]);
    }
}
