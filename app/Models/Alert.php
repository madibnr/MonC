<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'source_type',
        'source_id',
        'is_read',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the source model (Camera or Nvr).
     */
    public function source()
    {
        if ($this->source_type === 'camera') {
            return $this->belongsTo(Camera::class, 'source_id');
        }
        if ($this->source_type === 'nvr') {
            return $this->belongsTo(Nvr::class, 'source_id');
        }

        return null;
    }

    // ── Static Factory Methods ───────────────────────────────────────

    public static function cameraOffline(Camera $camera, ?array $metadata = null): self
    {
        return static::create([
            'type' => 'camera_offline',
            'severity' => 'warning',
            'title' => "Camera Offline: {$camera->name}",
            'message' => "Camera '{$camera->name}' (CH{$camera->channel_no}) in {$camera->building->name} has gone offline.",
            'source_type' => 'camera',
            'source_id' => $camera->id,
            'metadata' => $metadata,
        ]);
    }

    public static function nvrDisconnected(Nvr $nvr, ?array $metadata = null): self
    {
        return static::create([
            'type' => 'nvr_disconnected',
            'severity' => 'critical',
            'title' => "NVR Disconnected: {$nvr->name}",
            'message' => "NVR '{$nvr->name}' ({$nvr->ip_address}) in {$nvr->building->name} is unreachable.",
            'source_type' => 'nvr',
            'source_id' => $nvr->id,
            'metadata' => $metadata,
        ]);
    }

    public static function hddCritical(Nvr $nvr, int $usagePercent, ?array $metadata = null): self
    {
        return static::create([
            'type' => 'hdd_critical',
            'severity' => $usagePercent >= 95 ? 'critical' : 'warning',
            'title' => "HDD Usage Critical: {$nvr->name}",
            'message' => "NVR '{$nvr->name}' HDD usage is at {$usagePercent}%.",
            'source_type' => 'nvr',
            'source_id' => $nvr->id,
            'metadata' => array_merge(['hdd_usage_percent' => $usagePercent], $metadata ?? []),
        ]);
    }

    public static function recordingFailed(Camera $camera, ?string $reason = null, ?array $metadata = null): self
    {
        return static::create([
            'type' => 'recording_failed',
            'severity' => 'critical',
            'title' => "Recording Failed: {$camera->name}",
            'message' => "Recording failed for camera '{$camera->name}'".($reason ? ": {$reason}" : '.'),
            'source_type' => 'camera',
            'source_id' => $camera->id,
            'metadata' => $metadata,
        ]);
    }

    public static function streamError(Camera $camera, string $error, ?array $metadata = null): self
    {
        return static::create([
            'type' => 'stream_error',
            'severity' => 'warning',
            'title' => "Stream Error: {$camera->name}",
            'message' => "Stream error for camera '{$camera->name}': {$error}",
            'source_type' => 'camera',
            'source_id' => $camera->id,
            'metadata' => $metadata,
        ]);
    }

    // ── Actions ──────────────────────────────────────────────────────

    public function markRead(): self
    {
        $this->update(['is_read' => true]);

        return $this;
    }

    public function resolve(?int $userId = null, ?string $notes = null): self
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        return $this;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('is_resolved', true);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', 'critical');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'slate',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'camera_offline' => 'fa-video-slash',
            'nvr_disconnected' => 'fa-server',
            'hdd_critical' => 'fa-hdd',
            'recording_failed' => 'fa-circle-xmark',
            'stream_error' => 'fa-triangle-exclamation',
            default => 'fa-bell',
        };
    }
}
