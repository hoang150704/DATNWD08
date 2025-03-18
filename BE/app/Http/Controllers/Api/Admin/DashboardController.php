<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        try {
            $data = [
                'total_categories' => Category::count(),
                'total_products' => Product::count(),
                'total_users' => User::count(),
                'total_vouchers' => Voucher::count(),
                'products_by_category' => Category::withCount('products')->get(['id', 'name', 'products_count']),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
