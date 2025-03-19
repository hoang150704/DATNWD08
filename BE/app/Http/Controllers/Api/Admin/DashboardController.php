<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private Category $categories;
    private Product $products;
    private User $users;
    private Voucher $vouchers;

    public function __construct()
    {
        $this->categories = new Category();
        $this->products = new Product();
        $this->users = new User();
        $this->vouchers = new Voucher();
    }

    //  API: Lấy dữ liệu tổng quan của dashboard

    public function dashboard(Request $request)
    {
        $statisticBy = $request->query('statisticBy', '7day'); // Mặc định là 7 ngày
        $salesData = $this->getSalesStatistics($statisticBy);
        $topSellingProducts = $this->getTopSellingProducts();

        return response()->json([
            "status" => "success",
            "message" => "Lấy dữ liệu dashboard thành công!",
            "data" => [
                "totalCategories" => $this->categories->count(),
                "totalProducts" => $this->products->count(),
                "totalUsers" => $this->users->count(),
                "totalVouchers" => $this->vouchers->count(),
                "topSellingProducts" => $topSellingProducts,
                "salesStatistics" => $salesData,
                "statisticBy" => $statisticBy
            ]
        ], 200);
    }

    //  * Lấy danh sách sản phẩm bán chạy nhất (top 5)
    private function getTopSellingProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with('product:id,name,main_image')
            ->get();
    }

    //  * Lấy thống kê doanh số bán hàng theo thời gian
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
            case '6month':
                $query->where('created_at', '>=', now()->subMonths(6));
                break;
        }

        return $query->get();
    }
    public function getProductByCategory()
    {
        $data = Category::select(
            'categories.id',
            'categories.name',
            DB::raw('COALESCE(COUNT(product_category_relations.product_id), 0) as total_products') // Nếu NULL thì thay bằng 0
        )
            ->leftJoin('product_category_relations', 'categories.id', '=', 'product_category_relations.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_products')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Lấy thống kê sản phẩm theo danh mục thành công!',
            'data' => $data
        ], 200);
    }
}
