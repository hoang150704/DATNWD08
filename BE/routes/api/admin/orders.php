<?php

use App\Http\Controllers\Api\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/search', [OrderController::class, 'search']);
    Route::post('/create', [OrderController::class, 'store']);
    Route::patch('/changestatus', [OrderController::class, 'changeStatus'])->middleware('check.order.status');
    Route::put('/{order}/edit', [OrderController::class, 'update']);
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::post('/{code}/confirm', [OrderController::class, 'confirmOrder']);
    Route::post('/{code}/cancel', [OrderController::class, 'cancelOrderByAdmin']);
});
