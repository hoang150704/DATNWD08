<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id,)
    {
        //
        $listProductVariant = Product::with('variants:id,product_id,regular_price,sale_price','variants.values:variation_id,attribute_value_id','variants.values.attribute_values:id,name')->select('id','name')->findOrFail($id);
        $convertData = [
            'id'=>$listProductVariant->id,
            'name'=>$listProductVariant ->name
        ];
        foreach($listProductVariant->variants as $variant){
            $convertData['variants'][$variant->id]=[
                'id'=>$variant->id,
                'regular_price'=>$variant->regular_price,
                'sale_price'=>$variant->sale_price,
            ];
            foreach($variant->values as $value){
                $convertData['variants'][$variant->id]['values'][]=$value->attribute_values->name;
            }
        }
        $listProductVariant = $convertData;
        // 
        $product = Product::with('attributes.values')->find(1);
        return response()->json($product,200);
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
    }
}
