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
        $statisticBy = $request->query('statisticBy', '7day'); // Mặc định là 7 ngày
        $salesData = $this->getSalesStatistics($statisticBy); // Thống kê doanh số bán hàng
        $topSellingProducts = $this->getTopSellingProducts(); // Top 5 sản phẩm bán chạy nhất
        $productByCategory = $this->getProductByCategory(); // Số lượng sản phẩm theo danh mục
        $ratingStatistics = $this->getRatingStatistics(); // Số lượng đánh giá theo từng mức rating
        $topRatedProducts = $this->getTopRatedProducts(); // Top 5 sản phẩm được đánh giá cao nhất
        $topUsersBySpending = $this->getTopUsersBySpending(); // Top 5 user có số tiền chi tiêu nhiều nhất
        $year = $request->query('year', now()->year); // Năm thống kê

        return response()->json([
            "status" => "success",
            "message" => "Lấy dữ liệu dashboard thành công!",
            "data" => [
                "totalCategories" => Category::count(), // Tổng số danh mục
                "totalProducts" => DB::table('products')->count(), // Tổng số sản phẩm
                "totalUsers" => DB::table('users')->count(), // Tổng số người dùng
                "totalVouchers" => DB::table('vouchers')->count(), // Tổng số voucher
                "totalOrders" => Order::count(), // Tổng số đơn hàng
                "totalRevenue" => Order::where('payment_status_id', 1)->sum('final_amount'), // Tổng doanh thu
                "topSellingProducts" => $topSellingProducts, // Top 5 sản phẩm bán chạy nhất
                "salesStatistics" => $salesData, // Thống kê doanh số bán hàng
                "ratingStatistics" => $ratingStatistics, // Thống kê số lượng đánh giá theo từng mức rating
                "productByCategory" => $productByCategory, // Thống kê số lượng sản phẩm theo danh mục
                "topUsersBySpending" => $topUsersBySpending, // Top 5 user có số tiền chi tiêu nhiều nhất
                "topRatedProducts" => $topRatedProducts, // Top 5 sản phẩm được đánh giá cao nhất
                "revenueStatistics" => [
                    "daily" => $this->getRevenueStatistics('daily', $year), // Thống kê doanh thu theo ngày
                    "weekly" => $this->getRevenueStatistics('weekly', $year), // Thống kê doanh thu theo tuần
                    "monthly" => $this->getRevenueStatistics('monthly', $year), // Thống kê doanh thu theo tháng
                    "yearly" => $this->getRevenueStatistics('yearly', $year), // Thống kê doanh thu theo năm
                ],
                "statisticBy" => $statisticBy // Thời gian thống kê
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
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_products')
            ->get();
    }

    // Thống kê số lượng đánh giá theo từng mức rating
    private function getRatingStatistics()
    {
        return Comment::select('rating', DB::raw('COUNT(*) as total_reviews')) // Đếm số lượng đánh giá
            ->groupBy('rating')
            ->orderByDesc('rating')
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
        $join->on('products.id', '=', 'comments.product_id')
            ->where('comments.is_active', 1); // Chỉ lấy đánh giá đã duyệt
    })
    ->groupBy('products.id', 'products.name', 'products.avg_rating') // Thay đổi từ `rating` thành `avg_rating`
    ->orderByDesc('products.avg_rating') // Sắp xếp theo `avg_rating`
    ->orderByDesc(DB::raw('COUNT(comments.id)')) // Nếu rating giống nhau, ưu tiên sản phẩm có nhiều đánh giá hơn
    ->take(5)
    ->get();
}



    // Thống kê doanh số bán hàng theo thời gian
    private function getSalesStatistics($period)
    {
        $query = OrderItem::select(
            DB::raw('DATE(created_at) as date'), // Lấy ngày từ trường created_at
            DB::raw('SUM(quantity) as totalSales') // Tính tổng số lượng sản phẩm bán ra
        )
            ->groupBy('date') // Nhóm theo ngày
            ->orderBy('date', 'ASC');

        switch ($period) {
            case '7day':
                $query->where('created_at', '>=', now()->subDays(7)); // Lấy dữ liệu trong 7 ngày gần nhất
                break;
            case '1month':
                $query->where('created_at', '>=', now()->subMonth()); // Lấy dữ liệu trong 1 tháng gần nhất
                break;
            case '12month':
                $query->where('created_at', '>=', now()->subMonths(12)); // Lấy dữ liệu trong 12 tháng gần nhất
                break;
        }

        return $query->get();
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
                default => "DATE(created_at) as period",
            }
        ),
        DB::raw('SUM(final_amount) as totalRevenue') // Tính tổng doanh thu
    ])
    ->whereYear('created_at', $year) // Lọc theo năm
    ->where('payment_status_id', 1)   // Chỉ lấy đơn hàng đã thanh toán
    ->groupBy('period') // Nhóm theo thời gian
    ->orderBy('period', 'ASC');

    return $query->get();
}

}
