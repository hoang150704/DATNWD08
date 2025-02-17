<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductVariationRequest;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
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
        //
        $listProductVariant = Product::with('variants:id,product_id,regular_price,sale_price,sku,variant_image,stock_quantity','variants.values:variation_id,attribute_value_id','variants.values.attributeValue:id,name','productAttributes')->select('id','name','type')->findOrFail($idProduct);
        // if(){

        // }
        $convertData = [
            'id'=>$listProductVariant->id,
            'name'=>$listProductVariant ->name,
            'type'=>$listProductVariant->type
        ];
        foreach($listProductVariant->variants as $variant){
            $convertData['variants'][$variant->id]=[
                'id'=>$variant->id,
                'sku'=>$variant->sku,
                'regular_price'=>$variant->regular_price,
                'sale_price'=>$variant->sale_price,
                'stock_quantity'=>$variant->stock_quantity,
                'variant_image'=>$variant->variant_image,
                'url'=>$variant->variant_image == null ? null : Product::getConvertImage($variant->library->url,200,200,'thumb'),
            ];
            foreach($variant->values as $value){
                $convertData['variants'][$variant->id]['values'][]=$value->attributeValue->name;
            }
        }
        $listProductVariant = $convertData;
        return response()->json($listProductVariant,200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductVariationRequest $request,string $idProduct)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $product = Product::findOrFail($idProduct);

            $existingAttributeValues = [];
            foreach ($product->variants as $variant) {
                $existingAttributeValues[] = $variant->values->pluck('attribute_value_id')->toArray();
            }
        
            // Kiểm tra xem mảng 'values' trong yêu cầu có trùng với bất kỳ mảng nào trong $existingAttributeValues không
            //Nếu có trùng nghĩa là biến thể đó đã tồn tại
            foreach ($existingAttributeValues as $existingValues) {
                if (count(array_diff($data['values'], $existingValues)) === 0 && 
                    count(array_diff($existingValues, $data['values'])) === 0) {
                    return response()->json(["message" => "Biến thể bạn chọn đã tồn tại"], 400); 
                }
            }
            //
            $dataVariant = [
                "sku"=>$data['sku'],
                "regular_price"=>$data['regular_price'],
                "sale_price"=>$data['sale_price'],
                "variant_image"=>$data['variant_image'],
                "stock_quantity"=>$data['stock_quantity'],
                "product_id"=>$idProduct
            ];
            $productVariation = ProductVariation::create($dataVariant);
            // 
            foreach($data['values'] as $value){
                $dataProductVariationValue = [
                    "variation_id"=>$productVariation->id,
                    "attribute_value_id"=>$value
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
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id,$idProduct )
    {
        //
        $product_variant = ProductVariation::with('values','values.attributeValue')->findOrFail($id);
        $convertData = [
            "id"=> $product_variant->id,
            "product_id"=> $product_variant->product_id,
            "sku"=> $product_variant->sku,
            "variant_image"=> $product_variant->variant_image,
            'url'=>$product_variant->variant_image == null ? null : Product::getConvertImage($product_variant->library->url,200,200,'thumb'),
            "regular_price"=> $product_variant->regular_price,
            "sale_price"=> $product_variant->sale_price,
            "stock_quantity"=> $product_variant->stock_quantity,
        ];
        foreach($product_variant->values as $key => $value){
            $convertData['values'][$key] = [
                "id"=> $value->id,
                'attribute_id'=>$value->attributeValue->attribute_id,
                "attribute_value_id"=> $value->attribute_value_id,

            ];
            
        }
        return response()->json($convertData,200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id,$idProduct)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $dataVariant = [
                "sku"=>$data['sku'],
                "regular_price"=>$data['regular_price'],
                "sale_price"=>$data['sale_price'],
                "variant_image"=>$data['variant_image'],
                "stock_quantity"=>$data['stock_quantity'],
                "product_id"=>$idProduct
            ];
            $product_variant = ProductVariation::findOrFail($id);
            $product_variant->update($dataVariant);
            foreach($data['values'] as $value){
                $dataVariantValue =[
                    'attribute_value_id'=>$value['attribute_value_id']
                ];
                $variantValue = ProductVariationValue::findOrFail($value['id']);
                $variantValue->update($dataVariantValue);
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
            $product_variant->delete();
            return response()->json([
                "message" => "Đã xóa thành công",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
