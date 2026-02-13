<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

class YouTubeController extends Controller
{
    private YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Search for videos
     * 
     * @group YouTube API
     * @queryParam q string required Search query. Example: king gnu nekko
     * @queryParam page integer Page number for pagination. Example: 1
     * 
     * @response {
     *   "success": true,
     *   "data": [{
     *     "type": "video",
     *     "title": "King Gnu - Nekko",
     *     "videoId": "jz8O2t2r8Gs",
     *     "author": "King Gnu official",
     *     "lengthSeconds": 247
     *   }],
     *   "source": "invidious"
     * }
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|max:255',
            'page' => 'sometimes|integer|min:1|max:10',
        ]);

        $query = $validated['q'];
        $page = $validated['page'] ?? 1;

        $result = $this->youtubeService->search($query, $page);

        if (!$result['success']) {
            return response()->json($result, $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    /**
     * Get trending videos
     * 
     * @group YouTube API
     * @queryParam region string Region code (US, JP, etc). Example: JP
     * @queryParam type string Trending type (default, music, gaming, movies). Example: music
     * 
     * @response {
     *   "success": true,
     *   "data": [{
     *     "type": "video",
     *     "title": "Trending Video",
     *     "videoId": "abc123",
     *     "author": "Channel Name"
     *   }],
     *   "source": "invidious"
     * }
     */
    public function trending(Request $request)
    {
        $validated = $request->validate([
            'region' => 'sometimes|string|size:2',
            'type' => 'sometimes|string|in:default,music,gaming,movies',
        ]);

        $region = $validated['region'] ?? 'US';
        $type = $validated['type'] ?? 'default';

        $result = $this->youtubeService->trending($region, $type);

        if (!$result['success']) {
            return response()->json($result, $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    /**
     * Get video metadata
     * 
     * @group YouTube API
     * @urlParam id string required Video ID. Example: jz8O2t2r8Gs
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "videoId": "jz8O2t2r8Gs",
     *     "title": "King Gnu - Nekko",
     *     "author": "King Gnu official",
     *     "lengthSeconds": 247,
     *     "viewCount": 1000000,
     *     "likeCount": 50000,
     *     "description": "Official video"
     *   },
     *   "source": "invidious"
     * }
     */
    public function video(Request $request, string $id)
    {
        $result = $this->youtubeService->getVideoMetadata($id);

        if (!$result['success']) {
            return response()->json($result, $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    /**
     * Extract video stream URLs
     * 
     * @group YouTube API
     * @urlParam id string required Video ID. Example: jz8O2t2r8Gs
     * @queryParam quality string Video quality preference (best, 720p, 480p, etc). Example: best
     * 
     * @response {
     *   "success": true,
     *   "video_id": "jz8O2t2r8Gs",
     *   "title": "King Gnu - Nekko",
     *   "duration": 247,
     *   "streams": {
     *     "video": [{
     *       "url": "https://...",
     *       "quality": "720p",
     *       "format_id": "22",
     *       "ext": "mp4"
     *     }],
     *     "audio": [{
     *       "url": "https://...",
     *       "quality": "128kbps",
     *       "format_id": "140",
     *       "ext": "m4a"
     *     }]
     *   },
     *   "source": "yt-dlp",
     *   "expires_at": 1707123456
     * }
     * 
     * @response status=500 scenario="Server extraction failed" {
     *   "success": false,
     *   "extract_on_device": true,
     *   "video_id": "jz8O2t2r8Gs",
     *   "message": "Server extraction failed. Please extract on device using NewPipe Extractor."
     * }
     */
    public function stream(Request $request, string $id)
    {
        $validated = $request->validate([
            'quality' => 'sometimes|string|max:20',
            'refresh' => 'sometimes|in:true,false,1,0',
        ]);

        $quality = $validated['quality'] ?? 'best';
        
        // Convert refresh parameter to boolean
        $forceRefresh = false;
        if (isset($validated['refresh'])) {
            $forceRefresh = in_array($validated['refresh'], ['true', '1', 1, true], true);
        }

        $result = $this->youtubeService->extractStreamUrl($id, $quality, $forceRefresh);

        if (!$result['success']) {
            // Return 200 with extract_on_device flag instead of error
            // This tells Android app to use local NewPipe Extractor
            return response()->json($result, 200);
        }

        return response()->json($result);
    }

    /**
     * Get channel information
     * 
     * @group YouTube API
     * @urlParam id string required Channel ID. Example: UCkB8HnJSDSJ2hkLQFUc-YrQ
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "author": "King Gnu official",
     *     "authorId": "UCkB8HnJSDSJ2hkLQFUc-YrQ",
     *     "authorUrl": "https://www.youtube.com/channel/...",
     *     "subCount": 500000,
     *     "description": "Official channel",
     *     "latestVideos": []
     *   },
     *   "source": "invidious"
     * }
     */
    public function channel(Request $request, string $id)
    {
        $result = $this->youtubeService->getChannel($id);

        if (!$result['success']) {
            return response()->json($result, $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    /**
     * Get video comments
     * 
     * @group YouTube API
     * @urlParam id string required Video ID. Example: jz8O2t2r8Gs
     * @queryParam continuation string Continuation token for pagination. Example: abc123
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "comments": [{
     *       "author": "User Name",
     *       "commentText": "Great video!",
     *       "likeCount": 100,
     *       "commentedTime": "1 day ago"
     *     }],
     *     "continuation": "token_for_next_page"
     *   },
     *   "source": "invidious"
     * }
     */
    public function comments(Request $request, string $id)
    {
        $validated = $request->validate([
            'continuation' => 'sometimes|string',
        ]);

        $continuation = $validated['continuation'] ?? null;

        $result = $this->youtubeService->getComments($id, $continuation);

        if (!$result['success']) {
            return response()->json($result, $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }
    /**
     * Proxy video stream to avoid IP-locking issues
     * 
     * @group YouTube API
     * @urlParam id string required Video ID. Example: jz8O2t2r8Gs
     * @queryParam quality string Video quality preference (best, 720p, 480p, etc). Example: best
     */
    /**
     * Proxy video stream to avoid IP-locking issues
     */
    public function proxy(Request $request, string $id)
    {
        $validated = $request->validate([
            'quality' => 'sometimes|string|max:20',
        ]);

        $quality = $validated['quality'] ?? 'best';

        // Get stream URL from cache or extract fresh
        $result = $this->youtubeService->extractStreamUrl($id, $quality, false);

        if (!$result['success'] || !isset($result['format_18']['url'])) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to extract stream URL',
            ], 500);
        }

        $streamUrl = $result['format_18']['url'];

        // Get Range header from client if exists
        $rangeHeader = $request->header('Range');
        
        // Initialize stream context with headers
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => $rangeHeader ? "Range: $rangeHeader\r\n" : '',
                'follow_location' => 1,
            ]
        ];
        
        $context = stream_context_create($opts);
        
        // Open stream from YouTube
        $stream = @fopen($streamUrl, 'rb', false, $context);
        
        if (!$stream) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to open stream',
            ], 500);
        }

        // Get response headers from YouTube
        $metadata = stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];
        
        // Parse headers and forward relevant ones
        $responseHeaders = [];
        $statusCode = 200;
        
        foreach ($headers as $header) {
            if (stripos($header, 'HTTP/') === 0) {
                // Extract status code
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $statusCode = (int)$matches[1];
                }
            } elseif (stripos($header, ':') !== false) {
                list($name, $value) = explode(':', $header, 2);
                $name = strtolower(trim($name));
                $value = trim($value);
                
                // Forward important headers
                if (in_array($name, ['content-type', 'content-length', 'content-range', 'accept-ranges'])) {
                    $responseHeaders[$name] = $value;
                }
            }
        }

        // Stream response back to client
        return response()->stream(
            function () use ($stream) {
                while (!feof($stream)) {
                    $chunk = fread($stream, 8192);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    flush();
                }
                fclose($stream);
            },
            $statusCode,
            $responseHeaders
        );
    }
}
