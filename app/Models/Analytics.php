<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'total_downloads',
        'total_watch_time_seconds',
        'unique_media_watched',
        'most_watched_category',
        'device_breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'total_downloads' => 'integer',
        'total_watch_time_seconds' => 'integer',
        'unique_media_watched' => 'integer',
        'device_breakdown' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
