<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            //code...
            $products = Product::select('id','name', 'description', 'short_description', 'main_image', 'slug')->paginate(10);
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
    public function store(Request $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validate(
                [
                    "name" => "required|max:100",
                    "attribute_id"=>["required",Rule::exists('attributes', 'id')]
                ]
            );
            DB::commit();
            return response()->json([], 200);
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
