<?php

return [
    'fail_threshold' => (int) env('FAIL_THRESHOLD', 3),
    'slow_threshold_ms' => (int) env('SLOW_THRESHOLD_MS', 3000),
    'check_interval_minutes' => (int) env('CHECK_INTERVAL_MINUTES', 1),
    'digest_enabled' => filter_var(env('DIGEST_ENABLED', true), FILTER_VALIDATE_BOOL),
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'token_hours' => 8,
];
