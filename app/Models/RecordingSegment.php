<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordingSegment extends Model
{
    protected $fillable = [
        'camera_id',
        'start_time',
        'end_time',
        'file_path',
        'duration_seconds',
        'file_size',
        'status',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_seconds' => 'integer',
            'file_size' => 'integer',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForCamera($query, int $cameraId)
    {
        return $query->where('camera_id', $cameraId);
    }

    /**
     * Segments that overlap with a given time range.
     */
    public function scopeInRange($query, string $from, string $to)
    {
        return $query->where('start_time', '<', $to)
                     ->where(function ($q) use ($from) {
                         $q->where('end_time', '>', $from)
                           ->orWhereNull('end_time');
                     });
    }

    /**
     * Segments for a specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function getFullPath(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    public function getPublicUrl(): string
    {
        // file_path is relative to storage/app, e.g. recordings/257/2026/04/22/080000.mp4
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        if (! $bytes) return '-';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
