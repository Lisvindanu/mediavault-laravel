<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaylistStoreRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $playlists = Playlist::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return PlaylistResource::collection($playlists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlaylistStoreRequest $request)
    {
        $playlist = Playlist::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_public' => $request->is_public ?? false,
        ]);

        return new PlaylistResource($playlist);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $playlist = Playlist::where('user_id', $request->user()->id)
            ->with('media')
            ->findOrFail($id);

        return new PlaylistResource($playlist);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $playlist = Playlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $playlist->update($request->only(['name', 'description', 'is_public']));

        return new PlaylistResource($playlist);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $playlist = Playlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $playlist->delete();

        return response()->json([
            'message' => 'Playlist deleted successfully',
        ]);
    }

    /**
     * Add media to playlist
     */
    public function addMedia(Request $request, string $id)
    {
        $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'required|string|exists:media,id',
        ]);

        $playlist = Playlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $currentMaxPosition = $playlist->media()->max('position') ?? -1;

        foreach ($request->media_ids as $index => $mediaId) {
            $playlist->media()->syncWithoutDetaching([
                $mediaId => ['position' => $currentMaxPosition + $index + 1]
            ]);
        }

        // Update item count and total duration
        $playlist->item_count = $playlist->media()->count();
        $playlist->total_duration_seconds = $playlist->media()->sum('duration_seconds');
        $playlist->save();

        return response()->json([
            'message' => count($request->media_ids) . ' items added to playlist',
        ]);
    }

    /**
     * Remove media from playlist
     */
    public function removeMedia(Request $request, string $id, string $mediaId)
    {
        $playlist = Playlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $playlist->media()->detach($mediaId);

        // Update item count and total duration
        $playlist->item_count = $playlist->media()->count();
        $playlist->total_duration_seconds = $playlist->media()->sum('duration_seconds');
        $playlist->save();

        return response()->json([
            'message' => 'Media removed from playlist',
        ]);
    }
}
