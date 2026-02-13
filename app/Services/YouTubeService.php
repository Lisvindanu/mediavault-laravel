<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class YouTubeService
{
    private string $invidiousUrl;
    private int $searchCacheDuration = 3600; // 1 hour
    private int $videoCacheDuration = 21600; // 6 hours
    private int $streamCacheDuration = 21600; // 6 hours

    public function __construct()
    {
        $this->invidiousUrl = config('youtube.invidious_url', 'https://piped.project-n.site');
    }

    /**
     * Search for videos
     */
    public function search(string $query, int $page = 1): array
    {
        $cacheKey = "youtube:search:" . md5($query . $page);

        return Cache::remember($cacheKey, $this->searchCacheDuration, function () use ($query, $page) {
            try {
                $response = Http::timeout(10)->get($this->invidiousUrl . '/api/v1/search', [
                    'q' => $query,
                    'page' => $page,
                    'type' => 'video',
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'source' => 'invidious',
                    ];
                }

                Log::warning('Invidious search failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Search service unavailable',
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::error('YouTube search error', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Search failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get trending videos
     */
    public function trending(string $region = 'US', string $type = 'default'): array
    {
        $cacheKey = "youtube:trending:{$region}:{$type}";

        return Cache::remember($cacheKey, $this->searchCacheDuration, function () use ($region, $type) {
            try {
                $response = Http::timeout(10)->get($this->invidiousUrl . '/api/v1/trending', [
                    'region' => $region,
                    'type' => $type,
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'source' => 'invidious',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Trending service unavailable',
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::error('YouTube trending error', [
                    'region' => $region,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Trending failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get video metadata (without streams)
     */
    public function getVideoMetadata(string $videoId): array
    {
        $cacheKey = "youtube:video:metadata:{$videoId}";

        return Cache::remember($cacheKey, $this->videoCacheDuration, function () use ($videoId) {
            try {
                $response = Http::timeout(10)->get($this->invidiousUrl . "/api/v1/videos/{$videoId}", [
                    'fields' => 'title,videoId,author,authorId,lengthSeconds,description,descriptionHtml,viewCount,published,publishedText,likeCount,dislikeCount,videoThumbnails',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['error'])) {
                        unset($data['error']);
                    }

                    return [
                        'success' => true,
                        'data' => $data,
                        'source' => 'invidious',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Video metadata unavailable',
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::error('YouTube video metadata error', [
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Video metadata failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Extract stream URL using yt-dlp
     */
    public function extractStreamUrl(string $videoId, ?string $quality = 'best', bool $forceRefresh = false): array
    {
        $cacheKey = "youtube:stream:{$videoId}:{$quality}";

        // If force refresh is requested, clear cache immediately
        if ($forceRefresh) {
            Log::info('Force refresh requested, clearing cache', ['video_id' => $videoId]);
            Cache::forget($cacheKey);
        }

        // Check if cached data exists and is still valid (only if not forcing refresh)
        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if ($cached && isset($cached['expires_at'])) {
                $expiresAt = $cached['expires_at'];
                $timeRemaining = $expiresAt - now()->timestamp;
                if ($timeRemaining > 1800) { // More than 30 minutes remaining
                    Log::info('Using cached stream URL', ['video_id' => $videoId, 'time_remaining' => $timeRemaining]);
                    return $cached;
                }
                Cache::forget($cacheKey); // Expired or expiring soon, delete cache
            }
        }

        return Cache::remember($cacheKey, $this->streamCacheDuration, function () use ($videoId, $quality) {
            try {
                $result = $this->extractWithYtDlp($videoId, $quality);
                
                if ($result['success']) {
                    return $result;
                }

                Log::warning('yt-dlp extraction failed', [
                    'video_id' => $videoId,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'extract_on_device' => true,
                    'video_id' => $videoId,
                    'message' => 'Server extraction failed. Please extract on device using NewPipe Extractor.',
                    'error' => $result['error'] ?? 'Extraction failed',
                ];
            } catch (\Exception $e) {
                Log::error('Stream extraction error', [
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'extract_on_device' => true,
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Extract using yt-dlp - optimized for 480p video + best audio
     */
    private function extractWithYtDlp(string $videoId, ?string $quality): array
    {
        try {
            $url = "https://www.youtube.com/watch?v={$videoId}";
            
            $command = sprintf(
                'yt-dlp --dump-json --no-warnings --no-playlist --geo-bypass --geo-bypass-country US %s 2>&1',
                escapeshellarg($url)
            );

            $result = Process::timeout(30)->run($command);

            if (!$result->successful()) {
                return [
                    'success' => false,
                    'error' => 'yt-dlp execution failed: ' . $result->errorOutput(),
                ];
            }

            $output = $result->output();
            $data = json_decode($output, true);

            if (!is_array($data)) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from yt-dlp',
                ];
            }

            // Organize streams
            $format18 = null;

            if (isset($data['formats']) && is_array($data['formats'])) {
                foreach ($data['formats'] as $format) {
                    // Look for format 18 (360p combined video+audio)
                    if (($format['format_id'] ?? null) === '18') {
                        $format18 = [
                            'url' => $format['url'] ?? null,
                            'format_id' => $format['format_id'] ?? null,
                            'ext' => $format['ext'] ?? null,
                            'quality' => '360p',
                            'resolution' => ($format['width'] ?? 0) . 'x' . ($format['height'] ?? 0),
                            'width' => $format['width'] ?? null,
                            'height' => $format['height'] ?? null,
                            'fps' => $format['fps'] ?? null,
                            'filesize' => $format['filesize'] ?? $format['filesize_approx'] ?? null,
                            'tbr' => $format['tbr'] ?? null,
                            'vcodec' => $format['vcodec'] ?? null,
                            'acodec' => $format['acodec'] ?? null,
                            'abr' => $format['abr'] ?? null,
                        ];
                        break;
                    }
                }
            }

            return [
                'success' => true,
                'video_id' => $videoId,
                'title' => $data['title'] ?? null,
                'duration' => $data['duration'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'author' => $data['uploader'] ?? $data['channel'] ?? null,
                'description' => $data['description'] ?? null,
                'view_count' => $data['view_count'] ?? null,
                'format_18' => $format18,
                'source' => 'yt-dlp',
                'expires_at' => now()->addHours(6)->timestamp,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'yt-dlp error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get channel information
     */
    public function getChannel(string $channelId): array
    {
        $cacheKey = "youtube:channel:{$channelId}";

        return Cache::remember($cacheKey, $this->videoCacheDuration, function () use ($channelId) {
            try {
                $response = Http::timeout(10)->get($this->invidiousUrl . "/api/v1/channels/{$channelId}");

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'source' => 'invidious',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Channel unavailable',
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::error('YouTube channel error', [
                    'channel_id' => $channelId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Channel failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get video comments
     */
    public function getComments(string $videoId, ?string $continuation = null): array
    {
        $cacheKey = "youtube:comments:{$videoId}:" . md5($continuation ?? '');

        return Cache::remember($cacheKey, $this->searchCacheDuration, function () use ($videoId, $continuation) {
            try {
                $params = $continuation ? ['continuation' => $continuation] : [];
                $response = Http::timeout(10)->get(
                    $this->invidiousUrl . "/api/v1/comments/{$videoId}",
                    $params
                );

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'source' => 'invidious',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Comments unavailable',
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::error('YouTube comments error', [
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Comments failed: ' . $e->getMessage(),
                ];
            }
        });
    }
}
