<?php

return [
    'default_theme' => env('DEFAULT_THEME', 'christmas'),
    'retention_days' => (int) env('ROOM_RETENTION_DAYS', 90),
    'poll_interval' => (int) env('POLL_INTERVAL_MS', 4000),
    'max_photo_kb' => (int) env('MAX_PHOTO_KB', 12288),
];
