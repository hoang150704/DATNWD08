<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {

        // Thời gian thống kê
        $startDate = $request->query('startDate', now()->subDays(7)->toDateString()); // Mặc định 7 ngày trước
        // Nếu chỉ truyền startDate thì endDate sẽ là ngày hiện tại
        // Nếu không truyền startDate thì startDate sẽ là ngày tước endDate 7 ngày
        $endDate = $request->query('endDate', now()->toDateString()); // Ngày hiện tại

        // Thống kê doanh số bán hàng theo khoảng thời gian động
        $salesData = $this->getSalesStatistics($startDate, $endDate);

        // Nếu có truyền startDate và endDate thì lấy top sản phẩm bán chạy theo khoảng thời gian đó
        $topSellingByDate = null;
        if ($startDate && $endDate) {
            $topSellingByDate = $this->getTopSellingProductsByDateRange($startDate, $endDate);
        }

        // Lợi nhuận theo thời gian
        // $profit = $this->getProfit($startDate, $endDate);

        // Tỉ lệ khách hàng đăng nhập mua
        $loginPurchaseRate = $this->getLoginPurchaseRate($startDate, $endDate);

        // Tỉ lệ khách hàng không đăng nhập mua
        $guestPurchaseRate = $this->getGuestPurchaseRate($startDate, $endDate);

        // Top 5 sản phẩm bán chạy nhất
        $topSellingProducts = $this->getTopSellingProducts();

        // Số lượng sản phẩm theo danh mục
        $productByCategory = $this->getProductByCategory();

        // Tỉ lệ đơn hàng bị hủy
        $cancellationRate = $this->getCancellationRate($startDate, $endDate);

        // Số lượng đánh giá theo từng mức rating
        $ratingStatistics = $this->getRatingStatistics();

        // Top 5 sản phẩm được đánh giá cao nhất
        $topRatedProducts = $this->getTopRatedProducts();

        // Thống kê đơn hàng
        $orderStatistics = $this->getOrderStatistics($startDate, $endDate);

        // Top 5 user có số tiền chi tiêu nhiều nhất
        $topUsersBySpending = $this->getTopUsersBySpending();

        // Năm thống kê
        $year = $request->query('year', now()->year);

        return response()->json([
            "status" => "success",
            "message" => "Lấy dữ liệu dashboard thành công!",
            "data" => [
                // Tổng số danh mục
                "totalCategories" => Category::count(),

                // Tổng số sản phẩm
                "totalProducts" => DB::table('products')->count(),

                // Tổng số người dùng
                "totalUsers" => DB::table('users')->count(),

                // Tổng số voucher
                "totalVouchers" => DB::table('vouchers')->count(),

                // Tổng số đơn hàng
                "totalOrders" => Order::count(),

                // Tổng doanh thu
                "totalRevenue" => Order::where('payment_status_id', 2)->sum('final_amount'),

                // Top 5 sản phẩm bán chạy nhất
                "topSellingProducts" => $topSellingProducts,

                // Thống kê số lượng đánh giá theo từng mức rating
                "ratingStatistics" => $ratingStatistics,

                // Lợi nhuận
                // "profit" => $profit->total_profit ?? 0,

                // Số lượng sản phẩm theo danh mục
                "productByCategory" => $productByCategory,

                // Top 5 user có số tiền chi tiêu nhiều nhất
                "topUsersBySpending" => $topUsersBySpending,

                // Tỉ lệ khách hàng đăng nhập mua
                "loginPurchaseRate" => $loginPurchaseRate,

                // Tỉ lệ khách hàng không đăng nhập mua
                "guestPurchaseRate" => $guestPurchaseRate,

                // Top sản phẩm bán chạy nhất theo khoảng thời gian
                "topSellingByDate" => $topSellingByDate,

                // Tỉ lệ đơn hàng bị hủy
                "cancellationRate" => $cancellationRate,

                // Top 5 sản phẩm được đánh giá cao nhất
                "topRatedProducts" => $topRatedProducts,

                // Thống kê đơn hàng theo thời gian
                "orderStatistics" => $orderStatistics,

                // Thống kê doanh số bán hàng theo thời gian
                "salesStatistics" => $salesData,
                "startDate" => $startDate,
                "endDate" => $endDate,
            ]
        ], 200);
    }

    // Thống kê số lượng sản phẩm theo danh mục
    private function getProductByCategory()
    {
        return Category::select(
            'categories.id',
            'categories.name',
            DB::raw('COALESCE(COUNT(product_category_relations.product_id), 0) as total_products') // Đếm số lượng sản phẩm
        )
            ->leftJoin('product_category_relations', 'categories.id', '=', 'product_category_relations.category_id') // Join bảng product_category_relations
            ->groupBy('categories.id', 'categories.name') // Nhóm theo danh mục
            ->orderByDesc('total_products') // Sắp xếp theo số lượng sản phẩm giảm dần
            ->get();
    }

    // Thống kê số lượng đánh giá theo từng mức rating
    private function getRatingStatistics()
    {
        return Comment::select(
            'rating',
            DB::raw('COUNT(*) as total_reviews') // Đếm số lượng đánh giá
        )
            ->where('is_active', 1) // Chỉ lấy đánh giá đã duyệt
            ->groupBy('rating') // Nhóm theo rating
            ->orderBy('rating') // Sắp xếp theo rating tăng dần
            ->get();
    }


    // Lấy top 5 sản phẩm được đánh giá cao nhất
    private function getTopRatedProducts()
    {
        return Product::select(
            'products.id',
            'products.name',
            'products.avg_rating as avg_rating',
            DB::raw('COUNT(product_reviews.id) as total_reviews') // ✅ sửa chỗ này
        )
            ->leftJoin('product_reviews', function ($join) {
                $join->on('products.id', '=', 'product_reviews.product_id')
                    ->where('product_reviews.is_active', 1);
            })
            ->groupBy('products.id', 'products.name', 'products.avg_rating')
            ->orderByDesc('products.avg_rating')
            ->orderByDesc(DB::raw('COUNT(product_reviews.id)'))
            ->take(5)
            ->get();
    }

    // Thống kê doanh số bán hàng theo thời gian
    private function getSalesStatistics($startDate, $endDate)
    {
        return Order::select(
            DB::raw('DATE(created_at) as date'), // Lấy ngày
            DB::raw('SUM(final_amount) as totalRevenue'), // Tính tổng doanh thu
            DB::raw('COUNT(id) as totalOrders') // Đếm số lượng đơn hàng
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_status_id', 1) // Chỉ lấy đơn hàng đã thanh toán
            ->groupBy('date') // Nhóm theo ngày
            ->orderBy('date', 'ASC')    // Sắp xếp theo ngày tăng dần
            ->get();
    }



    // Lấy danh sách sản phẩm bán chạy nhất (top 5)
    private function getTopSellingProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold')) // Tính tổng số lượng sản phẩm bán ra
            ->groupBy('product_id') // Nhóm theo sản phẩm
            ->orderByDesc('total_sold') // Sắp xếp theo số lượng bán được
            ->take(5)
            ->with('product:id,name,main_image') // Lấy thông tin sản phẩm
            ->get();
    }

    // Lấy top 5 user có số tiền chi tiêu nhiều nhất
    private function getTopUsersBySpending()
    {
        return Order::select(
            'user_id',
            DB::raw('SUM(final_amount) as total_spent') // Tính tổng tiền đã chi
        )
            ->where('payment_status_id', 1) // Chỉ lấy đơn hàng đã thanh toán
            ->groupBy('user_id') // Nhóm theo user
            ->orderByDesc('total_spent') // Sắp xếp theo số tiền đã chi
            ->take(5) // Lấy top 5
            ->with('user:id,name,email') // Lấy thông tin user
            ->get();
    }

    // Lấy top sản phẩm bán chạy nhất theo khoảng thời gian
    private function getTopSellingProductsByDateRange($startDate, $endDate, $limit = 5)
    {
        // Lấy danh sách sản phẩm bán chạy nhất theo khoảng thời gian
        return OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_sold') // Tính tổng số lượng sản phẩm bán ra
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id') // Join bảng orders để lấy thông tin đơn hàng
            ->whereBetween('orders.created_at', [$startDate, $endDate]) // Lọc theo khoảng thời gian
            ->where('orders.order_status_id', 4) // Chỉ lấy đơn đã hoàn thành
            ->groupBy('product_id') // Nhóm theo sản phẩm
            ->orderByDesc('total_sold') // Sắp xếp theo số lượng bán được
            ->with('product:id,name,main_image')    // Lấy thông tin sản phẩm
            ->take($limit) // Lấy top 5
            ->get();
    }

    // Lấy tỉ lệ đơn hủy theo khoảng thời gian
    private function getCancellationRate($startDate, $endDate)
    {

        // Lấy tổng số đơn hàng và số đơn hàng đã hủy
        // Nếu không có đơn hàng nào thì trả về 0
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $canceledOrders = Order::where('order_status_id', 9) // Đơn hàng đã hủy
            ->whereBetween('created_at', [$startDate, $endDate]) // Lọc theo khoảng thời gian
            ->count();

        // Tính tỉ lệ đơn hàng bị hủy
        return ($totalOrders > 0) ? ($canceledOrders / $totalOrders) * 100 : 0;
    }

    // Tỉ lệ khách hàng đăng nhập mua
    private function getLoginPurchaseRate($startDate, $endDate)
    {

        // Lấy tổng số đơn hàng và số đơn hàng của khách
        // Nếu không có đơn hàng nào thì trả về 0
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        // Lấy số đơn hàng của khách
        // Nếu không có đơn hàng nào thì trả về 0
        $loginOrders = Order::where('user_id', '!=', null)
            ->whereBetween('created_at', [$startDate, $endDate]) // Lọc theo khoảng thời gian
            ->count();

        // Tính tỉ lệ khách hàng đăng nhập mua
        return ($totalOrders > 0) ? ($loginOrders / $totalOrders) * 100 : 0;
    }

    // Tỉ lệ khách hàng không đăng nhập mua
    private function getGuestPurchaseRate($startDate, $endDate)
    {

        // Lấy tổng số đơn hàng và số đơn hàng của khách
        // Nếu không có đơn hàng nào thì trả về 0
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        // Lấy số đơn hàng của khách
        // Nếu không có đơn hàng nào thì trả về 0
        $guestOrders = Order::where('user_id', null)
            ->whereBetween('created_at', [$startDate, $endDate]) // Lọc theo khoảng thời gian
            ->count();

        // Tính tỉ lệ khách hàng không đăng nhập mua
        return ($totalOrders > 0) ? ($guestOrders / $totalOrders) * 100 : 0;
    }

    // Số đơn hàng được tạo, đơn hoàn thành, đơn đã hủy, đơn đang xử lý, đang giao, v.v.
    private function getOrderStatistics($startDate, $endDate)
    {
        return Order::select(
            DB::raw('COUNT(*) as total_orders'), // Tổng số đơn hàng

            // Đơn hàng chờ xac nhận
            DB::raw('SUM(CASE WHEN order_status_id = 1 THEN 1 ELSE 0 END) as pending_orders'),

            // Đơn hàng đang chờ xử lý
            DB::raw('SUM(CASE WHEN order_status_id = 2 THEN 1 ELSE 0 END) as confirmed_orders') ,

            // Đơn hàng đã hoàn thành
            DB::raw('SUM(CASE WHEN order_status_id = 4 THEN 1 ELSE 0 END) as completed_orders'),

            // Đơn hàng đã hủy
            DB::raw('SUM(CASE WHEN order_status_id = 9 THEN 1 ELSE 0 END) as canceled_orders')
        )
            ->whereBetween('created_at', [$startDate, $endDate]) // Lọc theo khoảng thời gian
            ->first();
    }

    // // Lợi nhuận (tính từ chênh lệch final_amout của order trừ đi price của order item)
    // private function getProfit($startDate, $endDate)
    // {
    //     return Order::select(
    //         DB::raw('SUM(final_amount - (SELECT SUM(price) FROM order_items WHERE order_items.order_id = orders.id)) as total_profit') // Tính lợi nhuận
    //     )
    //         ->whereBetween('created_at', [$startDate, $endDate])
    //         ->first();
    // }

}
