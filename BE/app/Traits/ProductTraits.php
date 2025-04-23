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
use Symfony\Component\CssSelector\Node\AttributeNode;

trait ProductTraits
{
    // Generate SKU based on product name and attributes
    private function generateSku($productName, $attributes = [])
    {
        // Get first 3 letters of product name
        $namePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $productName), 0, 3));
        
        // Get current timestamp
        $timestamp = time();
        
        // Get random 3 digits
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        // If there are attributes, add their first letters
        $attributePrefix = '';
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attributeValue = AttributeValue::find($attribute);
                if ($attributeValue) {
                    $attributePrefix .= strtoupper(substr($attributeValue->name, 0, 1));
                }
            }
        }
        
        // Combine all parts
        $sku = $namePrefix . $attributePrefix . $timestamp . $random;
        
        // Check if SKU exists
        while (ProductVariation::where('sku', $sku)->exists()) {
            $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $sku = $namePrefix . $attributePrefix . $timestamp . $random;
        }
        
        return $sku;
    }

    // Calculate weight based on product type and attributes
    private function calculateWeight($productType, $attributes = [])
    {
        // Default weight for simple products
        $baseWeight = 100; // grams
        
        // If it's a variant product, add weight based on attributes
        if ($productType == 2 && !empty($attributes)) {
            $additionalWeight = 0;
            foreach ($attributes as $attribute) {
                $attributeValue = AttributeValue::find($attribute);
                if ($attributeValue) {
                    // Add 50g for each attribute value
                    $additionalWeight += 50;
                }
            }
            return $baseWeight + $additionalWeight;
        }
        
        return $baseWeight;
    }

    //Xử lí thêm sản phẩm đơn giản
    private function createBasicProduct($dataVariants, $idProduct)
    {
        $product = Product::find($idProduct);
        foreach ($dataVariants as $variant) {
            $data = [
                'product_id' => $idProduct,
                'weight' => $variant['weight'] ?? $this->calculateWeight($product->type),
                'regular_price' => $variant['regular_price'],
                'sale_price' => $variant['sale_price'],
                'stock_quantity' => $variant['stock_quantity'] ?? 0,
                'sku' => $variant['sku'] ?? $this->generateSku($product->name),
            ];
            ProductVariation::create($data);
        }
    }

    //Xử lí thêm sản phẩm biến thể 
    private function createVariantProduct($dataVariants, $dataAttributes, $idProduct)
    {
        $product = Product::find($idProduct);
        foreach ($dataVariants as $variant) {
            $dataVariants = [
                'product_id' => $idProduct,
                'regular_price' => $variant['regular_price'],
                'stock_quantity' => $variant['stock_quantity'],
                'weight' => $variant['weight'] ?? $this->calculateWeight($product->type, $variant['values'] ?? []),
                'sku' => $variant['sku'] ?? $this->generateSku($product->name, $variant['values'] ?? []),
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
        $mergedValues = collect($dataAttributes)->flatten()->unique()->values()->toArray();
        foreach($mergedValues as $valueAttribute){
            $attributeValue = AttributeValue::with('attribute')->findOrFail($valueAttribute);
            $convertData = [
                'product_id'=>$idProduct,
                'attribute_id'=> $attributeValue->attribute->id,
                'attribute_value_id'=>$valueAttribute,
            ];
            ProductAttribute::create($convertData);
        }
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
                'weight'=>$variant['weight'],
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
                'weight'=>$variant['weight'],
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
