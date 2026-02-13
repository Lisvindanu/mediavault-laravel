<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'id',
        'user_id',
        'url',
        'url_hash',
        'title',
        'description',
        'thumbnail_url',
        'duration_seconds',
        'category',
        'source_platform',
        'quality',
        'tags',
        'is_favorite',
        'playback_speed',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'tags' => 'array',
        'is_favorite' => 'boolean',
        'playback_speed' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_media')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }
    
    /**
     * Generate a new UUID for the model, but only if id is not already set
     */
    public function newUniqueId(): string
    {
        // If id is already set (from client), use it
        if (isset($this->attributes['id'])) {
            return $this->attributes['id'];
        }
        
        // Otherwise generate new UUID
        return (string) \Illuminate\Support\Str::uuid();
    }
}
