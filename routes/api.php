<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\YouTubeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Media
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media/sync', [MediaController::class, 'sync']);
    Route::get('/media/{id}', [MediaController::class, 'show']);
    Route::put('/media/{id}', [MediaController::class, 'update']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);

    // Playlists
    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::post('/playlists', [PlaylistController::class, 'store']);
    Route::get('/playlists/{id}', [PlaylistController::class, 'show']);
    Route::put('/playlists/{id}', [PlaylistController::class, 'update']);
    Route::delete('/playlists/{id}', [PlaylistController::class, 'destroy']);
    Route::post('/playlists/{id}/media', [PlaylistController::class, 'addMedia']);
    Route::delete('/playlists/{id}/media/{mediaId}', [PlaylistController::class, 'removeMedia']);

    // Analytics
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
    
    // YouTube API Proxy
    Route::prefix('youtube')->group(function () {
        Route::get('/search', [YouTubeController::class, 'search']);
        Route::get('/trending', [YouTubeController::class, 'trending']);
        Route::get('/video/{id}', [YouTubeController::class, 'video']);
        Route::get('/stream/{id}', [YouTubeController::class, 'stream']);
        Route::get('/proxy/{id}', [YouTubeController::class, 'proxy']);
        Route::get('/channel/{id}', [YouTubeController::class, 'channel']);
        Route::get('/comments/{id}', [YouTubeController::class, 'comments']);
    });
});
