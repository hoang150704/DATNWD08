<?php

use App\Http\Controllers\Api\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('/search', [OrderController::class, 'search']);
    Route::post('/create', [OrderController::class, 'store']);
    Route::put('/{order}/edit', [OrderController::class, 'update']);
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::post('/{code}/confirm', [OrderController::class, 'confirmOrder']); // Xác nhận
    Route::post('/{code}/cancel', [OrderController::class, 'cancelOrderBycancelOrderByAdminAdmin']); // Hủy
    Route::post('/{code}/approve_return', [OrderController::class, 'approveReturn']); //Đồng ý hoàn hàng
    Route::post('/{code}/reject_return', [OrderController::class, 'rejectReturn']); // Từ chối
    Route::post('/{code}/refun_auto', [OrderController::class, 'refundAuto']);
    Route::post('/{code}/refund_manual', [OrderController::class, 'refundManual']);
    Route::post('/{code}/refund_partial', [OrderController::class, 'refundPartial']);
    Route::post('/{code}/confirm_return_received', [OrderController::class, 'confirmReturnReceived']);
});
