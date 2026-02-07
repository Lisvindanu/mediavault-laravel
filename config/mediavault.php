<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MediaVault Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for MediaVault application
    |
    */

    'sync' => [
        'max_items_per_sync' => env('MEDIAVAULT_MAX_ITEMS_PER_SYNC', 1000),
        'sync_interval_minutes' => env('MEDIAVAULT_SYNC_INTERVAL', 360), // 6 hours
    ],

    'analytics' => [
        'enabled' => env('MEDIAVAULT_ANALYTICS_ENABLED', true),
        'retention_days' => env('MEDIAVAULT_ANALYTICS_RETENTION_DAYS', 365),
    ],

    'rate_limiting' => [
        'api_per_minute' => env('MEDIAVAULT_API_RATE_LIMIT', 60),
        'sync_per_hour' => env('MEDIAVAULT_SYNC_RATE_LIMIT', 10),
    ],

    'media' => [
        'allowed_platforms' => ['youtube', 'soundcloud', 'vimeo'],
        'allowed_categories' => [
            'music',
            'podcast',
            'tutorial',
            'entertainment',
            'documentary',
            'sports',
            'news',
            'uncategorized',
        ],
    ],
];
