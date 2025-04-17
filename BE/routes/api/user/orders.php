<?php

use App\Http\Controllers\Api\User\OrderClientController;
use Illuminate\Support\Facades\Route;

Route::get('/order_statuses', [OrderClientController::class, 'getOrderStatuses']);
Route::get('/orders_for_user', [OrderClientController::class, 'getOrdersForUser']);
Route::get('/order_detail/{code}', [OrderClientController::class, 'getOrderDetail']);
Route::post('/cancel_order/{code}', [OrderClientController::class, 'cancel'])->middleware('blacklist');
Route::post('/request_refun_order/{code}', [OrderClientController::class, 'requestRefund']);
Route::post('/retry_payment_order/{code}', [OrderClientController::class, 'retryPaymentVnpay']);
Route::post('/close_order/{code}', [OrderClientController::class, 'closeOrder']);
Route::post('/review_order/{code}', [OrderClientController::class, 'reviewProduct']);
Route::post('/send_verify_order', [OrderClientController::class, 'sendVerifyOrderCode']);
Route::post('/verify_order_code', [OrderClientController::class, 'verifyOrderCode']);