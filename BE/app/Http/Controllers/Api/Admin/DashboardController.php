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
        // Mặc định là 7 ngày
        $statisticBy = $request->query('statisticBy', '7day');

        // Thời gian thống kê
        $startDate = $request->query('startDate', now()->subDays(7)->toDateString());
        $endDate = $request->query('endDate', now()->toDateString());

        // Thống kê doanh số bán hàng theo khoảng thời gian động
        $salesData = $this->getSalesStatistics($startDate, $endDate);

        // Top 5 sản phẩm bán chạy nhất
        $topSellingProducts = $this->getTopSellingProducts();

        // Số lượng sản phẩm theo danh mục
        $productByCategory = $this->getProductByCategory();

        // Số lượng đánh giá theo từng mức rating
        $ratingStatistics = $this->getRatingStatistics();

        // Top 5 sản phẩm được đánh giá cao nhất
        $topRatedProducts = $this->getTopRatedProducts();

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
                "totalRevenue" => Order::where('payment_status_id', 1)->sum('final_amount'),

                // Top 5 sản phẩm bán chạy nhất
                "topSellingProducts" => $topSellingProducts,

                // Thống kê số lượng đánh giá theo từng mức rating
                "ratingStatistics" => $ratingStatistics,

                // Số lượng sản phẩm theo danh mục
                "productByCategory" => $productByCategory,

                // Top 5 user có số tiền chi tiêu nhiều nhất
                "topUsersBySpending" => $topUsersBySpending,

                // Top 5 sản phẩm được đánh giá cao nhất
                "topRatedProducts" => $topRatedProducts,

                // Thống kê doanh số bán hàng
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
        return Comment::select('rating', DB::raw('COUNT(*) as total_reviews')) // Đếm số lượng đánh giá
            ->groupBy('rating') // Nhóm theo rating
            ->orderByDesc('rating') // Sắp xếp theo rating giảm dần
            ->get();
    }

    // Lấy top 5 sản phẩm được đánh giá cao nhất
    private function getTopRatedProducts()
    {
        return Product::select(
            'products.id',
            'products.name',
            'products.avg_rating as avg_rating', // Thay đổi từ `rating` thành `avg_rating`
            DB::raw('COUNT(comments.id) as total_reviews') // Đếm tổng số đánh giá
        )
            ->leftJoin('comments', function ($join) {
                $join->on('products.id', '=', 'comments.product_id') // Join bảng comments
                    ->where('comments.is_active', 1); // Chỉ lấy đánh giá đã duyệt
            })
            ->groupBy('products.id', 'products.name', 'products.avg_rating') // Thay đổi từ `rating` thành `avg_rating`
            ->orderByDesc('products.avg_rating') // Sắp xếp theo `avg_rating`
            ->orderByDesc(DB::raw('COUNT(comments.id)')) // Nếu rating giống nhau, ưu tiên sản phẩm có nhiều đánh giá hơn
            ->take(5)
            ->get();
    }



    // Thống kê doanh số bán hàng theo thời gian
    private function getSalesStatistics($startDate, $endDate)
    {
        return OrderItem::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(quantity) as totalSales')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }


    // Lấy danh sách sản phẩm bán chạy nhất (top 5)
    private function getTopSellingProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold')) // Tính tổng số lượng sản phẩm bán ra
            ->groupBy('product_id') // Nhóm theo sản phẩm
            ->orderByDesc('total_sold') // Sắp xếp theo số lượng bán được
            ->take(5)
            ->with('product:id,name,main_image')
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


    // Lấy thống kê doanh thu theo thời gian
    private function getRevenueStatistics($period = 'daily', $year = null)
    {
        $year = $year ?? now()->year; // Nếu không truyền năm, mặc định lấy năm hiện tại

        $query = Order::select([
            DB::raw(
                match ($period) {
                    'daily' => "DATE(created_at) as period", // Lấy ngày
                    'weekly' => "YEARWEEK(created_at, 1) as period", // Lấy tuần
                    'monthly' => "DATE_FORMAT(created_at, '%Y-%m') as period", // Lấy tháng
                    'yearly' => "YEAR(created_at) as period", // Lấy năm
                    default => "DATE(created_at) as period", // Mặc định lấy ngày
                }
            ),
            DB::raw('SUM(final_amount) as totalRevenue') // Tính tổng doanh thu
        ])
            ->whereYear('created_at', $year) // Lọc theo năm
            ->where('payment_status_id', 1)   // Chỉ lấy đơn hàng đã thanh toán
            ->groupBy('period') // Nhóm theo thời gian
            ->orderBy('period', 'ASC'); // Sắp xếp tăng dần theo thời gian

        return $query->get();
    }
}
