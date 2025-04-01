<?php

use App\Http\Controllers\Api\User\OrderClientController;
use Illuminate\Support\Facades\Route;
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderClientController::class, 'getOrdersForUser']); // /api/orders
    Route::get('/{code}', [OrderClientController::class, 'getOrderDetail']); // /api/orders/{code}
    Route::post('/{code}/cancel', [OrderClientController::class, 'cancel']); // /api/orders/{code}/cancel
    Route::post('/{code}/request-refund', [OrderClientController::class, 'requestRefund']); // /api/orders/{code}/request-refund
    Route::post('/{code}/retry-payment', [OrderClientController::class, 'retryPaymentVnpay']); // /api/orders/{code}/retry-payment
});

Route::get('/order_statuses', [OrderClientController::class, 'getOrderStatuses']);
