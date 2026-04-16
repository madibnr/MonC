<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'module',
        'camera_id',
        'description',
        'ip_address',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    // ── Static Logging Methods ───────────────────────────────────────

    /**
     * Create an audit log entry.
     */
    public static function record(
        string $actionType,
        string $module,
        string $description,
        ?int $cameraId = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'user_id' => Auth::id(),
            'action_type' => $actionType,
            'module' => $module,
            'camera_id' => $cameraId,
            'description' => $description,
            'ip_address' => Request::ip(),
            'metadata' => $metadata,
        ]);
    }

    // ── Convenience logging methods ──────────────────────────────────

    public static function logAuth(string $action, string $description, ?array $metadata = null): self
    {
        return static::record($action, 'auth', $description, null, $metadata);
    }

    public static function logLiveView(string $description, ?int $cameraId = null, ?array $metadata = null): self
    {
        return static::record('live_view', 'live', $description, $cameraId, $metadata);
    }

    public static function logPlayback(string $description, ?int $cameraId = null, ?array $metadata = null): self
    {
        return static::record('playback', 'playback', $description, $cameraId, $metadata);
    }

    public static function logExport(string $description, ?int $cameraId = null, ?array $metadata = null): self
    {
        return static::record('export', 'export', $description, $cameraId, $metadata);
    }

    public static function logSnapshot(string $description, ?int $cameraId = null, ?array $metadata = null): self
    {
        return static::record('snapshot', 'snapshot', $description, $cameraId, $metadata);
    }

    public static function logPermissionChange(string $description, ?array $metadata = null): self
    {
        return static::record('permission_change', 'permission', $description, null, $metadata);
    }

    public static function logSettingsChange(string $description, ?array $metadata = null): self
    {
        return static::record('settings_change', 'settings', $description, null, $metadata);
    }

    public static function logSystem(string $action, string $description, ?array $metadata = null): self
    {
        return static::record($action, 'system', $description, null, $metadata);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCamera($query, int $cameraId)
    {
        return $query->where('camera_id', $cameraId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
