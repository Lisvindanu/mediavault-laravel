<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analytics;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Get analytics summary
     */
    public function summary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = $request->user();

        $analytics = Analytics::where('user_id', $user->id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->get();

        $totalDownloads = $analytics->sum('total_downloads');
        $totalWatchTimeSeconds = $analytics->sum('total_watch_time_seconds');
        $uniqueMediaWatched = $analytics->sum('unique_media_watched');

        // Get most watched category
        $categoryCounts = $analytics->pluck('most_watched_category')
            ->filter()
            ->countBy()
            ->sortDesc();
        $mostWatchedCategory = $categoryCounts->keys()->first();

        // Daily breakdown
        $dailyBreakdown = $analytics->map(function ($item) {
            return [
                'date' => $item->date->format('Y-m-d'),
                'downloads' => $item->total_downloads,
                'watch_time_seconds' => $item->total_watch_time_seconds,
            ];
        })->toArray();

        return response()->json([
            'total_downloads' => $totalDownloads,
            'total_watch_time_hours' => round($totalWatchTimeSeconds / 3600, 2),
            'most_watched_category' => $mostWatchedCategory,
            'unique_media_watched' => $uniqueMediaWatched,
            'daily_breakdown' => $dailyBreakdown,
        ]);
    }
}
