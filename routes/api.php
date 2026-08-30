<?php

use App\Http\Controllers\Api\AppAuthController;
use App\Http\Controllers\Api\AppUserController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\VideoFeedController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OverviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/homepage', [OverviewController::class, 'frontHomepage']);
Route::get('/search/{term}', [OverviewController::class, 'searchAudiosAndFile']);

Route::get('/article/{slug}', [ArticleController::class, 'getArticleBySlug']);
Route::get('/articles/page/{page}', [ArticleController::class, 'paginateArticle'])->whereNumber('page');
Route::get('/articles/sitemap', [ArticleController::class, 'sitemap']);

Route::get('/file/{slug}', [FileController::class, 'getFileBySlug']);
Route::get('/files/page/{page}', [FileController::class, 'paginateFiles'])->whereNumber('page');
Route::get('/files/sitemap', [FileController::class, 'sitemap']);

Route::get('/audios/category/{category}', [AudioController::class, 'frontAudiosbyCategory']);
Route::get('/audios/page/{page}', [AudioController::class, 'paginateAudio'])->whereNumber('page');
Route::get('/audios/sitemap', [AudioController::class, 'sitemap']);
Route::get('/audios/slug/{slug}', [AudioController::class, 'getAudioBySlug']);
Route::get('/audios/{type}', [AudioController::class, 'frontAudioCategoriesbyType']);

/*
|--------------------------------------------------------------------------
| API v2 — application mobile
|--------------------------------------------------------------------------
| Mêmes ressources que v1, enrichies de ce dont l'app V2 a besoin
| (nombre d'audios par catégorie, notamment).
*/
Route::prefix('v2')->group(function () {
    Route::get('/homepage', [OverviewController::class, 'frontHomepageV2']);
    Route::get('/audios/{type}', [AudioController::class, 'frontAudioCategoriesbyTypeV2']);
    Route::get('/search/{term}', [OverviewController::class, 'searchV2']);

    // Comptes de l'application mobile (feed vidéo).
    Route::post('/auth/register', [AppAuthController::class, 'register'])->middleware('throttle:app-register');
    Route::post('/auth/login', [AppAuthController::class, 'login'])->middleware('throttle:app-login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AppAuthController::class, 'me']);
        Route::post('/auth/logout', [AppAuthController::class, 'logout']);
        Route::post('/me/avatar', [AppAuthController::class, 'updateAvatar'])->middleware('throttle:app-avatar');

        // Publication de vidéos.
        Route::post('/videos', [VideoController::class, 'store'])->middleware('throttle:app-video');
        Route::get('/videos/{id}/status', [VideoController::class, 'status'])->whereNumber('id');

        // Interactions (compte requis).
        Route::middleware('throttle:app-interaction')->group(function () {
            Route::post('/videos/{id}/like', [VideoFeedController::class, 'like'])->whereNumber('id');
            Route::delete('/videos/{id}/like', [VideoFeedController::class, 'unlike'])->whereNumber('id');
            Route::post('/comments/{id}/like', [VideoFeedController::class, 'likeComment'])->whereNumber('id');
            Route::delete('/comments/{id}/like', [VideoFeedController::class, 'unlikeComment'])->whereNumber('id');
            Route::post('/users/{id}/follow', [VideoFeedController::class, 'follow'])->whereNumber('id');
            Route::delete('/users/{id}/follow', [VideoFeedController::class, 'unfollow'])->whereNumber('id');
        });

        Route::post('/videos/{id}/comments', [VideoFeedController::class, 'storeComment'])
            ->whereNumber('id')
            ->middleware('throttle:app-comment');
        Route::get('/me/videos', [AppUserController::class, 'myVideos']);
    });

    // Feed et profils : publics, le token (optionnel) enrichit la réponse.
    Route::middleware('throttle:app-read')->group(function () {
        Route::get('/feed', [VideoFeedController::class, 'feed']);
        Route::get('/videos/{id}/comments', [VideoFeedController::class, 'comments'])->whereNumber('id');
        Route::get('/users/{username}', [AppUserController::class, 'show']);
        Route::get('/users/{username}/videos', [AppUserController::class, 'videos']);
    });
    Route::post('/videos/{id}/view', [VideoFeedController::class, 'view'])->whereNumber('id')->middleware('throttle:app-view');
});
