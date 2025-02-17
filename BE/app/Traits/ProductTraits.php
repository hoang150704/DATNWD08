<?php

namespace App\Traits;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCategoryRelation;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Support\Str;

trait ProductTraits
{
    //Xử lí thêm sản phẩm đơn giản
    private function createBasicProduct($dataVariants, $idProduct)
    {
        foreach ($dataVariants as $variant) {
            $data = [
                'product_id' => $idProduct,
                'regular_price' => $variant['regular_price'],
                'sale_price' => $variant['sale_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'sku' => $variant['sku'],
            ];
        }
        ProductVariation::create($data);
    }

    //Xử lí thêm sản phẩm biến thể 
    private function createVariantProduct($dataVariants, $idProduct)
    {
        $attributeMap = [];
        foreach ($dataVariants as $variant) {
            // Xử lí dữ liệu lấy ra attribute
            $attributes = AttributeValue::whereIn('id', $variant['values'])
            ->pluck('attribute_id', 'id');
            foreach ($attributes as $attribute_value_id => $attribute_id) {
                if (!isset($attributeMap[$attribute_id])) {
                    $attributeMap[$attribute_id] = [];
                }
                if (!in_array($attribute_value_id, $attributeMap[$attribute_id])) {
                    $attributeMap[$attribute_id][] = $attribute_value_id;
                }
            }
            ///
            $dataVariants = [
                'product_id' => $idProduct,
                'regular_price' => $variant['regular_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'sku' => $variant['sku'],
            ];
            $variantNew = ProductVariation::create($dataVariants);
            foreach ($variant['values'] as $key => $value) {
                $dataValue = [
                    'variation_id' => $variantNew->id,
                    'attribute_value_id' => $value
                ];
                ProductVariationValue::create($dataValue);
            }
        }
                    //Thêm vapf bảng attribute
                    foreach($attributeMap as $key=>$values){
                        foreach($values as $value){
                            $dataProductAttribute =[
                                "product_id"=>$idProduct,
                                "attribute_id"=>$key,
                                "attribute_value_id"=>$value,
                            ];
                            ProductAttribute::create($dataProductAttribute );
                        }
                    }
        return $attributeMap;
    }
    // Xử lí slug
    private function handleSlug($data, $type, $idProduct = null)
    {
        if ($type == 'create') {
            $baseSlug = Str::slug($data); // Chuyển thành slug chuẩn

            // Lấy tất cả slug có dạng "hoang" hoặc "hoang-{số}"
            $existingSlugs = Product::where('slug', 'LIKE', "$baseSlug%")
                ->pluck('slug')
                ->toArray();

            if (!in_array($baseSlug, $existingSlugs)) {
                return $baseSlug; // Nếu slug gốc chưa tồn tại, dùng nó luôn
            }

            // Lọc ra các slug có số cuối cùng và tìm số lớn nhất
            $maxNumber = 0;
            foreach ($existingSlugs as $slug) {
                if (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $slug, $matches)) {
                    $maxNumber = max($maxNumber, (int)$matches[1]);
                }
            }

            return $baseSlug . '-' . ($maxNumber + 1);
        } elseif ($type == 'update') {
            // Tạo slug ban đầu
            $baseSlug = Str::slug($data);

            // Lấy tất cả slug 
            $existingSlugs = Product::where('slug', 'LIKE', "$baseSlug%")
                ->where('id', '!=', $idProduct) // Nếu update thì bỏ qua chính nó
                ->pluck('slug')
                ->toArray();

            // Nếu chưa có slug trùng, dùng luôn
            if (!in_array($baseSlug, $existingSlugs)) {
                return $baseSlug;
            }

            // Lọc ra số cuối cùng
            $maxNumber = 0;
            foreach ($existingSlugs as $slug) {
                if (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $slug, $matches)) {
                    $maxNumber = max($maxNumber, (int)$matches[1]);
                }
            }

            // Tạo slug mới với số lớn nhất + 1
            return $baseSlug . '-' . ($maxNumber + 1);
        }
    }

    //Xử lí thêm ảnh
    private function addImages($data, $idProduct)
    {
        if (!empty($data)) {
            foreach ($data as $image) {
                $dataProductImage = [
                    'product_id' => $idProduct,
                    'library_id' => $image
                ];

                ProductImage::create($dataProductImage);
            }
        }
    }

    //Xử lí thêm danh mục
    private function addCategories($data, $idProduct)
    {
        foreach ($data as $category) {
            $dataProductImage = [
                'product_id' => $idProduct,
                'category_id' => $category
            ];
            ProductCategoryRelation::create($dataProductImage);
        }
    }

    //Xử lí update sản phẩm đơn giản
    private function updateBasicProduct($data, $id)
    {
        foreach ($data as $variant) {
            $dataNoVariants = [
                'product_id' => $id,
                'regular_price' => $variant['regular_price'],
                'sale_price' => $variant['sale_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'sku' => $variant['sku'],
            ];

            $productBasic = ProductVariation::findorFail($variant['variant_id']);
            $productBasic->update($dataNoVariants);
        }
    }

    //Xử lí update product biến thể
    private function updateVariantProduct($data, $id)
    {
        foreach ($data as $variant) {
            $dataVariants = [
                'product_id' => $id,
                'regular_price' => $variant['regular_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'sku' => $variant['sku'],
            ];
            $productVari = ProductVariation::findorFail($variant['variant_id']);
            $productVari->update($dataVariants);
        }
    }

    //Ẩn các biến thể cũ (basic or variant)
    private function deletProductVaration($product)
    {
        $productBasics = $product->variants;
        foreach ($productBasics as $value) {
            $productBasic = ProductVariation::findorFail($value['id']);
            $productBasic->delete();
        }
        ProductAttribute::where('product_id', $product->id)->delete();

    }
}
