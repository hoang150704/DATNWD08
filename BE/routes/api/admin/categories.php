<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']); // Lấy tất cả danh mục trang list
    Route::get('/get-all-categories', [CategoryController::class, 'getParentCategories']);
    Route::patch('/changeCategory', [CategoryController::class, 'changeCategory']);
    Route::get('/update/{id}', [CategoryController::class, 'show']);
    Route::post('/create', [CategoryController::class, 'store']);
    Route::put('/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/delete', [CategoryController::class, 'destroy']);
    Route::delete('/hard-delete', [CategoryController::class, 'hardDelete']);
    Route::patch('/restore', [CategoryController::class, 'restore']);
    Route::get('/trash', [CategoryController::class, 'trash']);
    Route::get('/list', [CategoryController::class, 'getCategories']);
});