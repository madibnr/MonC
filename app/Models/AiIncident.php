<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiIncident extends Model
{
    use HasFactory;

    const TYPE_WATCHLIST_HIT = 'watchlist_hit';

    const TYPE_UNKNOWN_PLATE = 'unknown_plate';

    const TYPE_REPEATED_ENTRY = 'repeated_entry';

    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    const INCIDENT_TYPES = [
        self::TYPE_WATCHLIST_HIT => 'Watchlist Hit',
        self::TYPE_UNKNOWN_PLATE => 'Unknown Plate',
        self::TYPE_REPEATED_ENTRY => 'Repeated Entry',
        self::TYPE_SUSPICIOUS_ACTIVITY => 'Suspicious Activity',
    ];

    protected $fillable = [
        'camera_id',
        'plate_detection_log_id',
        'watchlist_plate_id',
        'incident_type',
        'severity',
        'title',
        'description',
        'plate_number',
        'snapshot_path',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'resolution_notes',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function plateDetectionLog(): BelongsTo
    {
        return $this->belongsTo(PlateDetectionLog::class);
    }

    public function watchlistPlate(): BelongsTo
    {
        return $this->belongsTo(WatchlistPlate::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeAcknowledged(Builder $query): Builder
    {
        return $query->where('is_acknowledged', true);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('incident_type', $type);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getSeverityBadge(): string
    {
        return match ($this->severity) {
            'critical' => 'bg-red-100 text-red-700',
            'high' => 'bg-orange-100 text-orange-700',
            'medium' => 'bg-yellow-100 text-yellow-700',
            'low' => 'bg-blue-100 text-blue-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function getIncidentTypeLabel(): string
    {
        return self::INCIDENT_TYPES[$this->incident_type] ?? ucfirst(str_replace('_', ' ', $this->incident_type));
    }

    /**
     * Create a watchlist hit incident from a detection.
     */
    public static function createWatchlistHit(PlateDetectionLog $detection, WatchlistPlate $watchlist): self
    {
        return static::create([
            'camera_id' => $detection->camera_id,
            'plate_detection_log_id' => $detection->id,
            'watchlist_plate_id' => $watchlist->id,
            'incident_type' => self::TYPE_WATCHLIST_HIT,
            'severity' => $watchlist->alert_level,
            'title' => "Watchlist plate detected: {$detection->plate_number}",
            'description' => "Plate {$detection->plate_number} matched watchlist entry. Reason: {$watchlist->reason}. Owner: {$watchlist->vehicle_owner}.",
            'plate_number' => $detection->plate_number,
            'snapshot_path' => $detection->snapshot_path,
            'occurred_at' => $detection->detected_at,
            'metadata' => [
                'confidence' => $detection->confidence,
                'camera_name' => $detection->camera?->name,
                'watchlist_reason' => $watchlist->reason,
                'vehicle_owner' => $watchlist->vehicle_owner,
            ],
        ]);
    }
}
