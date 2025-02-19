<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $idProduct)
    {
        try {
            // Lấy danh sách thuộc tính theo product_id
            $productAttributes = ProductAttribute::with("attribute:id,name","attribute_value:id,attribute_id,name")
                ->select('id','attribute_id','attribute_value_id','product_id')
                ->where("product_id", $idProduct)
                ->get()
                ->groupBy('attribute_id'); // Nhóm theo attribute_id
                $list = ProductAttribute::where("product_id", $idProduct)->distinct()->pluck('attribute_id')->toArray();

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
    
            return response()->json($list, 200);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $productAttribute = ProductAttribute::findOrFail($id);

        $productAttribute->delete();
    }
}
