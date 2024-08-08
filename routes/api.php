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

Route::get('/mainpage', [OverviewController::class, 'frontMainpage']);
Route::get('/audiopage', [AudioController::class, 'frontAudiopage']);

Route::get('/files/{page}', [FileController::class, 'paginateFiles']);
Route::get('/file/{slug}', [FileController::class, 'getFileBySlug']);
