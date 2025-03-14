<?php

use App\Http\Controllers\Api\Admin\ProductAttributeController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductVariationController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    //Route CRUD cho sản phẩm
    Route::get('/', [ProductController::class, 'index']); // Lấy danh sách sản phẩm
    Route::post('/', [ProductController::class, 'store']); // Thêm sản phẩm mới
    Route::get('/{id}', [ProductController::class, 'show']); // Xem chi tiết sản phẩm
    Route::put('/{id}', [ProductController::class, 'update']); // Cập nhật sản phẩm
    Route::delete('/{id}', [ProductController::class, 'destroy']); // Xóa sản phẩm
    Route::get('/search', [ProductController::class, 'search']); // Tìm kiếm sản phẩm

    //Route danh sách sản phẩm dành cho đặt hàng (Tránh xung đột với {id})
    //Nhóm route cho biến thể sản phẩm (Variants)
    Route::prefix('{idProduct}/variants')->group(function () {
        Route::get('/', [ProductVariationController::class, 'index']); // Danh sách biến thể
        Route::get('/list', [ProductVariationController::class, 'list']); // Danh sách chi tiết
        Route::post('/', [ProductVariationController::class, 'store']); // Tạo biến thể mới
        Route::get('/{id}', [ProductVariationController::class, 'show']); // Xem chi tiết biến thể
        Route::put('/{id}', [ProductVariationController::class, 'update']); // Cập nhật biến thể
    });

    //Nhóm route cho thuộc tính sản phẩm (Attributes)
    Route::prefix('{idProduct}/attributes')->group(function () {
        Route::get('/', [ProductAttributeController::class, 'index']); // Danh sách thuộc tính
        Route::put('/', [ProductAttributeController::class, 'update']); // Cập nhật thuộc tính
    });
});

// ✅ Nhóm route xóa dữ liệu
Route::prefix('variants')->group(function () {
    Route::delete('/{id}', [ProductVariationController::class, 'destroy']); // Xóa biến thể
});

Route::prefix('attributes')->group(function () {
    Route::delete('/{id}', [ProductAttributeController::class, 'destroy']); // Xóa thuộc tính
    Route::delete('/delete-attributes/{id}', [ProductAttributeController::class, 'deleteAttribute']); // Xóa thuộc tính chi tiết
});
Route::get('/list_product_order', [ProductController::class, 'listProductForOrder']);
