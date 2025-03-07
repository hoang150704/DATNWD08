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

        $products->transform(function ($product) {
            $product->url = $product->main_image ? Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb') : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            return $product;
        });

        return response()->json($products, 200);
    }

    public function getParentCategories()
    {
        // Lấy tất cả danh mục cha (parent_id = null)
        $categories = Category::whereNull('parent_id')->get();
        return response()->json($categories, 200);
    }

    public function getProductsByCategory($category_id)
    {
        // Kiểm tra danh mục có tồn tại không
        $category = Category::find($category_id);
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục!'], 404);
        }

        // Lấy danh sách sản phẩm thuộc danh mục đó
        $products = $category->products()
            ->with(['library', 'variants']) // Load thêm ảnh và biến thể sản phẩm
            ->orderBy('created_at', 'desc') // Sắp xếp theo ngày tạo
            ->paginate(8);

        $products->transform(function ($product) {
            $product->url = $product->main_image ? Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb') : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            return $product;
        });

        return response()->json($products, 200);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->input('keyword'); // Lấy từ khóa tìm kiếm từ query string

        if (!$query) {
            return response()->json([
                'message' => 'Vui lòng nhập từ khóa!'
            ], 400);
        }

        $products = Product::with(['library', 'variants'])
            ->where('name', 'like', '%' . $query . '%') // Tìm sản phẩm theo tên
            ->paginate(9);

        // Xử lý hiển thị ảnh
        foreach ($products as $key => $value) {
            if ($value->main_image == null || !$value->library) {
                $products[$key]['url'] = null;
            } else {
                $url = Product::getConvertImage($value->library->url, 100, 100, 'thumb');
                $products[$key]['url'] = $url;
            }

            // Xử lý giá
            $price = $this->getVariantPrice($value);
            $products[$key]['regular_price'] = $price['regular_price'];
            $products[$key]['sale_price'] = $price['sale_price'];
        }

        return response()->json($products, 200);
    }

    private function getVariantPrice($product)
    {
        if ($product->variants->isNotEmpty()) {
            $latestVariant = $product->variants()->latest()->first();

            return [
                'regular_price' => $latestVariant->regular_price,
                'sale_price' => $latestVariant->sale_price ?? null,
            ];
        }

        return [
            'regular_price' => null,
            'sale_price' => null,
        ];
    }
}
