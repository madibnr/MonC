<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NvrHealthLog extends Model
{
    protected $fillable = [
        'nvr_id',
        'hdd_total_bytes',
        'hdd_used_bytes',
        'hdd_free_bytes',
        'hdd_usage_percent',
        'hdd_status',
        'is_recording',
        'recording_channels',
        'bandwidth_kbps',
        'cpu_usage_percent',
        'memory_usage_percent',
        'firmware_version',
        'uptime_hours',
        'overall_status',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'hdd_total_bytes' => 'integer',
            'hdd_used_bytes' => 'integer',
            'hdd_free_bytes' => 'integer',
            'hdd_usage_percent' => 'integer',
            'is_recording' => 'boolean',
            'recording_channels' => 'integer',
            'bandwidth_kbps' => 'integer',
            'cpu_usage_percent' => 'integer',
            'memory_usage_percent' => 'integer',
            'uptime_hours' => 'integer',
            'raw_data' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function nvr(): BelongsTo
    {
        return $this->belongsTo(Nvr::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function getHddTotalFormatted(): string
    {
        return $this->formatBytes($this->hdd_total_bytes);
    }

    public function getHddUsedFormatted(): string
    {
        return $this->formatBytes($this->hdd_used_bytes);
    }

    public function getHddFreeFormatted(): string
    {
        return $this->formatBytes($this->hdd_free_bytes);
    }

    public function getBandwidthFormatted(): string
    {
        if (! $this->bandwidth_kbps) {
            return '-';
        }
        if ($this->bandwidth_kbps >= 1024) {
            return round($this->bandwidth_kbps / 1024, 1).' Mbps';
        }

        return $this->bandwidth_kbps.' Kbps';
    }

    public function getStatusColor(): string
    {
        return match ($this->overall_status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            'unreachable' => 'slate',
            default => 'slate',
        };
    }

    protected function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        $size = $bytes;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeLatestPerNvr($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('nvr_health_logs')
                ->groupBy('nvr_id');
        });
    }

    public function scopeHealthy($query)
    {
        return $query->where('overall_status', 'healthy');
    }

    public function scopeCritical($query)
    {
        return $query->where('overall_status', 'critical');
    }
}
