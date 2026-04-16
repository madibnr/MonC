<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClipExport extends Model
{
    protected $fillable = [
        'user_id',
        'camera_id',
        'clip_date',
        'start_time',
        'end_time',
        'file_path',
        'file_name',
        'file_size',
        'format',
        'status',
        'progress',
        'pid',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'clip_date' => 'date',
            'file_size' => 'integer',
            'progress' => 'integer',
            'pid' => 'integer',
            'completed_at' => 'datetime',
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

    // ── Helpers ──────────────────────────────────────────────────────

    public function getDownloadUrl(): ?string
    {
        if ($this->status !== 'completed' || ! $this->file_path) {
            return null;
        }

        return asset('storage/'.$this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        if (! $this->file_size) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function getDurationMinutes(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $end->diffInMinutes($start);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
