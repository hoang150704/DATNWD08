<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductVariationRequest;
use App\Http\Requests\Admin\Product\UpdateProductVariationRequest;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use App\Services\ProductVariation as ServicesProductVariation;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductVariationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $idProduct)
    {
        $listProductVariant = Product::with(
            'variants:id,weight,product_id,regular_price,sale_price,sku,variant_image,stock_quantity,deleted_at',
            'variants.values:variation_id,attribute_value_id',
            'variants.values.attributeValue:id,name',
            'productAttributes'
        )->select('id', 'name', 'type')->findOrFail($idProduct);

        if ($listProductVariant->type == 1) {
            return response()->json(['message' => 'Đây không phải sản phẩm biến thể']);
        }

        $convertData = [
            'id' => $listProductVariant->id,
            'name' => $listProductVariant->name,
            'type' => $listProductVariant->type,
            'variants' => [] // Chuyển đổi variants thành mảng
        ];

        foreach ($listProductVariant->variants as $variant) {
            $variantData = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'regular_price' => $variant->regular_price,
                'sale_price' => $variant->sale_price,
                'stock_quantity' => $variant->stock_quantity,
                'variant_image' => $variant->variant_image,
                'url' => $variant->variant_image == null ? null : Product::getConvertImage($variant->library->url, 200, 200, 'thumb'),
                'values' => []
            ];

            foreach ($variant->values as $value) {
                $variantData['values'][] = $value->attributeValue->name;
            }

            $convertData['variants'][] = $variantData; // Thêm object vào mảng variants
        }

        return response()->json($convertData, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductVariationRequest $request, string $idProduct)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $product = Product::findOrFail($idProduct);
            // Kiểm tra xem có phải sản phẩm biến thể không
            if ($product->type == 1) {
                return response()->json(['message' => 'Đây không phải sản phẩm biến thể'], 422);
            }
            //
            $existingAttributeValues = [];
            foreach ($product->variants as $variant) {
                $existingAttributeValues[] = $variant->values->pluck('attribute_value_id')->toArray();
            }

            // Kiểm tra xem mảng 'values' trong yêu cầu có trùng với bất kỳ mảng nào trong $existingAttributeValues không
            //Nếu có trùng nghĩa là biến thể đó đã tồn tại
            foreach ($existingAttributeValues as $existingValues) {
                if (
                    count(array_diff($data['values'], $existingValues)) === 0 &&
                    count(array_diff($existingValues, $data['values'])) === 0
                ) {
                    return response()->json(["message" => "Biến thể bạn chọn đã tồn tại"], 400);
                }
            }
            //
            $dataVariant = [
                "sku" => $data['sku'] ?? null,
                "regular_price" => $data['regular_price'] ?? 0,
                "sale_price" => $data['sale_price'] ?? null,
                "variant_image" => $data['variant_image'] ?? null,
                "stock_quantity" => $data['stock_quantity'] ?? 0,
                "product_id" => $idProduct,
                "weight" => $data['weight']
            ];
            $productVariation = ProductVariation::create($dataVariant);
            // 

            foreach ($data['values'] as $value) {
                $dataProductVariationValue = [
                    "variation_id" => $productVariation->id,
                    "attribute_value_id" => $value
                ];
                ProductVariationValue::create($dataProductVariationValue);
            }
            // Hoàn thành
            DB::commit();
            return response()->json(['message' => 'Bạn đã thêm biến thể thành công'], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                "message" => "Lỗi hệ thống 2 1",
                "error" => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $idProduct, $id)
    {
        //
        $product_variant = ProductVariation::with('values', 'values.attributeValue')->findOrFail($id);
        $convertData = [
            "id" => $product_variant->id,
            "product_id" => $product_variant->product_id,
            "sku" => $product_variant->sku,
            "variant_image" => $product_variant->variant_image,
            'url' => $product_variant->variant_image == null ? null : Product::getConvertImage($product_variant->library->url, 200, 200, 'thumb'),
            "regular_price" => $product_variant->regular_price,
            "sale_price" => $product_variant->sale_price,
            "weight" => $product_variant->weight,
            "stock_quantity" => $product_variant->stock_quantity,
        ];
        foreach ($product_variant->values as $key => $value) {
            $convertData['values'][$key] = [
                "id" => $value->id,
                'attribute_id' => $value->attributeValue->attribute_id,
                "attribute_value_id" => $value->attribute_value_id,
                "name" => $value->attributeValue->name,

            ];
        }
        return response()->json($convertData, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductVariationRequest $request, $idProduct, string $id)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $dataVariant = [
                "sku" => $data['sku'] ?? null,
                "regular_price" => $data['regular_price'] ?? 0,
                "sale_price" => $data['sale_price'] ?? null,
                "variant_image" => $data['variant_image'] ?? null,
                "stock_quantity" => $data['stock_quantity'] ?? 0,
                "product_id" => $idProduct,
                "weight" => $data['weight'] ?? 0
            ];
            $product_variant = ProductVariation::findOrFail($id);
            $product = Product::findOrFail($idProduct);
            // Kiểm tra xem có phải sản phẩm biến thể không
            if ($product->type == 1) {
                return response()->json(['message' => 'Đây không phải sản phẩm biến thể'], 422);
            }
            //
            $existingAttributeValues = [];
            foreach ($product->variants->where('id', '!=', $id) as $variant) {
                $existingAttributeValues[] = $variant->values->pluck('attribute_value_id')->toArray();
            }
            //
            $attributeValueIds = array_column($data['values'], 'attribute_value_id');
            // Kiểm tra xem mảng 'values' trong yêu cầu có trùng với bất kỳ mảng nào trong $existingAttributeValues không
            //Nếu có trùng nghĩa là biến thể đó đã tồn tại
            foreach ($existingAttributeValues as $existingValues) {
                if (
                    count(array_diff($attributeValueIds, $existingValues)) === 0 &&
                    count(array_diff($existingValues, $attributeValueIds)) === 0
                ) {
                    return response()->json(["message" => "Biến thể bạn chọn đã tồn tại"], 400);
                }
            }
            $product_variant->update($dataVariant);

            // Xoá tất cả giá trị cũ của biến thể đang sửa
            $product_variant->values()->delete();

            // Tạo lại từ data gửi lên
            foreach ($data['values'] as $value) {
                $product_variant->values()->create([
                    'attribute_value_id' => $value['attribute_value_id']
                ]);
            }
            // Hoàn thành
            DB::commit();
            return response()->json(['message' => 'Bạn đã sửa biến thể thành công'], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $product_variant = ProductVariation::findOrFail($id);
            $usedVariants = app(ServicesProductVariation::class)
            ->checkVariantUsedInActiveOrders([$id]);

        if (in_array($id, $usedVariants)) {
            return response()->json([
                'message' => "Biến thể đang được sử dụng trong đơn hàng, không thể xóa."
            ], 422);
        }
            ProductVariationValue::where('variation_id', $id)->delete();
            $product_variant->delete();
            DB::commit();
            return response()->json(['message' => "Bạn đã xóa thành công"], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "message" => "Lỗi hệ thống",
                'id' => $id,
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function list($idProduct)
    {
        try {
            // Lấy danh sách biến thể sản phẩm cùng với giá trị thuộc tính
            $listProductVariant = ProductVariation::with('values.attributeValue')
                ->where('product_id', $idProduct)
                ->get();

            // Chuyển đổi dữ liệu sang format mong muốn
            $formattedVariants = $listProductVariant->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'values' => $variant->values->map(fn($value) => $value->attributeValue->name)
                ];
            });

            return response()->json($formattedVariants, 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $e->getMessage()
            ], 500);
        }
    }

}
