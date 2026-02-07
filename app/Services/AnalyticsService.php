<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\User;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Process daily analytics for user
     */
    public function processDailyAnalytics(User $user, Carbon $date)
    {
        $analytics = Analytics::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
            ],
            [
                'total_downloads' => 0,
                'total_watch_time_seconds' => 0,
                'unique_media_watched' => 0,
                'most_watched_category' => null,
                'device_breakdown' => [],
            ]
        );

        return $analytics;
    }

    /**
     * Update analytics for media download
     */
    public function recordDownload(User $user)
    {
        $today = Carbon::today();
        $analytics = $this->processDailyAnalytics($user, $today);
        
        $analytics->increment('total_downloads');
    }

    /**
     * Update analytics for watch time
     */
    public function recordWatchTime(User $user, int $seconds, string $category)
    {
        $today = Carbon::today();
        $analytics = $this->processDailyAnalytics($user, $today);
        
        $analytics->increment('total_watch_time_seconds', $seconds);
        $analytics->increment('unique_media_watched');
        $analytics->most_watched_category = $category;
        $analytics->save();
    }
}
