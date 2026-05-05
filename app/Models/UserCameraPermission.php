<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCameraPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'camera_id',
        'can_live_view',
        'can_playback',
        'can_export',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'can_live_view' => 'boolean',
            'can_playback' => 'boolean',
            'can_export' => 'boolean',
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

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
