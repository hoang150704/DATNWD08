<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $attributes = Attribute::select('name','is_default')->get();
        return response()->json($attributes,200);
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
                    "name"=>"required",
                ]
                );
            $attribute = Attribute::create($data);
            return response()->json($attribute,200);
        } catch(ValidationException $e){
            return response()->json(["message"=>"Vui lòng nhập đầy đủ và đúng thông tin"],422);
        }catch (\Throwable $th) {
            //throw $th;
            return response()->json(["message"=>"Lỗi"],500);
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
