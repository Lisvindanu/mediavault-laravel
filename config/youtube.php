<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invidious API URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Invidious instance for fetching YouTube metadata
    | (search, trending, channel info, comments, etc.)
    |
    */
    'invidious_url' => env('INVIDIOUS_URL', 'https://piped.project-n.site'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration (in seconds)
    |--------------------------------------------------------------------------
    |
    | How long to cache different types of YouTube data
    |
    */
    'cache' => [
        'search' => env('YOUTUBE_CACHE_SEARCH', 3600), // 1 hour
        'video' => env('YOUTUBE_CACHE_VIDEO', 21600), // 6 hours
        'stream' => env('YOUTUBE_CACHE_STREAM', 21600), // 6 hours (YouTube URLs expire after ~6h)
        'channel' => env('YOUTUBE_CACHE_CHANNEL', 21600), // 6 hours
        'comments' => env('YOUTUBE_CACHE_COMMENTS', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Stream Extraction Settings
    |--------------------------------------------------------------------------
    |
    | Settings for extracting direct stream URLs from YouTube
    |
    */
    'extraction' => [
        // Use yt-dlp for stream extraction (true) or always tell client to extract (false)
        'use_ytdlp' => env('YOUTUBE_USE_YTDLP', true),
        
        // Path to yt-dlp binary
        'ytdlp_path' => env('YOUTUBE_YTDLP_PATH', '/usr/local/bin/yt-dlp'),
        
        // Timeout for yt-dlp extraction (in seconds)
        'ytdlp_timeout' => env('YOUTUBE_YTDLP_TIMEOUT', 30),
        
        // Default quality preference
        'default_quality' => env('YOUTUBE_DEFAULT_QUALITY', 'best'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for YouTube API endpoints (requests per minute)
    |
    */
    'rate_limit' => [
        'search' => env('YOUTUBE_RATE_LIMIT_SEARCH', 20),
        'stream' => env('YOUTUBE_RATE_LIMIT_STREAM', 10),
        'default' => env('YOUTUBE_RATE_LIMIT_DEFAULT', 30),
    ],
];
