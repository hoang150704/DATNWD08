<?php

use App\Http\Controllers\Api\Admin\AttributeValueController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\LibraryController;
use App\Http\Controllers\Api\Admin\ProductAttributeController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Middleware\CheckOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\AddressBookController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;


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
    // Admin Category
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/get-all-categories', [CategoryController::class, 'getParentCategories']);
        Route::get('/update/{id}', [CategoryController::class, 'show']);
        Route::post('/create', [CategoryController::class, 'store']);
        Route::put('/update/{id}', [CategoryController::class, 'update']);
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
        Route::delete('/hard-delete/{id}', [CategoryController::class, 'hardDelete']);
        Route::patch('/restore/{id}', [CategoryController::class, 'restore']);
        Route::get('/trash', [CategoryController::class, 'trash']);
    });

    // Admin Attribute
    Route::apiResource('attributes', AttributeValueController::class);

    // Admin Attribute Value
    Route::prefix('attribute_values')->group(function () {
        Route::get('/list/{id}', [AttributeValueController::class, 'index']);
        Route::get('/update/{id}', [AttributeValueController::class, 'show']);
        Route::post('/create', [AttributeValueController::class, 'store']);
        Route::put('/update/{id}', [AttributeValueController::class, 'update']);
        Route::delete('/delete/{id}', [AttributeValueController::class, 'destroy']);
    });

    // Admin Voucher
    Route::prefix('vouchers')->group(function () {
        Route::get('/', [VoucherController::class, 'index']);
        Route::post('/create', [VoucherController::class, 'store']);
        Route::get('/{code}', [VoucherController::class, 'show']);
        Route::put('/update/{id}', [VoucherController::class, 'update']);
        Route::delete('/delete/{code}', [VoucherController::class, 'destroy']);
    });

    // Admin Order
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/search', [OrderController::class, 'search']);
        Route::post('/create', [OrderController::class, 'store']);
        Route::patch('/changestatus', [OrderController::class, 'changeStatus'])->middleware('check.order.status');
        Route::get('/{order}', [OrderController::class, 'show']);
    });

    // Admin User
    Route::apiResource('users', UserController::class);
    Route::apiResource('address-books', AddressBookController::class);
});
Route::prefix('admin')->group(function () {
    // // AdminCategory
    require base_path('routes/api/admin/categories.php');
    // Admin Attribute
    require base_path('routes/api/admin/attributes.php');
    // Admin Attribute Value
    require base_path('routes/api/admin/attribute_values.php');
    // Thư viện
    require base_path('routes/api/admin/libraries.php');
    // Products
    require base_path('routes/api/admin/products.php');

});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
