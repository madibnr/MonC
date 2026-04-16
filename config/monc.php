<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | Stream Configuration
    |--------------------------------------------------------------------------
    */
    'stream' => [
        'hls_time' => env('HLS_SEGMENT_TIME', 2),
        'hls_list_size' => env('HLS_LIST_SIZE', 10),
        'default_transport' => env('RTSP_TRANSPORT', 'tcp'),
        'use_sub_stream' => env('USE_SUB_STREAM', true),
        'output_path' => env('STREAM_OUTPUT_PATH', storage_path('app/public/streams')),
    ],

    /*
    |--------------------------------------------------------------------------
    | NVR Default Settings
    |--------------------------------------------------------------------------
    */
    'nvr' => [
        'default_port' => env('NVR_DEFAULT_PORT', 554),
        'default_channels' => env('NVR_DEFAULT_CHANNELS', 64),
        'stream_path_main' => '/Streaming/Channels/{channel}01',
        'stream_path_sub' => '/Streaming/Channels/{channel}02',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grid Layout Options
    |--------------------------------------------------------------------------
    */
    'grid_layouts' => [1, 4, 9, 16, 32, 64],

    /*
    |--------------------------------------------------------------------------
    | Session & Cleanup
    |--------------------------------------------------------------------------
    */
    'stream_cleanup_interval' => env('STREAM_CLEANUP_INTERVAL', 300), // seconds
    'max_idle_stream_time' => env('MAX_IDLE_STREAM_TIME', 600), // seconds

    /*
    |--------------------------------------------------------------------------
    | Telegram Integration
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    */
    'export' => [
        'output_path' => env('EXPORT_OUTPUT_PATH', storage_path('app/public/exports')),
        'max_duration_minutes' => env('EXPORT_MAX_DURATION', 60),
        'default_format' => 'mp4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot Configuration
    |--------------------------------------------------------------------------
    */
    'snapshot' => [
        'output_path' => env('SNAPSHOT_OUTPUT_PATH', storage_path('app/public/snapshots')),
        'quality' => env('SNAPSHOT_QUALITY', 2), // FFmpeg quality (1=best, 31=worst)
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring
    |--------------------------------------------------------------------------
    */
    'health' => [
        'camera_check_interval' => env('CAMERA_CHECK_INTERVAL', 600), // seconds
        'nvr_health_interval' => env('NVR_HEALTH_INTERVAL', 1800), // seconds
        'hdd_warning_threshold' => env('HDD_WARNING_THRESHOLD', 90), // percent
        'hdd_critical_threshold' => env('HDD_CRITICAL_THRESHOLD', 95), // percent
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'service_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8100'),
        'timeout' => env('AI_SERVICE_TIMEOUT', 30),
        'scheduler_interval' => env('AI_SCHEDULER_INTERVAL', 5), // seconds
        'default_confidence_threshold' => env('AI_DEFAULT_CONFIDENCE', 85),
        'default_detection_interval' => env('AI_DEFAULT_INTERVAL', 5), // seconds
        'frame_output_path' => env('AI_FRAME_PATH', storage_path('app/public/ai_frames')),
        'detection_output_path' => env('AI_DETECTION_PATH', storage_path('app/public/ai_detections')),
        'max_frame_age_hours' => env('AI_MAX_FRAME_AGE', 1), // cleanup frames older than this
    ],
];
