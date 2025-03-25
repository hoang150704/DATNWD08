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
                "totalRevenue" => Order::where('stt_payment', 1)->sum('final_amount'), // Tổng doanh thu
                "topSellingProducts" => $topSellingProducts, // Top 5 sản phẩm bán chạy nhất
                "salesStatistics" => $salesData, // Thống kê doanh số bán hàng
                "ratingStatistics" => $ratingStatistics, // Thống kê số lượng đánh giá theo từng mức rating
                "productByCategory" => $productByCategory, // Thống kê số lượng sản phẩm theo danh mục
                "topUsersBySpending" => $topUsersBySpending, // Top 5 user có số tiền chi tiêu nhiều nhất
                "topRatedProducts" => $topRatedProducts, // Top 5 sản phẩm được đánh giá cao nhất
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
            DB::raw('COALESCE(COUNT(product_category_relations.product_id), 0) as total_products')
        )
            ->leftJoin('product_category_relations', 'categories.id', '=', 'product_category_relations.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_products')
            ->get();
    }

    // Thống kê số lượng đánh giá theo từng mức rating
    private function getRatingStatistics()
    {
        return Comment::select('rating', DB::raw('COUNT(*) as total_reviews'))
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
            'products.rating as avg_rating', // Lấy rating từ bảng products
            DB::raw('COUNT(comments.id) as total_reviews') // Lấy số lượng đánh giá từ bảng comments
        )
            ->leftJoin('comments', function ($join) {
                $join->on('products.id', '=', 'comments.product_id')
                    ->where('comments.is_active', 1); // Chỉ lấy đánh giá đã duyệt
            })
            ->groupBy('products.id', 'products.name', 'products.rating')
            ->orderByDesc('products.rating') // Sắp xếp theo rating từ bảng products
            ->orderByDesc(DB::raw('COUNT(comments.id)')) // Nếu rating giống nhau, ưu tiên sản phẩm có nhiều đánh giá hơn
            ->take(5)
            ->get();
    }


    // Thống kê doanh số bán hàng theo thời gian
    private function getSalesStatistics($period)
    {
        $query = OrderItem::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(quantity) as totalSales')
        )
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        switch ($period) {
            case '7day':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case '1month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case '12month':
                $query->where('created_at', '>=', now()->subMonths(12));
                break;
        }

        return $query->get();
    }

    // Lấy danh sách sản phẩm bán chạy nhất (top 5)
    private function getTopSellingProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with('product:id,name,main_image')
            ->get();
    }

    // Lấy top 5 user có số tiền chi tiêu nhiều nhất
    private function getTopUsersBySpending()
    {
        return Order::select(
            'user_id',
            DB::raw('SUM(final_amount) as total_spent')
        )
            ->where('stt_payment', 1) // Chỉ tính đơn hàng đã thanh toán
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->with('user:id,name,email') // Lấy thông tin user
            ->get();
    }
    private function getRevenueStatistics($period = 'daily', $year = null)
{
    $year = $year ?? now()->year; // Nếu không truyền năm, mặc định lấy năm hiện tại

    $query = Order::select([
        DB::raw(
            match ($period) {
                'daily' => "DATE(created_at) as period",
                'monthly' => "DATE_FORMAT(created_at, '%Y-%m') as period",
                'yearly' => "YEAR(created_at) as period",
                default => "DATE(created_at) as period",
            }
        ),
        DB::raw('SUM(final_amount) as totalRevenue') // Tính tổng doanh thu
    ])
    ->whereYear('created_at', $year) // Lọc theo năm
    ->where('stt_payment', 1)   // Chỉ lấy đơn hàng đã thanh toán
    ->groupBy('period')
    ->orderBy('period', 'ASC');

    return $query->get();
}

}
