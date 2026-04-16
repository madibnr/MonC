<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'nvr_id',
        'building_id',
        'channel_no',
        'name',
        'location',
        'description',
        'stream_url',
        'sub_stream_url',
        'status',
        'is_active',
        'last_seen_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'channel_no' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function nvr(): BelongsTo
    {
        return $this->belongsTo(Nvr::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserCameraPermission::class);
    }

    public function authorizedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_camera_permissions')
            ->withPivot('can_live_view', 'can_playback', 'can_export')
            ->withTimestamps();
    }

    public function streamSessions(): HasMany
    {
        return $this->hasMany(StreamSession::class);
    }

    public function clipExports(): HasMany
    {
        return $this->hasMany(ClipExport::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function aiSetting(): HasOne
    {
        return $this->hasOne(AiCameraSetting::class);
    }

    public function plateDetections(): HasMany
    {
        return $this->hasMany(PlateDetectionLog::class);
    }

    public function aiIncidents(): HasMany
    {
        return $this->hasMany(AiIncident::class);
    }

    // ── Stream URL helpers ───────────────────────────────────────────

    public function getMainStreamUrl(): string
    {
        if (! empty($this->stream_url)) {
            return $this->stream_url;
        }

        $nvr = $this->nvr;

        return "rtsp://{$nvr->username}:{$nvr->password}@{$nvr->ip_address}:{$nvr->port}/Streaming/Channels/{$this->channel_no}01";
    }

    public function getSubStreamUrl(): string
    {
        if (! empty($this->sub_stream_url)) {
            return $this->sub_stream_url;
        }

        $nvr = $this->nvr;

        return "rtsp://{$nvr->username}:{$nvr->password}@{$nvr->ip_address}:{$nvr->port}/Streaming/Channels/{$this->channel_no}02";
    }

    public function getHlsPath(): string
    {
        return "streams/camera_{$this->id}/index.m3u8";
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    public function scopeAiEnabled(Builder $query): Builder
    {
        return $query->whereHas('aiSetting', function ($q) {
            $q->where('ai_enabled', true);
        });
    }

    public function isAiEnabled(): bool
    {
        return $this->aiSetting && $this->aiSetting->ai_enabled;
    }
}
