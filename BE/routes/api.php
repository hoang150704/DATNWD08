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
    Route::post('/cancel_order', [GhnTrackingController::class, 'cancelOrderGhn']);
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
// Lấy thông tin order
Route::get('/search_order', [OrderClientController::class, 'searchOrderByCode']); // Lấy thông tin order theo mã đơn hàng dành cho khách không đăng nhập vẫn mua hàng
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
    //Order
    require base_path('routes/api/user/orders.php');
    //
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change_email', [AuthController::class, 'requestChangeEmail']);
    Route::post('/verify_new_email', [AuthController::class, 'verifyNewEmail']);
    //Profile routes
    Route::get('/profile', [ProfileController::class, 'info']);
    Route::post('/change_profile', [ProfileController::class, 'changeProfile']);
    //Address routes
    require base_path('routes/api/user/address_books.php');
    // Giỏ hàng
    require base_path('routes/api/user/carts.php');

    // Lấy link ảnh
    Route::post('/upload', [UploadController::class, 'uploadImage']);

    // Chức năng chỉ Admin mới call được api
    Route::prefix('admin')->middleware(['admin'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::patch('/{notification}', [NotificationController::class, 'markAsRead']);
        });


        // Require
        require base_path('routes/api/admin/categories.php'); // Danh mục
        require base_path('routes/api/admin/attributes.php'); // Thuộc tính
        require base_path('routes/api/admin/attribute_values.php'); // Giá trị thuộc tính
        require base_path('routes/api/admin/libraries.php'); // Thư viện ảnh sản phẩm
        require base_path('routes/api/admin/products.php'); // Sản phẩm
        require base_path('routes/api/admin/orders.php'); // Đơn hàng
        require base_path('routes/api/admin/comments.php'); // Bình luận
        require base_path('routes/api/admin/vouchers.php'); // Mã giảm giá
        require base_path('routes/api/admin/users.php'); // Người dùng
    });
    // Chức năng chỉ Staff mới call được api
    Route::prefix('staff')->middleware('staff')->group(function () {

        // Notification
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::patch('/{notification}', [NotificationController::class, 'markAsRead']);
        });

        // Require
        require base_path('routes/api/admin/categories.php'); // Danh mục
        require base_path('routes/api/admin/attributes.php'); // Thuộc tính
        require base_path('routes/api/admin/attribute_values.php'); // Giá trị thuộc tính
        require base_path('routes/api/admin/libraries.php'); // Thư viện ảnh sản phẩm
        require base_path('routes/api/admin/products.php'); // Sản phẩm
        require base_path('routes/api/admin/orders.php'); // Đơn hàng
        require base_path('routes/api/admin/comments.php'); // Bình luận
        require base_path('routes/api/admin/vouchers.php'); // Mã giảm giá
        require base_path('routes/api/admin/users.php'); // Người dùng
    });
});
