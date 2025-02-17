<?php

use App\Http\Controllers\Api\Admin\LibraryController;
use App\Http\Controllers\Api\Admin\ProductAttributeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('admin')->group(function () {
    // // AdminCategory
    require base_path('routes/api/admin/categories.php');
    // Admin Attribute
    require base_path('routes/api/admin/attributes.php');
    // Admin Attribute Value
    require base_path('routes/api/admin/attribute_values.php');
    // Thư viện
    Route::apiResource('libraries', LibraryController::class);
    // Products
    require base_path('routes/api/admin/products.php');

});



