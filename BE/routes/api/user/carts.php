<?php

use App\Http\Controllers\Api\User\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'addCart']);
Route::post('/cart/sync', [CartController::class, 'syncCart']);
Route::put('/cart/{id}', [CartController::class, 'changeQuantity']);
Route::delete('/cart/{id}', [CartController::class, 'removeItem']);
Route::post('/cart/clear', [CartController::class, 'clearAll']);