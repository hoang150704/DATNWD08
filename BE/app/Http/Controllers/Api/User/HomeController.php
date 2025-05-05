<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // public function index()
    // {
    //     try {
    //         // Lấy banner
    //         $banners = Banner::where('is_active', true)->get();
    //         $formattedBanners = $banners->map(function ($banner) {
    //             return [
    //                 'id' => $banner->id,
    //                 'image_url' => $banner->image_url,
    //                 'link' => $banner->link
    //             ];
    //         });

    //         // Lấy danh mục
    //         $categories = Category::where('is_active', true)
    //             ->orderBy('position')
    //             ->get();
    //         $formattedCategories = $categories->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //                 'slug' => $category->slug,
    //                 'image_url' => $category->image_url
    //             ];
    //         });

    //         // Lấy sản phẩm mới
    //         $newProducts = Product::with(['library', 'variants'])
    //             ->where('is_active', 1) // Chỉ lấy sản phẩm đang active
    //             ->orderBy('created_at', 'desc')
    //             ->take(8)
    //             ->get();

    //         $formattedNewProducts = $newProducts->map(function ($product) {
    //             $price = 0;
    //             if ($product->variants->isNotEmpty()) {
    //                 $firstVariant = $product->variants->first();
    //                 $price = $firstVariant->sale_price > 0 ? $firstVariant->sale_price : $firstVariant->regular_price;
    //             }

    //             return [
    //                 'id' => $product->id,
    //                 'name' => $product->name,
    //                 'slug' => $product->slug,
    //                 'price' => $price == 0 ? "Giá liên hệ" : $price,
    //                 'main_image' => $product->main_image ? Product::getConvertImage($product->library->url, 800, 800, 'thumb') : null,
    //             ];
    //         });

    //         // Lấy sản phẩm hot
    //         $hotProducts = Product::with(['library', 'variants'])
    //             ->where('is_active', 1) // Chỉ lấy sản phẩm đang active
    //             ->where('is_hot', true)
    //             ->orderBy('created_at', 'desc')
    //             ->take(8)
    //             ->get();

    //         $formattedHotProducts = $hotProducts->map(function ($product) {
    //             $price = 0;
    //             if ($product->variants->isNotEmpty()) {
    //                 $firstVariant = $product->variants->first();
    //                 $price = $firstVariant->sale_price > 0 ? $firstVariant->sale_price : $firstVariant->regular_price;
    //             }

    //             return [
    //                 'id' => $product->id,
    //                 'name' => $product->name,
    //                 'slug' => $product->slug,
    //                 'price' => $price == 0 ? "Giá liên hệ" : $price,
    //                 'main_image' => $product->main_image ? Product::getConvertImage($product->library->url, 800, 800, 'thumb') : null,
    //             ];
    //         });

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Lấy dữ liệu trang chủ thành công',
    //             'data' => [
    //                 'banners' => $formattedBanners,
    //                 'categories' => $formattedCategories,
    //                 'new_products' => $formattedNewProducts,
    //                 'hot_products' => $formattedHotProducts
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Lỗi hệ thống',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getLatestProducts()
    {
        // Lấy 8 sản phẩm mới nhất với các mối quan hệ
        $products = Product::with(['library', 'variants'])
            ->where('is_active', 1)
            ->latest()
            ->limit(10)
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

    public function discountProduct()
    {
        try {
            $product = ProductVariation::with('product:id,name,main_image,short_description')->whereNotNull('sale_price')->orderBy('sale_price')->first();
            return response()->json([
                'message' => 'Success',
                'data' => $product
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getProductsByCategory($slug)
    {
        $minPrice = request('minPrice');
        $maxPrice = request('maxPrice');
        $sort = request('sort');

        // Kiểm tra danh mục có tồn tại không
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục!'], 404);
        }

        // Lấy danh sách sản phẩm thuộc danh mục đó
        $query = $category->products()
            ->with(['library', 'variants'])
            ->where('is_active', 1);

        $products = $query->get();

        // Xử lý ảnh và giá cho từng sản phẩm
        $products->transform(function ($product) {
            $product->url = $product->main_image
                ? Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb')
                : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];
            $product->final_price = $product->sale_price ?? $product->regular_price;

            return $product;
        });

        // Lọc theo khoảng giá nếu có
        if ($minPrice !== null && $maxPrice !== null) {
            $products = $products->filter(function ($product) use ($minPrice, $maxPrice) {
                return $product->final_price >= $minPrice && $product->final_price <= $maxPrice;
            })->values();
        }

        // Sắp xếp theo final_price
        if ($sort === 'price_asc') {
            $products = $products->sortBy('final_price')->values();
        } elseif ($sort === 'price_desc') {
            $products = $products->sortByDesc('final_price')->values();
        } else {
            $products = $products->sortByDesc('created_at')->values();
        } 

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
