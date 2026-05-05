<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\RecordingSegment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlaybackService
{
    /**
     * Get all completed segments for a camera on a given date.
     * Returns ordered collection with gap information.
     */
    public function getSegmentsForDate(int $cameraId, string $date): Collection
    {
        return RecordingSegment::forCamera($cameraId)
            ->forDate($date)
            ->completed()
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get segments that overlap with a time range.
     */
    public function getSegmentsByRange(int $cameraId, string $from, string $to): Collection
    {
        return RecordingSegment::forCamera($cameraId)
            ->inRange($from, $to)
            ->completed()
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Resolve the correct segment for a given timestamp.
     * Returns the segment containing that timestamp, or the next available one.
     */
    public function resolveSegmentByTime(int $cameraId, string $timestamp): ?RecordingSegment
    {
        $ts = Carbon::parse($timestamp);

        // 1. Exact match: segment that contains this timestamp
        $segment = RecordingSegment::forCamera($cameraId)
            ->completed()
            ->where('start_time', '<=', $ts)
            ->where('end_time', '>', $ts)
            ->first();

        if ($segment) {
            return $segment;
        }

        // 2. No exact match: find the next available segment after this timestamp
        return RecordingSegment::forCamera($cameraId)
            ->completed()
            ->where('start_time', '>=', $ts)
            ->orderBy('start_time')
            ->first();
    }

    /**
     * Get the next segment after a given segment (for seamless playback).
     */
    public function getNextSegment(RecordingSegment $current): ?RecordingSegment
    {
        return RecordingSegment::forCamera($current->camera_id)
            ->completed()
            ->where('start_time', '>=', $current->end_time)
            ->orderBy('start_time')
            ->first();
    }

    /**
     * Build a timeline summary for a camera on a date.
     * Returns array of {start, end, segments} blocks for the timeline UI.
     */
    public function buildTimeline(int $cameraId, string $date): array
    {
        $segments = $this->getSegmentsForDate($cameraId, $date);

        if ($segments->isEmpty()) {
            return [];
        }

        // Merge adjacent/overlapping segments into continuous blocks
        $blocks = [];
        $currentBlock = null;

        foreach ($segments as $segment) {
            if (! $currentBlock) {
                $currentBlock = [
                    'start' => $segment->start_time->toIso8601String(),
                    'end'   => $segment->end_time->toIso8601String(),
                    'count' => 1,
                ];
                continue;
            }

            $blockEnd = Carbon::parse($currentBlock['end']);
            // If this segment starts within 5 seconds of the previous block end, merge
            if ($segment->start_time->diffInSeconds($blockEnd) <= 5) {
                $currentBlock['end'] = $segment->end_time->toIso8601String();
                $currentBlock['count']++;
            } else {
                $blocks[] = $currentBlock;
                $currentBlock = [
                    'start' => $segment->start_time->toIso8601String(),
                    'end'   => $segment->end_time->toIso8601String(),
                    'count' => 1,
                ];
            }
        }

        if ($currentBlock) {
            $blocks[] = $currentBlock;
        }

        return $blocks;
    }

    /**
     * Calculate the seek offset within a segment for a given timestamp.
     * Returns seconds from segment start.
     */
    public function calculateSeekOffset(RecordingSegment $segment, string $timestamp): float
    {
        $ts = Carbon::parse($timestamp);
        return max(0, $segment->start_time->floatDiffInSeconds($ts));
    }

    /**
     * Get recording summary stats for a camera on a date.
     */
    public function getRecordingSummary(int $cameraId, string $date): array
    {
        $segments = $this->getSegmentsForDate($cameraId, $date);

        return [
            'total_segments'  => $segments->count(),
            'total_duration'  => $segments->sum('duration_seconds'),
            'total_size'      => $segments->sum('file_size'),
            'earliest'        => $segments->first()?->start_time?->toIso8601String(),
            'latest'          => $segments->last()?->end_time?->toIso8601String(),
        ];
    }
}
