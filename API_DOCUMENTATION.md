# MediaVault API Documentation

Base URL: `https://api.mediavault.yourdomain.com/api`

## Authentication

All protected endpoints require `Authorization: Bearer {token}` header.

### Register

**POST** `/register`

Request:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "device_id": "unique-device-id"
}
```

Response (201):
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "1|abcdef123456..."
}
```

### Login

**POST** `/login`

Request:
```json
{
  "email": "john@example.com",
  "password": "SecurePass123",
  "device_id": "unique-device-id"
}
```

Response (200):
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "2|ghijkl789012..."
}
```

### Logout

**POST** `/logout`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "message": "Logged out successfully"
}
```

---

## Media Endpoints

### Get All Media

**GET** `/media`

Headers: `Authorization: Bearer {token}`

Query Parameters:
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20)
- `category` (optional): Filter by category
- `search` (optional): Search in title/description

Response (200):
```json
{
  "data": [
    {
      "id": "uuid-1",
      "url": "https://youtube.com/watch?v=abc123",
      "title": "Video Title",
      "description": "Description",
      "thumbnail_url": "https://...",
      "duration_seconds": 600,
      "category": "tutorial",
      "source_platform": "youtube",
      "quality": "720p",
      "tags": ["coding", "tutorial"],
      "is_favorite": false,
      "playback_speed": 1.0,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Sync Media

**POST** `/media/sync`

Headers: `Authorization: Bearer {token}`

Request:
```json
{
  "device_id": "unique-device-id",
  "sync_timestamp": 1704067200000,
  "media_items": [
    {
      "id": "uuid-1",
      "url": "https://youtube.com/watch?v=abc123",
      "title": "Video Title",
      "description": "Description",
      "thumbnail_url": "https://...",
      "duration_seconds": 600,
      "category": "tutorial",
      "source_platform": "youtube",
      "quality": "720p",
      "tags": ["coding", "tutorial"],
      "is_favorite": false,
      "playback_speed": 1.0
    }
  ],
  "deleted_ids": ["uuid-to-delete-1", "uuid-to-delete-2"]
}
```

Response (200):
```json
{
  "synced_count": 1,
  "failed_count": 0,
  "server_updates": [
    {
      "id": "uuid-2",
      "title": "New video from other device",
      "updated_at": 1704060000000
    }
  ]
}
```

### Get Single Media

**GET** `/media/{id}`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "data": {
    "id": "uuid-1",
    "url": "https://youtube.com/watch?v=abc123",
    "title": "Video Title",
    ...
  }
}
```

### Update Media

**PUT** `/media/{id}`

Headers: `Authorization: Bearer {token}`

Request:
```json
{
  "title": "Updated Title",
  "description": "Updated Description",
  "category": "entertainment",
  "tags": ["new", "tag"],
  "is_favorite": true,
  "playback_speed": 1.5
}
```

Response (200):
```json
{
  "data": {
    "id": "uuid-1",
    "title": "Updated Title",
    ...
  }
}
```

### Delete Media

**DELETE** `/media/{id}`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "message": "Media deleted successfully"
}
```

---

## Playlist Endpoints

### Get All Playlists

**GET** `/playlists`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "data": [
    {
      "id": "uuid-playlist-1",
      "name": "My Favorites",
      "description": "Best videos",
      "is_public": false,
      "item_count": 5,
      "total_duration_seconds": 3000,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Create Playlist

**POST** `/playlists`

Headers: `Authorization: Bearer {token}`

Request:
```json
{
  "name": "My Favorites",
  "description": "Best videos",
  "is_public": false
}
```

Response (201):
```json
{
  "data": {
    "id": "uuid-playlist-1",
    "name": "My Favorites",
    ...
  }
}
```

### Get Single Playlist

**GET** `/playlists/{id}`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "data": {
    "id": "uuid-playlist-1",
    "name": "My Favorites",
    ...
  }
}
```

### Update Playlist

**PUT** `/playlists/{id}`

Headers: `Authorization: Bearer {token}`

Request:
```json
{
  "name": "Updated Name",
  "description": "Updated Description",
  "is_public": true
}
```

Response (200):
```json
{
  "data": {
    "id": "uuid-playlist-1",
    "name": "Updated Name",
    ...
  }
}
```

### Delete Playlist

**DELETE** `/playlists/{id}`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "message": "Playlist deleted successfully"
}
```

### Add Media to Playlist

**POST** `/playlists/{id}/media`

Headers: `Authorization: Bearer {token}`

Request:
```json
{
  "media_ids": ["uuid-1", "uuid-2"]
}
```

Response (200):
```json
{
  "message": "2 items added to playlist"
}
```

### Remove Media from Playlist

**DELETE** `/playlists/{id}/media/{mediaId}`

Headers: `Authorization: Bearer {token}`

Response (200):
```json
{
  "message": "Media removed from playlist"
}
```

---

## Analytics Endpoints

### Get Analytics Summary

**GET** `/analytics/summary`

Headers: `Authorization: Bearer {token}`

Query Parameters:
- `start_date` (required): Start date (YYYY-MM-DD)
- `end_date` (required): End date (YYYY-MM-DD)

Response (200):
```json
{
  "total_downloads": 150,
  "total_watch_time_hours": 48.5,
  "most_watched_category": "tutorial",
  "unique_media_watched": 75,
  "daily_breakdown": [
    {
      "date": "2024-01-01",
      "downloads": 5,
      "watch_time_seconds": 7200
    }
  ]
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "message": "Server Error",
  "error": "Error details..."
}
```

---

## Rate Limiting

- API endpoints: 60 requests per minute
- Sync endpoint: 10 requests per hour

Rate limit headers:
- `X-RateLimit-Limit`: Total requests allowed
- `X-RateLimit-Remaining`: Requests remaining
- `Retry-After`: Seconds until reset (when limited)

---

## Categories

Allowed values:
- `music`
- `podcast`
- `tutorial`
- `entertainment`
- `documentary`
- `sports`
- `news`
- `uncategorized`

## Source Platforms

Allowed values:
- `youtube`
- `soundcloud`
- `vimeo`
