<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\CommentController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Middleware\CheckOrderStatus;
use Illuminate\Support\Facades\Route;

// ===============================================================================
// Các chức năng KHÔNG cần LOGIN
Route::post('/register', [AuthController::class, 'register']);
Route::get('/verify_email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Trang chủ
Route::get('/latest-products', [HomeController::class, 'getLatestProducts']);
Route::get('/parent-categories', [HomeController::class, 'getParentCategories']);
Route::get('/top-comments', [HomeController::class, 'getTopComments']);
Route::get('/categories/{category_id}/products', [HomeController::class, 'getProductsByCategory']);
Route::get('/search', [HomeController::class, 'searchProducts']);

// ===============================================================================
// Chức năng cần LOGIN
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change_email', [AuthController::class, 'requestChangeEmail']);
    Route::post('/verify_new_email', [AuthController::class, 'verifyNewEmail']);
    Route::post('/upload', [UploadController::class, 'uploadImage']);

    // Chức năng chỉ admin mới call được api
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        // Dashborad
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Trang quản trị Admin']);
        });

        // Voucher
        Route::prefix('vouchers')->group(function () {
            Route::get('/', [VoucherController::class, 'index']);
            Route::post('/create', [VoucherController::class, 'store']);
            Route::get('/{code}', [VoucherController::class, 'show']);
            Route::put('/{id}', [VoucherController::class, 'update']);
            Route::delete('/{id}', [VoucherController::class, 'destroy']);
        });

        // Đơn hàng 
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/search', [OrderController::class, 'search']);
            Route::post('/create', [OrderController::class, 'store']);
            Route::patch('/changestatus', [OrderController::class, 'changeStatus'])->middleware('check.order.status');
            Route::put('/{order}/edit', [OrderController::class, 'update']);
            Route::get('/{order}', [OrderController::class, 'show']);
        });

        // User
        Route::apiResource('users', UserController::class);
        Route::prefix('users')->group(function () {
            Route::post('/change_status/{id}', [UserController::class, 'changeActive']);
        });

        // Comment
        Route::prefix('comments')->group(function () {
            Route::get('/', [CommentController::class, 'index']);
            Route::get('hidden', [CommentController::class, 'hiddenComment']);
            Route::delete('delete', [CommentController::class, 'destroy']);
            Route::patch('reply', [CommentController::class, 'reply']);
            Route::patch('status', [CommentController::class, 'statusToggle']);
            Route::get('search', [CommentController::class, 'search']);
            Route::get('{comment}', [CommentController::class, 'show']);
        });

        // Require
        require base_path('routes/api/admin/categories.php');
        require base_path('routes/api/admin/attributes.php');
        require base_path('routes/api/admin/attribute_values.php');
        require base_path('routes/api/admin/libraries.php');
        require base_path('routes/api/admin/products.php');
    });
});