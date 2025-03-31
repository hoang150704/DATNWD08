<?php

use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);
Route::prefix('users')->group(function () {
    Route::post('/change_status/{id}', [UserController::class, 'changeActive']);
});