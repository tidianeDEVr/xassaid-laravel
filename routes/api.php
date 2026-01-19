<?php

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
