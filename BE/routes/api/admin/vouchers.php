<?php

use App\Http\Controllers\Api\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('vouchers')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);
    Route::post('/create', [VoucherController::class, 'store']);
    Route::get('/{id}', [VoucherController::class, 'show']);
    Route::put('/{id}', [VoucherController::class, 'update']);
    Route::delete('/', [VoucherController::class, 'destroy']);
});