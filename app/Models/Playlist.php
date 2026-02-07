<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Playlist extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
        'item_count',
        'total_duration_seconds',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'item_count' => 'integer',
        'total_duration_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'playlist_media')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }
}
