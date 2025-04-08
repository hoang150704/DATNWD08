<?php

use App\Http\Controllers\Api\Admin\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->group(function () {
    Route::get('/', [CommentController::class, 'index']);       
    Route::get('{id}', [CommentController::class, 'show']);                     
    Route::put('{id}/reply', [CommentController::class, 'reply']);              
    Route::put('{id}/status-toggle', [CommentController::class, 'statusToggle']);
});
