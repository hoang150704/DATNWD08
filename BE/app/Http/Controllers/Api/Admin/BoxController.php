<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Box;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BoxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            //code...
            $boxes = Box::select('id,name,height,width,weight,length')->paginate(10);
            return response()->json(
                [
                    'message'=>'Success',
                    'code'=>200,
                    'data'=>$boxes
                ],200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(
                [
                    'message'=>'Failed',
                    'code'=>500,
                    'errors'=>$th->getMessage()
                ],500
            );
        }
        


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validate(
                [
                    'name' => 'required|string',
                    'height' => 'required|numeric',
                    'width' => 'required|numeric',
                    'length' => 'required|numeric',
                    'weight' => 'required|numeric'
                ]


            );
            Box::create($data);
            DB::commit();
            return response()->json(
                [
                    'message' => 'Bạn đã thêm thành công',
                    'code' => 201,
                    'data' => $data
                ],
                200
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
            return response()->json(
                [
                    'message' => 'Bạn đã thêm thất bại',
                    'code' => 500,
                    'errors' => $th->getMessage()
                ],
                500
            );
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
            $box = Box::findOrFail($id);
            return response()->json(
                [
                    'message' => 'Success',
                    'code' => 200,
                    'data' => $box
                ],
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(
                [
                    'message' => 'Failed',
                    'code' => 500,
                    'errors' => $th->getMessage()
                ],
                500
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validate(
                [
                    'name' => 'required|string',
                    'height' => 'required|numeric',
                    'width' => 'required|numeric',
                    'length' => 'required|numeric',
                    'weight' => 'required|numeric'
                ]
            );
            $box = Box::findOrFail($id);
            $box->update($data);
            DB::commit();
            return response()->json(
                [
                    'message' => 'Bạn đã sửa thành công',
                    'code' => 200,
                    'data' => $box
                ],
                200
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
            return response()->json(
                [
                    'message' => 'Bạn đã thêm thất bại',
                    'code' => 500,
                    'errors' => $th->getMessage()
                ],
                500
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        //
        try {
            DB::beginTransaction();
            $box = Box::findOrFail($id);

            $data = $request->input('box_id');
            //
            Product::where('box_id', $id)->update(['box_id' => $data]);
            $box->delete();
            DB::commit();
            return response()->json(
                [
                    'message' => 'Bạn đã xóa thành công',
                    'code' => 200,
                ],
                200
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
            return response()->json(
                [
                    'message' => 'Bạn đã xóa thất bại',
                    'code' => 500,
                    'errors' => $th->getMessage()
                ],
                500
            );
        }
    }
}
