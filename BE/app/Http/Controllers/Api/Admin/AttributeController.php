<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
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
        $attributes = Attribute::select('name', 'is_default')->get();
        return response()->json($attributes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //code...
            $data = $request->validate(
                [
                    "name" => "required",
                ]
            );
            $slug = Str::slug($data['name']);
            // $attribute = Attribute::create($data);
            return response()->json($slug, 200);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Vui lòng nhập đầy đủ và đúng thông tin"], 422);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(["message" => "Lỗi"], 500);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $attribute = Attribute::withTrashed()->findOrFail($id);

            if ($attribute->trashed()) {
                return response()->json(['message' => 'Danh mục đã được xóa mềm'], 400);
            }
            if($attribute->is_default == 0){
                return response()->json(['message' => 'Danh mục mặc định không thể xóa'], 400);
            }
            //Xóa
            $attribute->delete();
            //Nếu thành công 
            return response()->json(['message' => 'Danh mục đã được chuyển vào thùng rác'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
