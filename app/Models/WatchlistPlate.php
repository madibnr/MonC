<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchlistPlate extends Model
{
    use HasFactory;

    const LEVEL_LOW = 'low';

    const LEVEL_MEDIUM = 'medium';

    const LEVEL_HIGH = 'high';

    const LEVEL_CRITICAL = 'critical';

    const ALERT_LEVELS = [
        self::LEVEL_LOW => 'Low',
        self::LEVEL_MEDIUM => 'Medium',
        self::LEVEL_HIGH => 'High',
        self::LEVEL_CRITICAL => 'Critical',
    ];

    protected $fillable = [
        'plate_number',
        'plate_number_normalized',
        'alert_level',
        'reason',
        'vehicle_owner',
        'vehicle_description',
        'notes',
        'is_active',
        'notify_telegram',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notify_telegram' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(AiIncident::class);
    }

    public function detections(): HasMany
    {
        return $this->hasMany(PlateDetectionLog::class, 'plate_number_normalized', 'plate_number_normalized');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel(Builder $query, string $level): Builder
    {
        return $query->where('alert_level', $level);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getAlertLevelBadge(): string
    {
        return match ($this->alert_level) {
            self::LEVEL_CRITICAL => 'bg-red-100 text-red-700',
            self::LEVEL_HIGH => 'bg-orange-100 text-orange-700',
            self::LEVEL_MEDIUM => 'bg-yellow-100 text-yellow-700',
            self::LEVEL_LOW => 'bg-blue-100 text-blue-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * Auto-normalize plate number before saving.
     */
    protected static function booted(): void
    {
        static::saving(function (WatchlistPlate $plate) {
            $plate->plate_number_normalized = PlateDetectionLog::normalizePlate($plate->plate_number);
        });
    }
}
