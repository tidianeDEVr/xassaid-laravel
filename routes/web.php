<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [OverviewController::class, 'dashboard'])->name('dashboard')->middleware(EnsureUserIsAdmin::class);

Route::get('/audios', [AudioController::class, 'renderAudios'])->middleware(EnsureUserIsAdmin::class);
Route::post('/audios', [AudioController::class, 'createAudio'])->middleware(EnsureUserIsAdmin::class);
Route::put('/audios/{audio}', [AudioController::class, 'updateAudio'])->middleware(EnsureUserIsAdmin::class);
Route::delete('/audios/{audio}', [AudioController::class, 'deleteAudio'])->middleware(EnsureUserIsAdmin::class);

Route::get('/articles', [ArticleController::class, 'renderArticles'])->middleware(EnsureUserIsAdmin::class);
Route::get('/articles/create', [ArticleController::class, 'renderCreateArticles'])->middleware(EnsureUserIsAdmin::class);
Route::post('/articles/create', [ArticleController::class, 'processCreateArticles'])->middleware(EnsureUserIsAdmin::class);
Route::get('/articles/{article}/edit', [ArticleController::class, 'renderEditArticle'])->middleware(EnsureUserIsAdmin::class);
Route::put('/articles/{article}', [ArticleController::class, 'updateArticle'])->middleware(EnsureUserIsAdmin::class);
Route::delete('/articles/{article}', [ArticleController::class, 'deleteArticle'])->middleware(EnsureUserIsAdmin::class);
Route::get('/library', [FileController::class, 'renderFiles'])->middleware(EnsureUserIsAdmin::class);
Route::post('/library', [FileController::class, 'createFile'])->middleware(EnsureUserIsAdmin::class);
Route::put('/library/{file}', [FileController::class, 'updateFile'])->middleware(EnsureUserIsAdmin::class);
Route::delete('/library/{file}', [FileController::class, 'deleteFile'])->middleware(EnsureUserIsAdmin::class);

Route::get('/users', [UserController::class, 'renderUsers'])->middleware(EnsureUserIsAdmin::class);
Route::post('/users', [UserController::class, 'createAdmin'])->middleware(EnsureUserIsAdmin::class);
Route::put('/users/{user}', [UserController::class, 'updateUser'])->middleware(EnsureUserIsAdmin::class);
Route::delete('/users/{user}', [UserController::class, 'deleteUser'])->middleware(EnsureUserIsAdmin::class);

Route::prefix('/categories')->group(function () {
    Route::post('/audios',  [AudioController::class, 'createCategory'])->middleware(EnsureUserIsAdmin::class);
    Route::get('/audios', [AudioController::class, 'renderCategories'])->middleware(EnsureUserIsAdmin::class)->name('audios.renderCategories');
    Route::put('/audios/{category}', [AudioController::class, 'updateCategory'])->middleware(EnsureUserIsAdmin::class);
    Route::delete('/audios/{category}', [AudioController::class, 'deleteCategory'])->middleware(EnsureUserIsAdmin::class);
});

// Securities
Route::delete('/logout', [SecurityController::class, 'logout'])->name('security.logout');

Route::get('/login', [SecurityController::class, 'login'])->name('security.login');
Route::post('/login', [SecurityController::class, 'doLogin']);
