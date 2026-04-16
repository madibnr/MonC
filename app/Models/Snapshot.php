<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Snapshot extends Model
{
    protected $fillable = [
        'user_id',
        'camera_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
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

    public function getUrl(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function getThumbnailUrl(): string
    {
        return $this->getUrl();
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
}
