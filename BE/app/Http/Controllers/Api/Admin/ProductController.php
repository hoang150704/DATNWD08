<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\Product\ProductExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Comment;
use App\Models\Product;
use App\Traits\ProductTraits;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    use ProductTraits;
    /**
     * Display a listing of the resource.
     */
    protected function search()
    {
        try {
            $params = array_filter(request()->only(['keyword', 'category']));

            $query = Product::query();

            if (isset($params['keyword'])) {
                $query->where('name', 'like', "%{$params['keyword']}%");
            }

            if (isset($params['category'])) {
                if ($params['category'] === 'uncategorized') {
                    $query->where(function ($query) {
                        $query->whereDoesntHave('categories')
                            ->orWhereHas('categories', function ($q) {
                                $q->whereNull('category_id');
                            });
                    });
                } else {
                    $query->whereHas('categories', function ($query) use ($params) {
                        $query->where('categories.id', $params['category']);
                    });
                }
            }

            return $query
                ->with(['categories:id,name'])
                ->select('id', 'name', 'main_image', 'type', 'slug')
                ->latest()
                ->paginate(10);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ], 404);
        }
    }

    public function index()
    {
        try {
            $products = $this->search();

            foreach ($products as $key => $value) {

                if ($value->main_image == null) {
                    $products[$key]['url'] = null;
                } else {
                    $url = Product::getConvertImage($value->library->url, 100, 100, 'thumb');
                    $products[$key]['url'] = $url;
                }
            }
            return response()->json($products, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage()
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
                'description' => $validatedData['description'] ?? null,
                'short_description' => $validatedData['short_description'] ?? null,
                'main_image' => $validatedData['main_image'] ?? null,
                'type' => $validatedData['type'],
            ];
            //Xử lí slug
            $slug = $this->handleSlug($dataProduct['name'], 'create');
            $dataProduct['slug'] = $slug;

            //Thêm sản phẩm
            $product = Product::create($dataProduct);

            // Thêm list ảnh
            $images = $validatedData['images'] ?? [];
            $this->addImages($images, $product->id);

            // Xử lí danh mục
            $categories = $validatedData['categories'] ?? [];
            $this->addCategories($categories, $product->id);

            // Xử lí thêm sản phẩm biến thể hay đơn giản
            if ($request->type == 1) {
                $this->createBasicProduct($validatedData['variants'], $product->id);
            } else {
                $this->createVariantProduct($validatedData['variants'], $validatedData['attributes'], $product->id);
            }
            // Hoàn thành
            DB::commit();
            return response()->json(['message' => 'Bạn đã thêm sản phẩm thành công'], 200);
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
            $product = Product::select('id', 'name', 'description', 'short_description', 'main_image', 'slug', 'type')->findOrFail($id);
            //Covert dữ liệu
            $convertData = [
                "id" => $product->id,
                "name" => $product->name,
                "description" => $product->description,
                "short_description" => $product->short_description,
                "main_image" => $product->main_image,
                "url_main_image" => $product->main_image == null ? "" : Product::getConvertImage($product->library->url, 400, 400, 'thumb'),
                "type" => $product->type,
                "slug" => $product->slug,
            ];
            //List biến thể
            $convertData['variants'] = $product->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'regular_price' => $variant->regular_price,
                    'sale_price' => $variant->sale_price,
                    'weight' => $variant->weight,
                    'stock_quantity' => $variant->stock_quantity,
                    'values' => $variant->values->map(function ($value) {
                        return [
                            'name' => $value->attributeValue->name
                        ];
                    })
                ];
            });
            //Categories
            $convertData['categories'] = $product->categories->pluck('id')->toArray();
            //Thư viện ảnh
            $convertData['product_images'] = $product->productImages->map(function ($image) {
                return [
                    'public_id' => $image->id,
                    'url' => Product::getConvertImage($image->url, 400, 400, 'thumb')
                ];
            });

            return response()->json($convertData, 200);
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
    public function update(UpdateProductRequest $request, string $id)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $product = Product::findorFail($id);
            //Sửa sản phẩm
            $validatedData = $request->validated();

            //COnvert data
            $dataProduct = [
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'short_description' => $validatedData['short_description'] ?? null,
                'main_image' => $validatedData['main_image'] ?? null,
                'type' => $validatedData['type'],
                'slug' => $validatedData['slug']
            ];
            $dataProduct['slug'] = $this->handleSlug($dataProduct['slug'], 'update', $id);

            //Tiến hành sửa biến thể or basic
            if ($product->type == 1) {  //Nếu ban dầu là sp đơn giản
                if ($dataProduct['type'] == 1) {  // Sau khi update vẫn là sp đơn giản
                    //Update sản phẩm đơn giản
                    $this->updateBasicProduct($validatedData['variants'], $id);
                } else { // Sau khi update là sp biến thể
                    //Ẩn biến thể cũ đi
                    $this->deletProductVaration($product);

                    // Thêm biến thể
                    $this->createVariantProduct($validatedData['variants'], $validatedData['attributes'], $id);
                }
            } else { // Trước đó là sp biến thể
                if ($dataProduct['type'] == 1) { // sau update là sp đơn giản
                    //Ẩn biến thể cũ
                    $this->deletProductVaration($product);

                    //Thêm sản phẩm đơn giản
                    $this->createBasicProduct($validatedData['variants'], $id);
                } else { //sau update vẫn là biến thể
                    //Update sản phẩm biến thể
                    $this->updateVariantProduct($validatedData['variants'], $id);
                }
            }
            $product->update($dataProduct);
            $product->categories()->sync($validatedData['categories']);
            $product->productImages()->sync($validatedData['images']);
            DB::commit();
            return response()->json($validatedData['variants'], 200);
        } catch (\Exception $e) {
            //throw $th;
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        try {
            $ids = request('ids');

            $products = Product::whereIn('id', $ids)->with(['variants', 'variants.orderItems.order'])->get();

            foreach ($products as $product) {

                // Kiểm tra nếu sản phẩm có trong order_items của đơn hàng chưa thanh toán online
                foreach ($product->variants as $variant) {
                    foreach ($variant->orderItems as $orderItem) {
                        $order = $orderItem->order;
                        if ($order && $order->payment_method === 'vnpay' && $order->payment_status_id == 1) {
                            return response()->json([
                                'message' => "Không thể xoá sản phẩm '{$product->name}' vì đang chờ thanh toán online."
                            ], 403);
                        }
                    }
                }

                // Xoá giá trị thuộc tính của biến thể
                $product->variants->each(function ($variant) {
                    $variant->values()->delete();
                });

                // Xoá các biến thể
                $product->variants()->delete();

                // Xoá mềm sản phẩm
                $product->delete();
            }

            return response()->json(['message' => 'Sản phẩm đã được xóa'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
        } catch (\Throwable $th) {
            Log::error('Lỗi khi xóa sản phẩm: ' . $th->getMessage());
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => env('APP_DEBUG') ? $th->getMessage() : 'Vui lòng thử lại sau!'
            ], 500);
        }
    }

    public function listProductForOrder(Request $request)
    {
        try {
            // Lấy tham số tìm kiếm
            $search = $request->input('search');

            // Tạo query lấy sản phẩm
            $query = Product::with([
                'variants' => function ($query) {
                    $query->select('id', 'product_id', 'stock_quantity', 'weight', 'regular_price', 'sale_price');
                },
                'variants.values.attributeValue' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->select('id', 'name', 'main_image', 'type');

            // ✅ Tìm kiếm theo tên sản phẩm nếu có
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            // ✅ Phân trang sản phẩm
            $products = $query->paginate(10);

            // Format lại dữ liệu để chỉ lấy mảng tên thuộc tính và hình ảnh
            $products->getCollection()->transform(function ($product) {
                $product->image_url = $product->main_image
                    ? Product::getConvertImage(optional($product->library)->url, 200, 200, 'thumb')
                    : null;
                $product->variants->transform(function ($variant) {
                    $variant->values = $variant->values ? $variant->values->pluck('attributeValue.name')->toArray() : [];
                    return $variant;
                });
                return $product;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Lấy danh sách sản phẩm thành công!',
                'data' => $products
            ], 200);
        } catch (Exception $e) {
            // Ghi log lỗi
            Log::error('Lỗi khi lấy danh sách sản phẩm: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Đã xảy ra lỗi khi lấy danh sách sản phẩm!',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Vui lòng thử lại sau!'
            ], 500);
        }
    }

    public function trash()
    {
        try {
            $listSoftDeleteProducts = Product::onlyTrashed()->with(['variants'])->paginate(15);
            return response()->json($listSoftDeleteProducts, 200); // trả về response
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    public function hardDelete()
    {
        try {
            $ids = request('ids');

            Product::onlyTrashed()->whereIn('id', $ids)->forceDelete();

            return response()->json(['message' => 'Xóa sản phẩm thành công'], 200);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    public function export()
    {
        return Excel::download(new ProductExport, 'product.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
