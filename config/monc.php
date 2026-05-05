<?php

return [
    /*
    |--------------------------------------------------------------------------
    | go2rtc Configuration
    |--------------------------------------------------------------------------
    | go2rtc is a zero-dependency streaming server bundled with MonC.
    | It handles RTSP → MSE/WebRTC transcoding with sub-second latency.
    | No FFmpeg installation required for live preview.
    */
    'go2rtc' => [
        'binary_path' => env('GO2RTC_BINARY_PATH', base_path('bin/go2rtc.exe')),
        'config_path' => env('GO2RTC_CONFIG_PATH', base_path('bin/go2rtc.yaml')),
        'api_url' => env('GO2RTC_API_URL', 'http://127.0.0.1:1984'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration (used for snapshots, exports, AI frame capture)
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
        'default_transport' => env('RTSP_TRANSPORT', 'tcp'),
        'use_sub_stream' => env('USE_SUB_STREAM', true),
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
    | Recording Configuration
    |--------------------------------------------------------------------------
    */
    'recording' => [
        'enabled' => env('RECORDING_ENABLED', false),
        'segment_duration' => env('RECORDING_SEGMENT_DURATION', 30), // seconds
        'retention_days' => env('RECORDING_RETENTION_DAYS', 30),
        'output_path' => env('RECORDING_OUTPUT_PATH', storage_path('app/recordings')),
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
        'default_confidence_threshold' => env('AI_DEFAULT_CONFIDENCE', 50),
        'default_detection_interval' => env('AI_DEFAULT_INTERVAL', 5), // seconds
        'frame_output_path' => env('AI_FRAME_PATH', storage_path('app/public/ai_frames')),
        'detection_output_path' => env('AI_DETECTION_PATH', storage_path('app/public/ai_detections')),
        'max_frame_age_hours' => env('AI_MAX_FRAME_AGE', 1), // cleanup frames older than this
    ],
];
