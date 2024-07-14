<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\SecurityController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [OverviewController::class, 'dashboard'])->name('dashboard')->middleware(EnsureUserIsAdmin::class);

Route::get('/audios', [AudioController::class, 'renderAudios'])->middleware(EnsureUserIsAdmin::class);
Route::post('/audios', [AudioController::class, 'createAudio'])->middleware(EnsureUserIsAdmin::class);

Route::get('/library', function () {
    return view('pages/library');
})->middleware(EnsureUserIsAdmin::class);

Route::get('/users', function () {
    return view('pages/users');
})->middleware(EnsureUserIsAdmin::class);

Route::prefix('/categories')->group(function () {
    Route::post('/audios',  [AudioController::class, 'createCategory'])->middleware(EnsureUserIsAdmin::class);
    Route::get('/audios', [AudioController::class, 'renderCategories'])->middleware(EnsureUserIsAdmin::class)->name('audios.renderCategories');
});

// Securities
Route::delete('/logout', [SecurityController::class, 'logout'])->name('security.logout');

Route::get('/login', [SecurityController::class, 'login'])->name('security.login');

Route::post('/login', [SecurityController::class, 'doLogin']);
