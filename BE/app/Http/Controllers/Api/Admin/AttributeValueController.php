<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {
        //
        try {
            //code...
            $attribute = Attribute::with('values')->findOrFail($id);
            return response()->json($attribute, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy thuộc tính'], 404);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }
    public function list()
    {
        try {
            $attributes = Attribute::with('values:id,name,attribute_id')->select('id', 'name')->get();

            $convertedData = $attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'data' => $attribute->values->map(function ($value) {
                        return [
                            'label' => $value->name,
                            'id' => $value->id,
                        ];
                    })
                ];
            });

            return response()->json($convertedData, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    //
    public function listNonAttribute($id)
    {
        try {
            $list = ProductAttribute::where("product_id", $id)->distinct()->pluck('attribute_id')->toArray();


            $attributes = Attribute::with('values:id,name,attribute_id')
                ->select('id', 'name')
                ->whereNotIn('id', $list) // Loại trừ các id trong $list
                ->get();

            $convertedData = $attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'data' => $attribute->values->map(function ($value) {
                        return [
                            'label' => $value->name,
                            'id' => $value->id,
                        ];
                    })
                ];
            });

            return response()->json($convertedData, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validate(
                [
                    "name" => "required|max:100",
                    "attribute_id" => ["required", Rule::exists('attributes', 'id')]
                ]
            );
            $attribute_value = AttributeValue::create($data);
            DB::commit();
            return response()->json($attribute_value, 200);
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
            $attribute = AttributeValue::select('id', 'name')->findOrFail($id);
            return response()->json($attribute, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy giá trị thuộc tính'], 404);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validate([
                "name" => "required|max:100",
            ]);

            $attribute_value = AttributeValue::findOrFail($id);
            $attribute_value->update($data);

            DB::commit();
            return response()->json($attribute_value, 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(
                [
                    "message" => "Lỗi nhập dữ liệu",
                    'error' => $e->getMessage()
                ],
                422
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json(["message" => "Lỗi", 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $attribute_value = AttributeValue::findOrFail($id);
            // Kiểm tra xem có product_variation_values nào sử dụng attribute_value_id này không
            $isUsed = ProductVariationValue::where('attribute_value_id', $id)->exists();
            if ($isUsed) {
                DB::rollBack();
                return response()->json(['message' => 'Không thể xóa giá trị thuộc tính vì đang được sử dụng ở biến thể sản phẩm!'], 400);
            }
            //Xóa
            ProductAttribute::where('attribute_value_id', $id)->delete();
            //TÌm các product variation có variation values có attribute_value_id đang xóa
            $variationsToDelete = ProductVariation::whereIn('id', function ($query) use ($id) {
                $query->select('variation_id')
                    ->from('product_variation_values')
                    ->where('attribute_value_id', $id);
            })->pluck('id')->toArray();
            if (!empty($variationsToDelete)) {
                // Xóa tất cả product_variation_values của variations bị ảnh hưởng
                ProductVariationValue::whereIn('variation_id', $variationsToDelete)->delete();

                // Xóa tất cả product_variations bị ảnh hưởng
                ProductVariation::whereIn('id', $variationsToDelete)->delete();
            }
            $attribute_value->delete();
            //Nếu thành công
            DB::commit();
            return response()->json(['message' => 'Giá trị thuộc tính đã được chuyển vào thùng rác'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Giá trị thuộc tính không tồn tại'], 404);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
