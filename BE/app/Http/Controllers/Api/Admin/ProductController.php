<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategoryRelation;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            //code...
            $products = Product::with("categories:name", "library:id,public_id")->select('id', 'name', 'main_image', 'slug')->latest()->paginate(10);
            foreach ($products as $key => $value) {

                if ($value->main_image == null) {
                    $products[$key]['url'] = null;
                } else {
                    $publicId = $value->library->public_id;
                    $url = Product::getConvertImage($publicId, 200, 200, 'thumb');
                    $products[$key]['url'] = $url;
                }
            }
            return response()->json($products, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage() // Trả về chi tiết lỗi
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $validatedData = $request->validated();
            // Xử lí thêm product
            $dataProduct = [
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'short_description' => $validatedData['short_description'],
                'main_image' => $validatedData['main_image'],
                'type'=> $validatedData['type'],
            ];
            $slug = Str::slug($dataProduct['name']);
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$slug}-$count";
                $count++;
            }
            $dataProduct['slug'] = $slug;
            $product = Product::create($dataProduct);
            // Thêm list ảnh
            foreach ($validatedData['images'] as $image) {
                $dataProductImage = [
                    'product_id' => $product->id,
                    'library_id' => $image
                ];

                $productImage = ProductImage::create($dataProductImage);
            }
            // Thêm xong list ảnh
            // Xử lí danh mục
            foreach ($validatedData['categories'] as $category) {
                $dataProductImage = [
                    'product_id' => $product->id,
                    'category_id' => $category
                ];
                $productImage = ProductCategoryRelation::create($dataProductImage);
            }
            // Xử lí variants 
            if ($request->type == 1) // Sản phẩm đơn giản
            {
                foreach ($validatedData['variants'] as $variant) {
                    $dataNoVariants = [
                        'product_id' => $product->id,
                        'regular_price' => $variant['regular_price'],
                        'sale_price' => $variant['sale_price'],
                        'sku' => $variant['sku'],
                    ];
                }
                $variantNew = ProductVariation::create($dataNoVariants);
            } else {
                foreach ($validatedData['variants'] as $variant) {
                    foreach ($validatedData['variants'] as $variant) {
                        $dataVariants = [
                            'product_id' => $product->id,
                            'regular_price' => $variant['regular_price'],
                            'sale_price' => $variant['sale_price'],
                            'variant_image' => $variant['variant_image'],
                            'sku' => $variant['sku'],
                        ];
                        $variantNew = ProductVariation::create($dataVariants);
                    foreach($variant['values'] as $key=>$value){
                        $dataValue = [
                            'variation_id'=> $variantNew->id,
                            'attribute_value_id'=>$value
                        ];
                        ProductVariationValue::create($dataValue);
                    }
                    }

                }
            }
            // Hoàn thành
            DB::commit();
            return response()->json(['message'=>'Bạn đã thêm dữ liệu thành công'], 200);
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
    public function show(string $id)
    {
        //
        try {
            //code...
            $products = Product::with("categories:name",'variants','variants.values', "library:id,public_id")->findOrFail($id);
            return response()->json($products, 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage() // Trả về chi tiết lỗi
            ], 500);
        }
        
        
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


