<?php

use App\Http\Controllers\Api\Admin\CommentController;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
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

Route::prefix('admin')->group(function () {
    Route::prefix('comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::get('hidden', [CommentController::class, 'hiddenComment']);
        Route::delete('delete', [CommentController::class, 'destroy']);
        Route::patch('reply', [CommentController::class, 'reply']);
        Route::patch('status', [CommentController::class, 'statusToggle']);
        Route::get('search', [CommentController::class, 'search']);
        Route::get('{comment}', [CommentController::class, 'show']);
    });
});
