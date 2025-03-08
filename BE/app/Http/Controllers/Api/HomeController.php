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
        $keyword = $request->input('keyword');

        if (empty($keyword)) {
            return response()->json(['message' => 'Vui lòng nhập từ khóa tìm kiếm!'], 400);
        }

        $products = Product::with(['library', 'variants'])
            ->where('name', 'like', '%' . $keyword . '%')
            ->orWhere('slug', 'like', '%' . $keyword . '%')
            ->orWhere('description', 'like', '%' . $keyword . '%')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm!'], 404);
        }

        // 4. Phân trang thủ công
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 9;
        $pagedData = $products->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $products->getCollection()->transform(function ($product) {
            // Xử lý ảnh
            $product->url = $product->main_image && $product->library
                ? Product::getConvertImage($product->library->url, 100, 100, 'thumb') : null;

            // Xử lý giá
            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            return $product;
        });

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
