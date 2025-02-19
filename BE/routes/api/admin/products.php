
<?php

use App\Http\Controllers\Api\Admin\ProductAttributeController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductVariationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
//Variant
Route::prefix('products')->group(function () {
    Route::prefix('{idProduct}/variants')->group(function () {
        Route::get('/', [ProductVariationController::class, 'index']); 
        Route::get('/list', [ProductVariationController::class, 'list']); 
        Route::post('/', [ProductVariationController::class, 'store']); 
        Route::get('/{id}', [ProductVariationController::class, 'show']);
        Route::put('/{id}', [ProductVariationController::class, 'update']);
        
    });
    Route::prefix('{idProduct}/attributes')->group(function () {
        Route::get('/', [ProductAttributeController::class, 'index']); 
    });
});
Route::delete('variants/{id}', [ProductVariationController::class, 'destroy']);