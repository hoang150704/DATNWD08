<?php
// ADMIN

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\CommentController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\ProfileController;
// USER
use App\Http\Controllers\Api\User\VoucherController as ClientVoucherController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\HomeController;
use App\Http\Controllers\Api\User\ShopController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\Services\UploadController;
use App\Http\Controllers\Api\User\OrderClientController;
use App\Http\Controllers\Api\User\ProductDetailController;

use App\Http\Controllers\Api\Services\GhnTrackingController;
use App\Http\Middleware\CheckOrderStatus;
use Illuminate\Support\Facades\Route;
use App\Models\ProductVariation;

// =======================================================================================================================================
// Các chức năng KHÔNG phải LOGIN
Route::post('/register', [AuthController::class, 'register']);
Route::get('/verify_email', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/auth/google/callback', [AuthController::class, 'googleAuth']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/product_detail/{id}', [ProductDetailController::class, 'show']);

Route::prefix('ghn')->group(function () {
    Route::post('/get_time_and_fee', [GhnTrackingController::class, 'getFeeAndTimeTracking']);
    Route::post('/post_order/{id}', [GhnTrackingController::class, 'postOrderGHN']);
});

// Đăng nhập bằng google
Route::post('/auth/google/callback', [AuthController::class, 'googleAuth']);

// Trang chủ
Route::get('/latest-products', [HomeController::class, 'getLatestProducts']);
Route::get('/parent-categories', [HomeController::class, 'getParentCategories']);
Route::get('/categories/{category_id}/products', [HomeController::class, 'getProductsByCategory']);
Route::get('/search', [HomeController::class, 'searchProducts']);
//Thanh toán
Route::post('/checkout', [OrderClientController::class, 'store']);
Route::get('/vnpay-return', [OrderClientController::class, 'callbackPayment']);
// Cửa hàng
Route::get('/products', [ShopController::class, 'getAllProducts']);
Route::get('/categories', [ShopController::class, 'getAllCategories']);
Route::get('/categories/{category_id}/products', [ShopController::class, 'getProductsByCategory']);

// Đánh giá
Route::get('/products/{product_id}/reviews', [ReviewController::class, 'getReviewsByProduct']);
Route::post('/reviews', [ReviewController::class, 'store']);

//Chi tiết sản phẩm
Route::get('/product_detail/{id}', [ProductDetailController::class, 'show']);

// Lấy biến thể
Route::post('/variation', [CartController::class, 'getVariation']);

// Voucher
Route::prefix('voucher')->group(function () {
    Route::get('/', [ClientVoucherController::class, 'index']); // Lấy danh sách voucher
    Route::get('/{id}', [ClientVoucherController::class, 'show']); // Lấy chi tiết voucher
    Route::get('/search', [ClientVoucherController::class, 'search']); // Tìm kiếm voucher
    Route::post('/apply-voucher', [ClientVoucherController::class, 'applyVoucher']); // Áp dụng voucher
});

// =======================================================================================================================================
// Chức năng cần LOGIN
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change_email', [AuthController::class, 'requestChangeEmail']);
    Route::post('/verify_new_email', [AuthController::class, 'verifyNewEmail']);
    //Profile routes
    Route::get('/profile', [ProfileController::class, 'info']);
    Route::post('/change_profile', [ProfileController::class, 'changeProfile']);

    //Address routes
    Route::get('/addresses', [ProfileController::class, 'index']); // Lấy danh sách địa chỉ
    Route::get('/addresses/default', [ProfileController::class, 'getDefault']); // Lấy địa chỉ mặc định
    Route::post('/addresses', [ProfileController::class, 'store']); // Thêm địa chỉ mới
    Route::put('/addresses/{id}', [ProfileController::class, 'update']); // Cập nhật địa chỉ
    Route::delete('/addresses/{id}', [ProfileController::class, 'destroy']); // Xóa địa chỉ
    Route::put('/addresses/{id}/set-default', [ProfileController::class, 'setDefault']); // Đặt địa chỉ mặc định mới
    Route::get('/addresses/{id}/select', [ProfileController::class, 'selectAddressForOrder']); // Chọn địa chỉ cho đơn hàng (chỉ dùng tạm thời)
    // =========================================================================

    // Giỏ hàng
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'addCart']);
    Route::post('/cart/sync', [CartController::class, 'syncCart']);
    Route::put('/cart/{id}', [CartController::class, 'changeQuantity']);
    Route::delete('/cart/{id}', [CartController::class, 'removeItem']);
    Route::post('/cart/clear', [CartController::class, 'clearAll']);

    // Lấy link ảnh
    Route::post('/upload', [UploadController::class, 'uploadImage']);

    // Chức năng chỉ Admin mới call được api
    Route::prefix('admin')->middleware(['admin'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);

        // Voucher
        Route::prefix('vouchers')->group(function () {
            Route::get('/', [VoucherController::class, 'index']);
            Route::post('/create', [VoucherController::class, 'store']);
            Route::get('/{id}', [VoucherController::class, 'show']);
            Route::put('/{id}', [VoucherController::class, 'update']);
            Route::delete('/', [VoucherController::class, 'destroy']);
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

        //Xử lí api giao hàng nhanh
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
            Route::get('{comment}', [CommentController::class, 'show']);
        });

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::patch('/{notification}', [NotificationController::class, 'markAsRead']);
        });

        // Require
        require base_path('routes/api/admin/categories.php');
        require base_path('routes/api/admin/attributes.php');
        require base_path('routes/api/admin/attribute_values.php');
        require base_path('routes/api/admin/libraries.php');
        require base_path('routes/api/admin/products.php');
    });
});
