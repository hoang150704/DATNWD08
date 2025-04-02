<?php

use App\Http\Controllers\Api\Admin\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('contact')->group(function () {
    Route::get('/', [ContactController::class, 'index']);
    Route::post('/create', [ContactController::class, 'store']);
    Route::post('/reply_mail', [ContactController::class, 'reply_mail']);
    Route::get('/{id}', [ContactController::class, 'show']);
    Route::delete('/', [ContactController::class, 'destroy']);
});