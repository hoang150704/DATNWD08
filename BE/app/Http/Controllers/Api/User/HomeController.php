<?php

namespace App\Http\Controllers\Api\User;

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
            return response()->json(['message' => 'Vui lòng nhập từ khóa!'], 400);
        }
    
        // Chuẩn hóa từ khóa tìm kiếm (bỏ dấu)
        $normalizedKeyword = $this->normalizeVietnamese($keyword);

        // Tìm kiếm sản phẩm với tên đã được chuẩn hóa
        $products = Product::with(['library', 'variants'])
            ->whereRaw('name LIKE ?', ['%'.$normalizedKeyword.'%'])
            ->get();
    
        if ($products->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm!'], 200);
        }
    
        // Phân trang thủ công
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
    
        // Xử lý hình ảnh và giá
        $products->getCollection()->transform(function ($product) {
            $product->url = $product->main_image && $product->library
                ? Product::getConvertImage($product->library->url, 100, 100, 'thumb')
                : null;
    
            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'] ?? null;
            $product->sale_price = $price['sale_price'] ?? null;
    
            return $product;
        });
    
        return response()->json($products, 200);
    }
    
    /**
     * Chuẩn hóa chuỗi tiếng Việt (bỏ dấu)
     */
    public function normalizeVietnamese($string)
    {
        $unicode = array(
            'a' => '/á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ|á|à|ả|ã|ạ|ắ|ằ|ẳ|ẵ|ặ|ấ|ầ|ẩ|ẫ|ậ/iu',
            'e' => '/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/iu',
            'i' => '/í|ì|ỉ|ĩ|ị/iu',
            'o' => '/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/iu',
            'u' => '/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/iu',
            'y' => '/ý|ỳ|ỷ|ỹ|ỵ/iu',
            'd' => '/đ/iu',
            'A' => '/Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ|Á|À|Ả|Ã|Ạ|Ắ|Ằ|Ẳ|Ẵ|Ặ|Ấ|Ầ|Ẩ|Ẫ|Ậ/iu',
            'E' => '/É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ/iu',
            'I' => '/Í|Ì|Ỉ|Ĩ|Ị/iu',
            'O' => '/Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ/iu',
            'U' => '/Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự/iu',
            'Y' => '/Ý|Ỳ|Ỷ|Ỹ|Ỵ/iu',
            'D' => '/Đ/iu'
        );
    
        foreach ($unicode as $nonUnicode => $pattern) {
            $string = preg_replace($pattern, $nonUnicode, $string);
        }
    
        return $string;
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
