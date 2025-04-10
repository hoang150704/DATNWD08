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

                // Số lượng sản phẩm theo danh mục
                "productByCategory" => $productByCategory,

                // Top 5 user có số tiền chi tiêu nhiều nhất
                "topUsersBySpending" => $topUsersBySpending,

                // Tỉ lệ đơn hàng bị hủy
                "cancellationRate" => $cancellationRate,

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
        return OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_sold')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status_id', 4) // Chỉ lấy đơn đã hoàn thành
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product:id,name,main_image')
            ->take($limit)
            ->get();
    }

    // Lấy tỉ lệ đơn hủy theo khoảng thời gian
    private function getCancellationRate($startDate, $endDate)
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $canceledOrders = Order::where('order_status_id', 9) // Đơn hàng đã hủy
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return ($totalOrders > 0) ? ($canceledOrders / $totalOrders) * 100 : 0;
    }

    //
}
