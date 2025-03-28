<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ProfileController;


Route::get('/addresses', [ProfileController::class, 'index']); // Lấy danh sách địa chỉ
Route::get('/addresses/default', [ProfileController::class, 'getDefault']); // Lấy địa chỉ mặc định
Route::post('/addresses', [ProfileController::class, 'store']); // Thêm địa chỉ mới
Route::put('/addresses/{id}', [ProfileController::class, 'update']); // Cập nhật địa chỉ
Route::delete('/addresses/{id}', [ProfileController::class, 'destroy']); // Xóa địa chỉ
Route::put('/addresses/{id}/set-default', [ProfileController::class, 'setDefault']); // Đặt địa chỉ mặc định mới
Route::get('/addresses/{id}/select', [ProfileController::class, 'selectAddressForOrder']); // Chọn địa chỉ cho đơn hàng (chỉ dùng tạm thời)