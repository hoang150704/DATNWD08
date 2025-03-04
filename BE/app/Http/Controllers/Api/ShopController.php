<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function getAllProducts(Request $request)
    {
        // Lấy giá trị sort_by và price range từ request
        $sortBy     = $request->input('sort_by', 'default'); // Mặc định là 'default'
        $minPrice   = $request->input('min_price', null); // Giá tối thiểu
        $maxPrice   = $request->input('max_price', null); // Giá tối đa

        // Bắt đầu xây dựng query cho products
        $query = Product::with(['library', 'variants']);

        // 1. Xử lý lọc theo khoảng giá
        $query = $this->filterByPriceRange($query, $minPrice, $maxPrice);

        // 2. Lọc và phân trang
        $products = $this->getPaginatedProducts($query, 9, $request->input('page', 1));

        // 3. Xử lý sắp xếp theo giá hoặc đánh giá (nếu có)
        $products = $this->sortByPrice($products, $sortBy);

        if ($sortBy == 'top_rated') {
            $products = Product::withAvg('comments', 'rating')
                ->orderByDesc('comments_avg_rating')
                ->get();
        }

        // Xử lý hiển thị hình ảnh và giá sản phẩm
        foreach ($products as $key => $value) {
            // Kiểm tra nếu sản phẩm không có hình ảnh
            if ($value->main_image == null) {
                $products[$key]['url'] = null;  // Nếu không có hình ảnh chính
            } else {
                // Xử lý hình ảnh nếu có
                $url = Product::getConvertImage($value->library->url, 100, 100, 'thumb');
                $products[$key]['url'] = $url;
            }

            // Lấy giá từ biến thể gần đây nhất
            $price = $this->getVariantPrice($value);
            $products[$key]['price'] = $price;
        }

        return response()->json($products, 200);
    }

    /**
     * Lọc các sản phẩm theo khoảng giá (Price Range)
     *
     * @param $query
     * @param $minPrice
     * @param $maxPrice
     * @return mixed
     */
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

    /**
     * Lọc và phân trang kết quả
     *
     * @param $query
     * @param $perPage
     * @param $page
     * @return mixed
     */
    private function getPaginatedProducts($query, $perPage, $page)
    {
        return $query->paginate($perPage, ['*'], 'page', $page);  // Phân trang với số sản phẩm mỗi trang
    }

    /**
     * Sắp xếp sản phẩm theo giá
     *
     * @param $query
     * @param $sortBy
     * @return mixed
     */
    private function sortByPrice($products, $sortBy)
    {
        if ($sortBy == 'high_to_low') {
            // Sắp xếp theo giá từ cao đến thấp
            return $products->sortByDesc(function ($product) {
                return $this->getVariantPrice($product);
            });
        } elseif ($sortBy == 'low_to_high') {
            // Sắp xếp theo giá từ thấp đến cao
            return $products->sortBy(function ($product) {
                return $this->getVariantPrice($product);
            });
        }

        return $products;
    }

    /**
     * Lấy giá từ biến thể gần đây nhất của sản phẩm.
     *
     * @param Product $product
     * @return mixed
     */
    private function getVariantPrice($product)
    {
        if ($product->variants->isNotEmpty()) {
            $latestVariant = $product->variants->sortByDesc('created_at')->first();

            if ($latestVariant->sale_price) {
                return $latestVariant->sale_price;
            } else {
                return $latestVariant->regular_price;
            }
        }
        return null;
    }

    public function getAllCategories()
    {
        // Lấy tất cả danh mục cha và kèm theo danh mục con (children)
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return response()->json($categories, 200);
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
            ->paginate(9);

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
}
