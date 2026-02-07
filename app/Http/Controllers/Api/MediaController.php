<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Media::where('user_id', $request->user()->id);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $media = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return MediaResource::collection($media);
    }

    /**
     * Sync media metadata from mobile to server
     */
    public function sync(SyncRequest $request)
    {
        $user = $request->user();
        $syncedCount = 0;
        $failedCount = 0;
        $serverUpdates = [];

        DB::beginTransaction();

        try {
            // Process incoming media items
            foreach ($request->media_items as $mediaData) {
                try {
                    Media::updateOrCreate(
                        [
                            'id' => $mediaData['id'],
                            'user_id' => $user->id,
                        ],
                        [
                            'url' => $mediaData['url'],
                            'title' => $mediaData['title'],
                            'description' => $mediaData['description'] ?? null,
                            'thumbnail_url' => $mediaData['thumbnail_url'] ?? null,
                            'duration_seconds' => $mediaData['duration_seconds'],
                            'category' => $mediaData['category'],
                            'source_platform' => $mediaData['source_platform'],
                            'quality' => $mediaData['quality'] ?? null,
                            'tags' => $mediaData['tags'] ?? [],
                            'is_favorite' => $mediaData['is_favorite'],
                            'playback_speed' => $mediaData['playback_speed'],
                        ]
                    );
                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            // Process deletions
            if (isset($request->deleted_ids) && count($request->deleted_ids) > 0) {
                Media::whereIn('id', $request->deleted_ids)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // Get server updates (media added from other devices)
            $lastSyncTimestamp = $request->sync_timestamp ?? 0;
            $serverUpdates = Media::where('user_id', $user->id)
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

            // Log sync
            SyncLog::create([
                'user_id' => $user->id,
                'device_id' => $request->device_id,
                'sync_type' => 'incremental',
                'items_synced' => $syncedCount,
                'status' => $failedCount > 0 ? 'partial' : 'success',
                'error_details' => $failedCount > 0 ? ['failed_count' => $failedCount] : null,
            ]);

            DB::commit();

            return response()->json([
                'synced_count' => $syncedCount,
                'failed_count' => $failedCount,
                'server_updates' => $serverUpdates,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $media = Media::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return new MediaResource($media);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $media = Media::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $media->update($request->only([
            'title',
            'description',
            'category',
            'tags',
            'is_favorite',
            'playback_speed',
        ]));

        return new MediaResource($media);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $media = Media::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }
}
