<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategoryRelation;
use App\Models\ProductImage;
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
            $products = Product::with("categories:name","library:id,public_id")->select('id', 'name', 'main_image', 'slug')->latest()->paginate(10);
            foreach ($products as $key=>$value) {
 
                if($value->main_image == null){
                    $products[$key]['url'] = null;
                }else{
                    $publicId = $value->library->public_id;
                    $url = Product::getConvertImage($publicId,200,200,'thumb');
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
            foreach($validatedData['images'] as $image){
                $dataProductImage = [
                    'product_id'=>$product->id,
                    'library_id'=>$image
                ];

                $productImage = ProductImage::create($dataProductImage);
            }
            // Thêm xong list ảnh
            // Xử lí danh mục
            foreach($validatedData['categories'] as $image){
                $dataProductImage = [
                    'product_id'=>$product->id,
                    'category_id'=>$image
                ];

                $productImage = ProductCategoryRelation::create($dataProductImage);
            }
            // Xử lí variants 
            if($request->type == 1) // Sản phẩm đơn giản
            {
                // // Data mẫu
                // $novariants = [
                //      [
                //     "variant_image"=>1, // or không có thì gửi là null,
                //     "regular_price"=>300000, // or null
                //     "sale_price"=>190000, // or null lưu ý giá sale không bằng hoặc lớn hơn giá gốc
                //     "stock_quantity"=>1000 ,// số lượng
                //     "values"=>[] // gửi lên 1 mảng rỗng
                //      ]
                // ];

                $dataVariants = [
                        'regular_price' => $validatedData['regular_price'],
                        'sale_price' => $validatedData['sale_price'],
                        'short_description' => $validatedData['short_description'],
                        'variant_image' => $validatedData['variant_image'],
                ];
            }

            else{
                //Data mẫu
                $variants = [
                    [
                        "image"=>1, // or không có thì gửi là null,
                        "regular_price"=>300000, // or null
                        "sale_price"=>190000, // or null lưu ý giá sale không bằng hoặc lớn hơn giá gốc
                        "stock_quantity"=>1000 ,// số lượng
                        "values"=>[
                            1,5 // gủi lên attributes_value_id của 2 hoặc 3 hoặc 4,....
                        ]
                    ],
                    [
                        "image"=>1, // or không có thì gửi là null,
                        "regular_price"=>300000, // or null
                        "sale_price"=>190000, // or null lưu ý giá sale không bằng hoặc lớn hơn giá gốc
                        "stock_quantity"=>1000 ,// số lượng
                        "values"=>[
                            1,5 // gủi lên attributes_value_id của 2 hoặc 3 hoặc 4,....
                        ]
                    ],


                ];
            }
            // Hoàn thành
            DB::commit();
            return response()->json($dataProduct, 200);
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
