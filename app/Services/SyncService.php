<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Playlist;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncService
{
    /**
     * Process media sync from mobile device
     */
    public function syncMedia(User $user, array $mediaItems, array $deletedIds, string $deviceId, int $timestamp)
    {
        $syncedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();

        try {
            // Process media items
            foreach ($mediaItems as $item) {
                try {
                    Media::updateOrCreate(
                        [
                            'id' => $item['id'],
                            'user_id' => $user->id,
                        ],
                        $item
                    );
                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            // Process deletions
            if (count($deletedIds) > 0) {
                Media::whereIn('id', $deletedIds)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // Log sync
            SyncLog::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'sync_type' => 'incremental',
                'items_synced' => $syncedCount,
                'status' => $failedCount > 0 ? 'partial' : 'success',
                'error_details' => $failedCount > 0 ? ['failed_count' => $failedCount] : null,
            ]);

            DB::commit();

            return [
                'synced_count' => $syncedCount,
                'failed_count' => $failedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get server updates for device
     */
    public function getServerUpdates(User $user, int $lastSyncTimestamp)
    {
        return Media::where('user_id', $user->id)
            ->where('updated_at', '>', date('Y-m-d H:i:s', $lastSyncTimestamp / 1000))
            ->get()
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'title' => $media->title,
                    'updated_at' => $media->updated_at->timestamp * 1000,
                ];
            })
            ->toArray();
    }
}
