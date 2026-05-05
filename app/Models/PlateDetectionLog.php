<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlateDetectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'plate_number',
        'plate_number_normalized',
        'confidence',
        'snapshot_path',
        'vehicle_type',
        'vehicle_color',
        'direction',
        'bounding_box',
        'raw_response',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'bounding_box' => 'array',
            'raw_response' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(AiIncident::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('detected_at', today());
    }

    public function scopeByCamera(Builder $query, int $cameraId): Builder
    {
        return $query->where('camera_id', $cameraId);
    }

    public function scopeByPlate(Builder $query, string $plate): Builder
    {
        return $query->where('plate_number_normalized', static::normalizePlate($plate));
    }

    public function scopeHighConfidence(Builder $query, int $threshold = 80): Builder
    {
        return $query->where('confidence', '>=', $threshold);
    }

    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('detected_at', [$from, $to]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Normalize plate number for consistent matching.
     * Removes spaces, dashes, and converts to uppercase.
     */
    public static function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($plate)));
    }

    /**
     * Check if this plate is on the watchlist.
     */
    public function isOnWatchlist(): bool
    {
        return WatchlistPlate::active()
            ->where('plate_number_normalized', $this->plate_number_normalized)
            ->exists();
    }

    /**
     * Get the matching watchlist entry if exists.
     */
    public function getWatchlistMatch(): ?WatchlistPlate
    {
        return WatchlistPlate::active()
            ->where('plate_number_normalized', $this->plate_number_normalized)
            ->first();
    }
}
