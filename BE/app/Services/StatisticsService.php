<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsService
{
    // Thống kê cố định (không phụ thuộc vào thời gian)
    public function getFixedStatistics()
    {
        return [
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

            // Top 5 sản phẩm bán chạy nhất mọi thời đại
            "topSellingProducts" => $this->getTopSellingProducts(),

            // Thống kê số lượng đánh giá theo từng mức rating
            "ratingStatistics" => $this->getRatingStatistics(),

            // Số lượng sản phẩm theo danh mục
            "productByCategory" => $this->getProductByCategory(),

            // Top 5 sản phẩm được đánh giá cao nhất
            "topRatedProducts" => $this->getTopRatedProducts(),
        ];
    }

    // Thống kê theo thời gian
    public function getTimeBasedStatistics($startDate, $endDate)
    {
        return [
            // Tổng doanh thu trong khoảng thời gian
            "totalRevenue" => Order::where('payment_status_id', 2)
                ->where('order_status_id', 5)
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum('final_amount'),

            // Top 5 user có số tiền chi tiêu nhiều nhất
            "topUsersBySpending" => $this->getTopUsersBySpending($startDate, $endDate),

            // Tỉ lệ khách hàng đăng nhập mua
            "loginPurchaseRate" => $this->getLoginPurchaseRate($startDate, $endDate),

            // Tỉ lệ khách hàng không đăng nhập mua
            "guestPurchaseRate" => $this->getGuestPurchaseRate($startDate, $endDate),

            // Top sản phẩm bán chạy nhất theo khoảng thời gian
            "topSellingByDate" => $this->getTopSellingProductsByDateRange($startDate, $endDate),

            // Tỉ lệ đơn hàng bị hủy
            "cancellationRate" => $this->getCancellationRate($startDate, $endDate),

            // Thống kê đơn hàng theo thời gian
            "orderStatistics" => $this->getOrderStatistics($startDate, $endDate),


            // Thống kê doanh số bán hàng theo thời gian
            "salesStatistics" => $this->getSalesStatistics($startDate, $endDate),
        ];
    }

    // Thống kê hôm nay
    public function getTodayStatistics()
    {
        $today = Carbon::today();
        return $this->getTimeBasedStatistics($today, $today);
    }

    // Thống kê hôm qua
    public function getYesterdayStatistics()
    {
        $yesterday = Carbon::yesterday();
        return $this->getTimeBasedStatistics($yesterday, $yesterday);
    }

    // Thống kê tuần này
    public function getThisWeekStatistics()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        return $this->getTimeBasedStatistics($startOfWeek, $endOfWeek);
    }

    // Thống kê tuần trước
    public function getLastWeekStatistics()
    {
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
        return $this->getTimeBasedStatistics($startOfLastWeek, $endOfLastWeek);
    }

    // Thống kê tháng này
    public function getThisMonthStatistics()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        return $this->getTimeBasedStatistics($startOfMonth, $endOfMonth);
    }

    // Thống kê tháng trước
    public function getLastMonthStatistics()
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        return $this->getTimeBasedStatistics($startOfLastMonth, $endOfLastMonth);
    }

    // Thống kê quý này
    public function getThisQuarterStatistics()
    {
        $startOfQuarter = Carbon::now()->startOfQuarter();
        $endOfQuarter = Carbon::now()->endOfQuarter();
        return $this->getTimeBasedStatistics($startOfQuarter, $endOfQuarter);
    }

    // Thống kê quý trước
    public function getLastQuarterStatistics()
    {
        $startOfLastQuarter = Carbon::now()->subQuarter()->startOfQuarter();
        $endOfLastQuarter = Carbon::now()->subQuarter()->endOfQuarter();
        return $this->getTimeBasedStatistics($startOfLastQuarter, $endOfLastQuarter);
    }

    // Thống kê năm này
    public function getThisYearStatistics()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();
        return $this->getTimeBasedStatistics($startOfYear, $endOfYear);
    }

    // Thống kê năm trước
    public function getLastYearStatistics()
    {
        $startOfLastYear = Carbon::now()->subYear()->startOfYear();
        $endOfLastYear = Carbon::now()->subYear()->endOfYear();
        return $this->getTimeBasedStatistics($startOfLastYear, $endOfLastYear);
    }

    // Lấy tất cả thống kê theo các khoảng thời gian
    public function getAllTimePeriodStatistics()
    {
        return [
            'today' => $this->getTodayStatistics(),
            'yesterday' => $this->getYesterdayStatistics(),
            'thisWeek' => $this->getThisWeekStatistics(),
            'lastWeek' => $this->getLastWeekStatistics(),
            'thisMonth' => $this->getThisMonthStatistics(),
            'lastMonth' => $this->getLastMonthStatistics(),
            'thisQuarter' => $this->getThisQuarterStatistics(),
            'lastQuarter' => $this->getLastQuarterStatistics(),
            'thisYear' => $this->getThisYearStatistics(),
            'lastYear' => $this->getLastYearStatistics(),
        ];
    }

    // Lấy top 5 sản phẩm bán chạy nhất
    private function getTopSellingProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with('product:id,name,main_image')
            ->get();
    }

    // Thống kê số lượng đánh giá theo từng mức rating
    private function getRatingStatistics()
    {
        return Comment::select(
            'rating',
            DB::raw('COUNT(*) as total_reviews')
        )
            ->where('is_active', 1)
            ->groupBy('rating')
            ->orderBy('rating')
            ->get();
    }

    // Số lượng sản phẩm theo danh mục
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

    // Lấy top 5 sản phẩm được đánh giá cao nhất
    private function getTopRatedProducts()
    {
        return Product::select(
            'products.id',
            'products.name',
            'products.avg_rating as avg_rating',
            DB::raw('COUNT(product_reviews.id) as total_reviews')
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

    // Lấy top 5 user có số tiền chi tiêu nhiều nhất
    private function getTopUsersBySpending($startDate, $endDate)
    {
        return Order::select(
            'user_id',
            DB::raw('SUM(final_amount) as total_spent')
        )
            ->where('payment_status_id', 2)
            ->where('order_status_id', 5)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->with('user:id,name,email')
            ->take(5)
            ->get();
    }

    // Lấy top sản phẩm bán chạy nhất theo khoảng thời gian
    private function getTopSellingProductsByDateRange($startDate, $endDate)
    {
        return OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_sold')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status_id', 5)
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product:id,name,main_image')
            ->take(5)
            ->get();
    }

    // Lấy tỉ lệ đơn hủy theo khoảng thời gian
    private function getCancellationRate($startDate, $endDate)
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $canceledOrders = Order::where('order_status_id', 9)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return ($totalOrders > 0) ? ($canceledOrders / $totalOrders) * 100 : 0;
    }

    // Tỉ lệ khách hàng đăng nhập mua
    private function getLoginPurchaseRate($startDate, $endDate)
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $loginOrders = Order::where('user_id', '!=', null)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return ($totalOrders > 0) ? ($loginOrders / $totalOrders) * 100 : 0;
    }

    // Tỉ lệ khách hàng không đăng nhập mua
    private function getGuestPurchaseRate($startDate, $endDate)
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $guestOrders = Order::where('user_id', null)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return ($totalOrders > 0) ? ($guestOrders / $totalOrders) * 100 : 0;
    }

    // Thống kê đơn hàng theo thời gian
    private function getOrderStatistics($startDate, $endDate)
    {
        return Order::select(
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(CASE WHEN order_status_id = 1 THEN 1 ELSE 0 END) as pending_orders'),
            DB::raw('SUM(CASE WHEN order_status_id = 2 THEN 1 ELSE 0 END) as confirmed_orders'),
            DB::raw('SUM(CASE WHEN order_status_id = 5 THEN 1 ELSE 0 END) as completed_orders'),
            DB::raw('SUM(CASE WHEN order_status_id = 9 THEN 1 ELSE 0 END) as canceled_orders')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();
    }

    // Thống kê doanh số bán hàng theo thời gian
    private function getSalesStatistics($startDate, $endDate)
    {
        return Order::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw('SUM(final_amount) as totalRevenue'),
            DB::raw('COUNT(id) as totalOrders')
        )
            ->where('order_status_id', 5)
            ->where('payment_status_id', 2)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }

    // Lấy thống kê theo khoảng thời gian
    public function getStatisticsByPeriod($period = null, $startDate = null, $endDate = null)
    {
        // Nếu không có period và startDate, endDate thì mặc định là 7 ngày gần nhất
        if (!$period && !$startDate && !$endDate) {
            $startDate = now()->subDays(7)->toDateString();
            $endDate = now()->toDateString();
            return $this->getTimeBasedStatistics($startDate, $endDate);
        }

        // Nếu có period thì lấy thống kê theo period
        if ($period) {
            switch ($period) {
                case 'today':
                    return $this->getTodayStatistics();
                case 'yesterday':
                    return $this->getYesterdayStatistics();
                case 'this-week':
                    return $this->getThisWeekStatistics();
                case 'last-week':
                    return $this->getLastWeekStatistics();
                case 'this-month':
                    return $this->getThisMonthStatistics();
                case 'last-month':
                    return $this->getLastMonthStatistics();
                case 'this-quarter':
                    return $this->getThisQuarterStatistics();
                case 'last-quarter':
                    return $this->getLastQuarterStatistics();
                case 'this-year':
                    return $this->getThisYearStatistics();
                case 'last-year':
                    return $this->getLastYearStatistics();
                default:
                    // Nếu period không hợp lệ, mặc định là 7 ngày gần nhất
                    $startDate = now()->subDays(7)->toDateString();
                    $endDate = now()->toDateString();
                    return $this->getTimeBasedStatistics($startDate, $endDate);
            }
        }

        // Nếu có startDate và endDate thì lấy thống kê theo khoảng thời gian
        if ($startDate && $endDate) {
            return $this->getTimeBasedStatistics($startDate, $endDate);
        }

        // Mặc định là 7 ngày gần nhất
        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();
        return $this->getTimeBasedStatistics($startDate, $endDate);
    }
}
