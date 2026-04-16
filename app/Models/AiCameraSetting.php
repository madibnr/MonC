<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCameraSetting extends Model
{
    use HasFactory;

    // AI Type constants (scalable for future modules)
    const TYPE_PLATE_RECOGNITION = 'plate_recognition';

    const TYPE_HUMAN_DETECTION = 'human_detection';

    const TYPE_VEHICLE_COUNTING = 'vehicle_counting';

    const TYPE_MOTION_DETECTION = 'motion_detection';

    const AI_TYPES = [
        self::TYPE_PLATE_RECOGNITION => 'Plate Recognition',
        self::TYPE_HUMAN_DETECTION => 'Human Detection',
        self::TYPE_VEHICLE_COUNTING => 'Vehicle Counting',
        self::TYPE_MOTION_DETECTION => 'Motion Detection',
    ];

    protected $fillable = [
        'camera_id',
        'ai_enabled',
        'ai_type',
        'detection_interval_seconds',
        'confidence_threshold',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'detection_interval_seconds' => 'integer',
            'confidence_threshold' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('ai_enabled', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('ai_type', $type);
    }

    public function scopePlateRecognition(Builder $query): Builder
    {
        return $query->where('ai_type', self::TYPE_PLATE_RECOGNITION);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getAiTypeLabel(): string
    {
        return self::AI_TYPES[$this->ai_type] ?? ucfirst(str_replace('_', ' ', $this->ai_type));
    }

    public static function getAvailableTypes(): array
    {
        return self::AI_TYPES;
    }

    /**
     * Get all cameras that should be processed by AI.
     * This is the core method for performance optimization.
     */
    public static function getEnabledCameras(?string $aiType = null): Collection
    {
        $query = static::enabled()->with(['camera', 'camera.building', 'camera.nvr']);

        if ($aiType) {
            $query->byType($aiType);
        }

        return $query->get();
    }
}
