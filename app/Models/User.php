<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Role constants
    const ROLE_SUPERADMIN = 'superadmin';

    const ROLE_ADMIN_IT = 'admin_it';

    const ROLE_OPERATOR = 'operator';

    const ROLE_AUDITOR = 'auditor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'telegram_chat_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function cameraPermissions(): HasMany
    {
        return $this->hasMany(UserCameraPermission::class);
    }

    public function accessibleCameras(): BelongsToMany
    {
        return $this->belongsToMany(Camera::class, 'user_camera_permissions')
            ->withPivot('can_live_view', 'can_playback', 'can_export')
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
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

    public function alertSubscriptions(): HasMany
    {
        return $this->hasMany(AlertSubscription::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Role helpers ─────────────────────────────────────────────────

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdminIt(): bool
    {
        return $this->role === self::ROLE_ADMIN_IT;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isAuditor(): bool
    {
        return $this->role === self::ROLE_AUDITOR;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // ── Permission helpers ───────────────────────────────────────────

    public function canAccessCamera(int $cameraId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->cameraPermissions()
            ->where('camera_id', $cameraId)
            ->exists();
    }

    public function canLiveView(int $cameraId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->cameraPermissions()
            ->where('camera_id', $cameraId)
            ->where('can_live_view', true)
            ->exists();
    }

    public function canPlayback(int $cameraId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->cameraPermissions()
            ->where('camera_id', $cameraId)
            ->where('can_playback', true)
            ->exists();
    }

    public function canExport(int $cameraId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->cameraPermissions()
            ->where('camera_id', $cameraId)
            ->where('can_export', true)
            ->exists();
    }
}
