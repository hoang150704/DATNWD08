<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getLatestProducts()
    {
        // Lấy 8 sản phẩm mới nhất với các mối quan hệ
        $products = Product::with(['library', 'variants'])
            ->latest()
            ->limit(8)
            ->get();

        // xử lý hiển thị ảnh
        foreach ($products as $key => $value) {
            if ($value->main_image == null) {
                $products[$key]['url'] = null;
            } else {
                $url = Product::getConvertImage($value->library->url, 100, 100, 'thumb');
                $products[$key]['url'] = $url;
            }
        }

        // Thêm price từ biến thể sản phẩm
        if ($value->variants->isNotEmpty()) {
            $products[$key]['regular_price'] = $value->variants->first()->regular_price;
            $products[$key]['sale_price'] = $value->variants->first()->sale_price;
        } else {
            $products[$key]['regular_price'] = null;
            $products[$key]['sale_price'] = null;
        }

        return response()->json($products, 200);
    }

    public function getAllCategories()
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }

    public function getTopComments()
    {
        // Lấy danh sách sản phẩm có rating trung bình cao nhất (top 5)
        $topRatedProducts = Product::withAvg('comments', 'rating') // Lấy trung bình rating của bình luận
            ->orderByDesc('comments_avg_rating') // Sắp xếp giảm dần theo rating trung bình
            ->take(5)
            ->pluck('id'); // Lấy danh sách ID sản phẩm

        // Lấy danh sách sản phẩm có nhiều lượt bình luận nhất (top 5)
        $mostCommentedProducts = Product::withCount('comments') // Đếm số bình luận
            ->orderByDesc('comments_count') // Sắp xếp giảm dần theo số bình luận
            ->take(5)
            ->pluck('id'); // Lấy danh sách ID sản phẩm

        // Gộp danh sách các sản phẩm tiêu biểu
        $featuredProductIds = $topRatedProducts->merge($mostCommentedProducts)->unique();

        // Lấy bình luận từ các sản phẩm tiêu biểu
        $comments = Comment::whereIn('product_id', $featuredProductIds)
            ->where('is_active', 1) // Chỉ lấy bình luận được kích hoạt
            ->orderByDesc('rating') // Ưu tiên bình luận có rating cao
            ->take(10) // Giới hạn số bình luận
            ->with('user:id,avatar,name,username') // Eager load thông tin người dùng (id, avatar)
            ->get();

        return response()->json($comments, 200);
    }

    public function getProductsByCategory($category_id)
    {
        // Kiểm tra danh mục có tồn tại không
        $category = Category::find($category_id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Lấy danh sách sản phẩm thuộc danh mục đó
        $products = $category->products()
            ->with(['library', 'variants']) // Load thêm ảnh và biến thể sản phẩm
            ->orderBy('created_at', 'desc') // Sắp xếp theo ngày tạo
            ->paginate(8); // Phân trang 10 sản phẩm mỗi lần

        // Xử lý dữ liệu hiển thị
        foreach ($products as $key => $product) {
            if ($product->main_image == null) {
                $products[$key]['url'] = null;
            } else {
                $url = Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb');
                $products[$key]['url'] = $url;
            }

            // Thêm giá từ biến thể sản phẩm
            if ($product->variants->isNotEmpty()) {
                $products[$key]['regular_price'] = $product->variants->first()->regular_price;
                $products[$key]['sale_price'] = $product->variants->first()->sale_price;
            } else {
                $products[$key]['regular_price'] = null;
                $products[$key]['sale_price'] = null;
            }
        }

        return response()->json($products, 200);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->input('keyword'); // Lấy từ khóa tìm kiếm từ query string

        if (!$query) {
            return response()->json([
                'message' => 'Please enter a search query.'
            ], 400);
        }

        $products = Product::with(['library', 'variants'])
            ->where('name', 'like', '%' . $query . '%') // Tìm sản phẩm theo tên
            ->paginate(8); // Phân trang 8 sản phẩm mỗi trang

        // Xử lý hiển thị ảnh tương tự như getLatestProducts()
        foreach ($products as $key => $value) {
            if ($value->main_image == null) {
                $products[$key]['url'] = null;
            } else {
                $url = Product::getConvertImage($value->library->url, 100, 100, 'thumb');
                $products[$key]['url'] = $url;
            }

            if ($value->variants->isNotEmpty()) {
                $products[$key]['regular_price'] = $value->variants->first()->regular_price;
                $products[$key]['sale_price'] = $value->variants->first()->sale_price;
            } else {
                $products[$key]['regular_price'] = null;
                $products[$key]['sale_price'] = null;
            }
        }

        return response()->json($products, 200);
    }
}
