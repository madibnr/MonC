<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nvr extends Model
{
    use HasFactory;

    protected $table = 'nvrs';

    protected $fillable = [
        'building_id',
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'model',
        'total_channels',
        'status',
        'last_seen_at',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'username',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'port' => 'integer',
            'total_channels' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(NvrHealthLog::class);
    }

    public function latestHealth()
    {
        return $this->hasOne(NvrHealthLog::class)->latestOfMany();
    }

    // ── Accessors ────────────────────────────────────────────────────

    public function getStreamBaseUrlAttribute(): string
    {
        return "rtsp://{$this->username}:{$this->password}@{$this->ip_address}:{$this->port}";
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
}
