<?php

use App\Http\Controllers\Api\Admin\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->group(function () {
    Route::get('/', [CommentController::class, 'index']);
    Route::get('hidden', [CommentController::class, 'hiddenComment']);
    Route::delete('delete', [CommentController::class, 'destroy']);
    Route::patch('reply', [CommentController::class, 'reply']);
    Route::patch('status', [CommentController::class, 'statusToggle']);
    Route::get('{comment}', [CommentController::class, 'show']);
});
