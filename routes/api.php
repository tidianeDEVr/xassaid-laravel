<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OverviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$activeVersion = 'v1';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/homepage', [OverviewController::class, 'frontHomepage']);


Route::get('/file/{slug}', [FileController::class, 'getFileBySlug']);
Route::get('/files/{page}', [FileController::class, 'paginateFiles']);

Route::get('/audios/category/{category}', [AudioController::class, 'frontAudiosbyCategory']);
Route::get('/audios/page/{page}', [AudioController::class, 'paginateAudio']);
Route::get('/audios/{type}', [AudioController::class, 'frontAudioCategoriesbyType']);
