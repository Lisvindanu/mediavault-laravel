<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    use HasFactory;

    protected $table = 'watch_history';

    protected $fillable = [
        'user_id',
        'media_id',
        'watch_progress_seconds',
        'is_completed',
        'watched_at',
        'device_id',
    ];

    protected $casts = [
        'watch_progress_seconds' => 'integer',
        'is_completed' => 'boolean',
        'watched_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
