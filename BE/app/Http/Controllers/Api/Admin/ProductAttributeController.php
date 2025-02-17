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
        //
        try {
            //code...
            $productAttribute = ProductAttribute::with("attribute","attribute_value")->select('id','attribute_id','attribute_value_id','product_id')->where("product_id",'=',$idProduct)->get();
            return response()->json($productAttribute,200);
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
