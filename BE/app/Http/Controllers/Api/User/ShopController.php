<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function getAllProducts(Request $request)
    {
        $sort = $request->query('sort', 'default');

        // Lấy sản phẩm đang hoạt động
        $products = Product::with(['library', 'variants'])
            ->where('is_active', 1)
            ->get();

        // Xử lý ảnh và giá cho từng sản phẩm
        $products->transform(function ($product) {
            $product->url = $product->main_image ? Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb') : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            return $product;
        });

        // Sắp xếp theo yêu cầu
        if ($sort === 'price_asc') {
            $products = $products->sortBy('regular_price')->values();
        } elseif ($sort === 'price_desc') {
            $products = $products->sortByDesc('regular_price')->values();
        } else {
            // Mặc định: sắp xếp mới nhất
            $products = $products->sortByDesc('created_at')->values();
        }

        // Giới hạn 10 sản phẩm như cũ
        $products = $products->take(10);

        return response()->json($products, 200);
    }



    private function filterByPriceRange($query, $minPrice, $maxPrice)
    {
        // Trường hợp có cả minPrice và maxPrice, lọc các sản phẩm có giá trong khoảng này
        if ($minPrice && $maxPrice) {
            $query->whereHas('variants', function ($q) use ($minPrice, $maxPrice) {
                // Lọc với price (sale_price nếu có, còn không thì regular_price)
                $q->whereRaw('IFNULL(sale_price, regular_price) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
            });
        } elseif ($minPrice) {
            // Trường hợp chỉ có minPrice, lọc các sản phẩm có giá >= minPrice
            $query->whereHas('variants', function ($q) use ($minPrice) {
                $q->whereRaw('IFNULL(sale_price, regular_price) >= ?', [$minPrice]);
            });
        } elseif ($maxPrice) {
            // Trường hợp chỉ có maxPrice, lọc các sản phẩm có giá <= maxPrice
            $query->whereHas('variants', function ($q) use ($maxPrice) {
                $q->whereRaw('IFNULL(sale_price, regular_price) <= ?', [$maxPrice]);
            });
        }

        return $query;
    }

    private function sortByPrice($products, $sortBy)
    {
        if ($sortBy == 'high_to_low') {
            return $products->sortByDesc(function ($product) {
                $priceData = $this->getVariantPrice($product);
                return $priceData['sale_price'] ?? $priceData['regular_price']; // Ưu tiên sale_price
            });
        } elseif ($sortBy == 'low_to_high') {
            return $products->sortBy(function ($product) {
                $priceData = $this->getVariantPrice($product);
                return $priceData['sale_price'] ?? $priceData['regular_price']; // Ưu tiên sale_price
            });
        }

        return $products;
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


    public function getAllCategories()
    {
        // Lấy tất cả danh mục cha và kèm theo danh mục con (children)
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return response()->json($categories, 200);
    }

    public function getProductsByCategory($category_id, Request $request)
    {
        // Kiểm tra danh mục có tồn tại không
        $sortBy = $request->input('sort_by', 'default'); // Mặc định là 'default'
        $minPrice = $request->input('min_price', null); // Giá tối thiểu
        $maxPrice = $request->input('max_price', null); // Giá tối đa
        $category = Category::find($category_id);
        if (!$category) {
            return response()->json(['message' => 'Danh mục không tồn tại!'], 404);
        }

        // Lấy danh sách sản phẩm thuộc danh mục đó
        $products = $category->products()
            ->with(['library', 'variants']) // Load thêm ảnh và biến thể sản phẩm
            ->orderBy('created_at', 'desc') // Sắp xếp theo ngày tạo
            ->paginate(9);

        // Xử lý hiển thị hình ảnh và giá sản phẩm
        $products->getCollection()->transform(function ($product) {
            $product->url = $product->main_image ? Product::getConvertImage($product->library->url ?? '', 100, 100, 'thumb') : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            return $product;
        });

        return response()->json($products, 200);
    }
}
