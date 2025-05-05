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
            $product->url = $product->main_image
                ? Product::getConvertImage($product->library->url ?? '', 250, 250, 'thumb')
                : null;

            $price = $this->getVariantPrice($product);
            $product->regular_price = $price['regular_price'];
            $product->sale_price = $price['sale_price'];

            // Gán final_price
            if (!is_null($product->sale_price)) {
                $product->final_price = $product->sale_price;
            } else {
                $product->final_price = $product->regular_price;
            }

            return $product;
        });

        // Sắp xếp theo yêu cầu
        if ($sort === 'price_asc') {
            $products = $products->sortBy('final_price')->values();
        } elseif ($sort === 'price_desc') {
            $products = $products->sortByDesc('final_price')->values();
        } else {
            $products = $products->sortByDesc('created_at')->values();
        }

        // Chỉ trả về 3 trường giá và thông tin cần thiết
        $filteredProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'url' => $product->url,
                'slug' => $product->slug,
                'regular_price' => $product->regular_price,
                'sale_price' => $product->sale_price,
                'final_price' => $product->final_price,
            ];
        });

        return response()->json($filteredProducts, 200);
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

}
