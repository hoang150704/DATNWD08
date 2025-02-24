<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\UpdateProductAttributeRequest;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Dotenv\Exception\ValidationException;

class ProductAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $idProduct)
    {
        try {
            // Lấy danh sách thuộc tính theo product_id
            $productAttributes = ProductAttribute::with("attribute:id,name", "attribute_value:id,attribute_id,name")
                ->select('id', 'attribute_id', 'attribute_value_id', 'product_id')
                ->where("product_id", $idProduct)
                ->get()
                ->groupBy('attribute_id'); // Nhóm theo attribute_id
            $list = ProductAttribute::where("product_id", $idProduct)->get();
            // Định dạng lại dữ liệu
            $formattedData = [
                'product_id' => $idProduct,
                'attributes' => []
            ];

            foreach ($productAttributes as $attributeId => $items) {
                $attribute = $items->first()->attribute; // Lấy thông tin thuộc tính

                $formattedData['attributes'][] = [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'attribute_values' => $items->map(function ($item) {
                        return [
                            'id' => $item->attribute_value->id,
                            'name' => $item->attribute_value->name
                        ];
                    })->unique()->values()->toArray() // Lọc trùng lặp
                ];
            }

            return response()->json($formattedData, 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            //code...

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductAttributeRequest $request, string $idProduct)
    {
        //
        try {
            //
            DB::beginTransaction();
            $validatedData = $request->validated();
            // Kiểm tra xem nó có phải sản phẩm biến thể hay không
            $product = Product::findorFail($idProduct);
            if ($product->type == 1) {
                return response()->json(['message' => 'Đây không phải sản phẩm biến thể'], 422);
            }
            //Lấy ra mảng data parentVariants
            $parentVariants = $validatedData["attribute"]["parentVariants"];
            unset($validatedData["attribute"]["parentVariants"]); // Xóa parentVariants khỏi mảng gốc

            // Gộp các mảng selected
            $selectedValues = [];
            foreach ($validatedData["attribute"] as $key => $value) {
                $selectedValues = array_merge($selectedValues, $value);
            }
            //Lấy các attribute_value_id đã tồn tại trong database với idproduct
            $existingValues = ProductAttribute::where('product_id', $idProduct)
                ->whereIn('attribute_value_id', $selectedValues)
                ->pluck('attribute_value_id')
                ->toArray();

            // Lọc ra các attribute_id mới
            $newValues = array_diff($selectedValues, $existingValues);

            // Lấy ra các attribute_id đã tồn tại
            $existingAttributes = ProductAttribute::where('product_id', $idProduct)
                ->whereIn('attribute_id', $parentVariants)
                ->pluck('attribute_id')
                ->toArray();
            // Lấy ra các attribute id
            $newAttributes = array_diff($parentVariants, $existingAttributes);

            // Nếu mảng có giá trị thì thêm các attribute_value_id mới này vào
            if (!empty($newValues)) {
                foreach ($newValues as $newValue) {
                    $attributeValue = AttributeValue::findOrFail($newValue);
                    $data = [
                        'product_id' => $idProduct,
                        'attribute_id' => $attributeValue->attribute_id,
                        'attribute_value_id' => $newValue
                    ];
                    ProductAttribute::create($data);
                }
            }

            // Sau khi thêm mới thành công tiến hành 
            
            // Nếu các người dùng thêm thuộc tính mới
            if (!empty($newAttributes)) {
                $existingVariations = ProductVariation::where('product_id', $idProduct)
                    ->pluck('id')
                    ->toArray(); // Lấy ra mảng các variantion id có của sản phẩm đó

                // Lặp các thuộc tính mới
                foreach ($newAttributes as $newAttribute) {
                    $newAttributeValue = ProductAttribute::where('attribute_id', $newAttribute)->where('product_id', $idProduct)->first();
                    foreach ($existingVariations as $existingVariation) {
                        $dataValues = [
                            'variation_id' => $existingVariation,
                            'attribute_value_id' => $newAttributeValue->attribute_value_id,
                        ];
                        ProductVariationValue::create($dataValues);
                    }
                }
            }
            //

            // 
            // Hoàn thành
            DB::commit();
            return response()->json(['hehe'], 200);
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
        $productAttribute = ProductAttribute::findOrFail($id);
    
        // Xóa mềm các biến thể có liên quan
        ProductVariation::whereHas('values', function ($query) use ($productAttribute) {
            $query->where('attribute_value_id', $productAttribute->attribute_value_id);
        })->each(function ($variation) {
            $variation->values()->delete(); // Xóa mềm ProductVariationValue trước
            $variation->delete(); // Xóa mềm ProductVariation
        });
    
        // Xóa mềm product attribute
        $productAttribute->delete();
    
        return response()->json(['message' => 'Bạn đã xóa thành công (soft delete)']);
    }
    


    function deleteAttribute($id)
    {
        ProductAttribute::where('attribute_id', $id)->delete();
        return response()->json(['message' => 'Bạn đã xóa thành công']);
    }
}
