# YouTube Stream Proxy Implementation

## Overview

MediaVault backend menggunakan stream proxy untuk mengatasi masalah **IP-locking** pada YouTube stream URLs. Ketika yt-dlp mengekstrak URL dari YouTube, URL tersebut terikat ke IP address yang melakukan request (VPS). Jika Android client dengan IP berbeda mencoba mengakses URL tersebut, YouTube akan menolak dengan HTTP 403 error.

## Problem Statement

### Before Stream Proxy
1. VPS extract YouTube URL menggunakan yt-dlp
2. URL yang dihasilkan memiliki parameter `ip=VPS_IP_ADDRESS`
3. Backend return URL ke Android client
4. Android client (dengan IP berbeda) mencoba akses URL
5. **YouTube reject dengan HTTP 403 Forbidden**

### Root Cause
YouTube membuat URL yang **IP-locked** untuk mencegah URL sharing dan abuse. URL hanya valid untuk IP address yang melakukan ekstraksi.

## Solution: Stream Proxy

Backend bertindak sebagai proxy untuk streaming video dari YouTube ke Android client.

### Architecture

```
Android Client → Backend Proxy → YouTube
```

**Flow:**
1. Android request ke `/api/youtube/proxy/{videoId}`
2. Backend extract URL menggunakan yt-dlp (from cache or fresh)
3. Backend fetch stream dari YouTube menggunakan URL
4. Backend forward stream chunks ke Android client
5. Android client receive video tanpa 403 error

## Implementation Details

### 1. Endpoint: Stream Proxy

**URL:** `GET /api/youtube/proxy/{videoId}`

**Parameters:**
- `quality` (optional): Video quality preference (default: "best")

**Headers Required:**
- `Authorization: Bearer {token}`

**Example Request:**
```bash
GET https://mediavault.project-n.site/api/youtube/proxy/gdauR-YQkAQ?quality=best
Authorization: Bearer {your_token}
```

### 2. Controller Implementation

Location: `app/Http/Controllers/Api/YouTubeController.php`

```php
public function proxy(Request $request, string $id)
{
    // 1. Extract stream URL (from cache or fresh)
    $result = $this->youtubeService->extractStreamUrl($id, $quality, false);

    // 2. Open stream from YouTube
    $stream = fopen($streamUrl, 'rb', false, $context);

    // 3. Forward response headers
    // Content-Type, Content-Length, Content-Range, Accept-Ranges

    // 4. Stream chunks to client
    return response()->stream(function () use ($stream) {
        while (!feof($stream)) {
            echo fread($stream, 8192); // 8KB chunks
            flush();
        }
        fclose($stream);
    }, $statusCode, $responseHeaders);
}
```

### 3. HTTP Range Request Support

Stream proxy mendukung **seeking** dalam video dengan meneruskan HTTP Range headers:

**Client Request:**
```
GET /api/youtube/proxy/{videoId}
Range: bytes=1000000-2000000
```

**Backend Forward to YouTube:**
```
GET {youtube_url}
Range: bytes=1000000-2000000
```

**Response:**
```
HTTP/1.1 206 Partial Content
Content-Range: bytes 1000000-2000000/10829590
Content-Length: 1000001
```

## Features

### ✅ IP-Lock Bypass
URL extraction dan playback dilakukan dari IP yang sama (VPS), sehingga tidak ada IP-mismatch.

### ✅ Smart Caching
Backend cache stream URLs selama 6 jam. Cache otomatis di-invalidate saat:
- URL expiration < 30 menit
- Client request dengan parameter `refresh=true`

### ✅ Bandwidth Efficient
Stream menggunakan chunked transfer (8KB per chunk) dengan flush setelah setiap chunk, sehingga:
- Tidak load seluruh video ke memory
- Support video streaming real-time
- Client bisa seek tanpa download full video

### ✅ ExoPlayer Compatible
Response headers yang di-forward kompatibel dengan ExoPlayer:
- `Content-Type: video/mp4`
- `Content-Length: {filesize}`
- `Accept-Ranges: bytes`
- `Content-Range: bytes {start}-{end}/{total}` (untuk partial content)

## Cache Management

### Cache Strategy

```php
// Check if cached URL is still valid
$cached = Cache::get($cacheKey);
if ($cached && isset($cached['expires_at'])) {
    $timeRemaining = $cached['expires_at'] - now()->timestamp;
    if ($timeRemaining > 1800) { // > 30 minutes
        return $cached; // Use cache
    }
    Cache::forget($cacheKey); // Expired, invalidate
}
```

### Force Refresh

Client bisa force regenerate URL dengan parameter `refresh=true`:

```
GET /api/youtube/stream/{videoId}?quality=best&refresh=true
```

Backend akan:
1. Delete cache untuk video tersebut
2. Extract fresh URL dari yt-dlp
3. Return new URL dan cache untuk 6 jam

## YouTube Format Details

### Format 18 (360p Combined)

Backend menggunakan **format 18** dari YouTube:

**Specifications:**
- Resolution: 640x360 (360p)
- Video Codec: H.264 (avc1.42001E)
- Audio Codec: AAC (mp4a.40.2)
- Container: MP4
- Type: Combined (video + audio dalam 1 file)

**Advantages:**
- ✅ Single file (tidak perlu merge video+audio)
- ✅ Compatible dengan semua device
- ✅ Filesize optimal (~1-15MB per menit)
- ✅ Tidak butuh DASH manifest

## Android Client Usage

### Option 1: Direct Proxy (Recommended)

```kotlin
val videoId = "gdauR-YQkAQ"
val token = "Bearer your_token"

val proxyUrl = "https://mediavault.project-n.site/api/youtube/proxy/$videoId?quality=best"

val mediaItem = MediaItem.Builder()
    .setUri(proxyUrl)
    .setHttpRequestHeaders(mapOf("Authorization" to token))
    .build()

player.setMediaItem(mediaItem)
player.prepare()
player.play()
```

### Option 2: Fallback on 403

```kotlin
player.addListener(object : Player.Listener {
    override fun onPlayerError(error: PlaybackException) {
        if (error.errorCode == PlaybackException.ERROR_CODE_IO_BAD_HTTP_STATUS) {
            // Fallback to proxy
            val proxyUrl = "https://mediavault.project-n.site/api/youtube/proxy/$videoId"
            player.setMediaItem(
                MediaItem.Builder()
                    .setUri(proxyUrl)
                    .setHttpRequestHeaders(mapOf("Authorization" to token))
                    .build()
            )
            player.prepare()
            player.play()
        }
    }
})
```

## Performance Considerations

### Bandwidth Usage

**Direct URL (Before):**
```
Android ←→ YouTube (direct connection)
```
- Bandwidth: YouTube CDN only
- Cost: Free

**Proxied Stream (After):**
```
Android ←→ VPS ←→ YouTube
```
- Bandwidth: VPS → YouTube + VPS → Android (2x)
- Cost: VPS bandwidth usage

### Optimization Tips

1. **Cache Aggressively**: 6 hour cache reduces yt-dlp calls
2. **Stream Chunking**: 8KB chunks prevents memory overflow
3. **CDN Caching**: Use Cloudflare or CDN for proxied streams
4. **Selective Proxy**: Only proxy on 403, use direct URL when possible

## Security

### Authentication

Semua requests ke proxy endpoint **require authentication**:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/youtube/proxy/{id}', [YouTubeController::class, 'proxy']);
});
```

### Rate Limiting

Consider adding rate limiting untuk prevent abuse:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/youtube/proxy/{id}', [YouTubeController::class, 'proxy']);
});
```

## Troubleshooting

### Issue 1: Still Getting 403

**Cause:** Cache returning old IP-locked URL

**Solution:**
```
GET /api/youtube/proxy/{videoId}?quality=best&refresh=true
```

### Issue 2: Slow Streaming

**Cause:** VPS bandwidth limitations

**Solutions:**
- Upgrade VPS bandwidth
- Use CDN caching
- Consider direct URL fallback for fast connections

### Issue 3: Seeking Not Working

**Cause:** Range headers not forwarded correctly

**Solution:** Verify `Accept-Ranges: bytes` header in response

## Future Improvements

### 1. Adaptive Bitrate Streaming (ABR)
- Support multiple quality levels
- Automatic quality switching based on bandwidth

### 2. CDN Integration
- Cache popular videos on CDN edge
- Reduce VPS bandwidth usage

### 3. Direct URL Preference
- Try direct URL first
- Fallback to proxy only on 403

### 4. Analytics
- Track bandwidth usage per user
- Monitor cache hit rate
- Alert on high bandwidth consumption

## API Endpoints Summary

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/youtube/stream/{id}` | GET | Return stream URL metadata |
| `/api/youtube/proxy/{id}` | GET | Proxy video stream (fixes 403) |
| `/api/youtube/search` | GET | Search YouTube videos |
| `/api/youtube/trending` | GET | Get trending videos |
| `/api/youtube/video/{id}` | GET | Get video metadata |
| `/api/youtube/channel/{id}` | GET | Get channel info |
| `/api/youtube/comments/{id}` | GET | Get video comments |

## Configuration

### YouTubeService Config

Location: `config/youtube.php`

```php
return [
    'cache_duration' => env('YOUTUBE_CACHE_DURATION', 360), // 6 hours
    'geo_bypass' => true,
    'geo_bypass_country' => 'US',
];
```

### yt-dlp Command

```bash
yt-dlp --dump-json --no-warnings --no-playlist \
       --geo-bypass --geo-bypass-country US \
       'https://www.youtube.com/watch?v={videoId}'
```

## Dependencies

- **yt-dlp**: YouTube stream extraction
- **Laravel 11**: Backend framework
- **Laravel Sanctum**: API authentication
- **PHP 8.2+**: Required for yt-dlp Process integration

## Changelog

### v1.0.0 (2026-02-11)
- ✅ Initial stream proxy implementation
- ✅ HTTP Range request support
- ✅ Smart caching with expiration
- ✅ Force refresh mechanism
- ✅ Format 18 (360p) support
- ✅ Geo-bypass for restricted videos

---

**Author:** Lisvindanu
**Last Updated:** 2026-02-11
**Status:** Production Ready
