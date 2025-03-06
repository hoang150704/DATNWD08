<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductDetailController extends Controller
{
    public function show(string $id)
    {
        try {
            // Lấy sản phẩm với đầy đủ biến thể, thuộc tính và hình ảnh
            $product = Product::with([
                'variants.values.attributeValue.attribute',
                'categories',
                'productImages',
                'library'
            ])->findOrFail($id);
    
            // Chuyển đổi dữ liệu sản phẩm
            $convertData = [
                "id" => $product->id,
                "name" => $product->name,
                "description" => $product->description,
                "short_description" => $product->short_description,
                "url_main_image" => $product->main_image ? Product::getConvertImage($product->library->url, 800, 800, 'thumb') : "",
                "type" => $product->type,
                "slug" => $product->slug,
            ];
    
            // Danh sách biến thể (variants)
            $convertData['variants'] = $product->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'regular_price' => $variant->regular_price,
                    'sale_price' => $variant->sale_price,
                    'weight' => $variant->weight,
                    'stock_quantity' => $variant->stock_quantity,
                    'values' => $variant->values->map(function ($value) {
                        return [
                            'attribute_id' => $value->attributeValue->attribute->id,  // Lấy ID của thuộc tính
                            'attribute_name' => $value->attributeValue->attribute->name, // Lấy tên thuộc tính
                            'attribute_value_id' => $value->attributeValue->id, // Lấy ID của giá trị thuộc tính
                            'value' => $value->attributeValue->name, // Lấy tên giá trị thuộc tính
                        ];
                    })
                ];
            });
    
            // Nhóm thuộc tính động (để hiển thị trên giao diện)
            $convertData['attributes'] = $product->variants->flatMap(function ($variant) {
                return $variant->values->map(function ($value) {
                    return [
                        'attribute_id' => $value->attributeValue->attribute->id,
                        'attribute_name' => $value->attributeValue->attribute->name,
                        'attribute_value_id' => $value->attributeValue->id,
                        'value' => $value->attributeValue->name,
                    ];
                });
            })->groupBy('attribute_name')->map(function ($items, $attribute_name) {
                return [
                    'attribute_id' => $items->first()['attribute_id'], // Lấy ID của thuộc tính
                    'attribute_name' => $attribute_name,
                    'values' => $items->map(function ($item) {
                        return [
                            'attribute_value_id' => $item['attribute_value_id'], // Lấy ID của giá trị thuộc tính
                            'value' => $item['value'], // Lấy tên giá trị thuộc tính
                        ];
                    })->unique('attribute_value_id')->values()->toArray()
                ];
            })->values();
    
            // Danh mục sản phẩm
            $convertData['categories'] = $product->categories->pluck('name')->toArray();
    
            // Hình ảnh sản phẩm
            $convertData['product_images'] = $product->productImages->map(function ($image) {
                return [
                    'url' => Product::getConvertImage($image->url, 800, 800, 'thumb')
                ];
            });
    
            return response()->json($convertData, 200);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage()
            ], 500);
        }
    }
    
     
    
}
