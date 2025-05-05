<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            //code...
            $attributes = Attribute::select('id', 'name', 'is_default')->paginate(10);
            return response()->json($attributes, 200);
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
    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validate(
                [
                    "name" => "required|max:100|unique:attributes,name",
                ]
            );
            $attribute = Attribute::create($data);
            DB::commit();
            return response()->json($attribute, 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $th) {
            DB::rollBack(); // Hoàn tác nếu có lỗi khác
            Log::error($th); // Ghi log lỗi để dễ debug
            return response()->json([
                "message" => "Lỗi hệ thống",
                "error" => $th->getMessage() // Trả về chi tiết lỗi
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
            $attribute = Attribute::select('id', 'name', 'is_default')->findOrFail($id);
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->validate([
                "name" => "required|max:100",
                "is_default" => "required|in:0,1"
            ]);

            $attribute = Attribute::findOrFail($id);
            $attribute->update($data);

            DB::commit();
            return response()->json($attribute, 200);
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
            $attribute = Attribute::findOrFail($id);
            if ($attribute->is_default == 0) {
                return response()->json(['message' => 'Thuộc tính mặc định không thể xóa'], 400);
            }
            // Lấy các values của thuộc tính
            $attributeValueIds = AttributeValue::where('attribute_id', $id)->pluck('id')->toArray();
            // Kiểm tra xem có product_variation_values nào sử dụng các attribute_value_id này không
            $isUsed = ProductVariationValue::whereIn('attribute_value_id', $attributeValueIds)
                ->exists();
            if ($isUsed) {
                DB::rollBack();
                return response()->json(['message' => 'Không thể xóa thuộc tính vì đang được sử dụng ở biến thể sản phẩm!'], 400);
            }
            //Xóa
            ProductAttribute::where('attribute_id', $id)->delete();
            //Lấy ra mảng các productvariations có values liên quan
            $variationsToDelete = ProductVariation::join('product_variation_values', 'product_variations.id', '=', 'product_variation_values.variation_id')
                ->whereIn('product_variation_values.attribute_value_id', $attributeValueIds)
                ->distinct()
                ->pluck('product_variations.id')
                ->toArray();
            // Kiểm tra xem mảng có rỗng không, nếu không rỗng thì xóa
            if (!empty($variationsToDelete)) {
                ProductVariationValue::whereIn('variation_id', $variationsToDelete)->delete();
                ProductVariation::whereIn('id', $variationsToDelete)->delete();
            }
            //Xóa các attributes values liên quan
            AttributeValue::whereIn('id', $attributeValueIds)->delete();
            // Xóa attribute
            $attribute->delete();
            //Nếu thành công
            DB::commit();
            return response()->json(['message' => 'Thuộc tính đã được chuyển vào thùng rác'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Thuộc tính không tồn tại'], 404);
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
