<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Library;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            //code...
            $libraries = Library::select('id','url')->latest()->paginate(12);
            foreach($libraries as $key=>$value){
                $url = Library::getConvertImage($value['url'],250,250,'thumb');
                $list[] = [
                    'id'=>$value['id'],
                    'url'=>$url
                ];
            }
            return response()->json($list,200);
        } catch (\Throwable $th) {
            //throw $th;
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
            $request->validate([
                'images' => 'required|array',
                'images.*' => 'file|mimes:jpeg,png,jpg,webp|max:5120', // Kiểm tra tất cả ảnh
            ]);
    
            $images = $request->file('images');
            $validImages = [];
            $invalidImages = [];
    
            foreach ($images as $image) {
                try {
                    // Upload lên Cloudinary
                    $result = cloudinary()->upload($image->getRealPath());
                  
                    $url = $result->getSecurePath();
    
                    // Lưu vào Database
                    Library::create([
                        'public_id' => 1,
                        'url' => $url
                    ]);
    
                    $validImages[] = [
                        'file' => $image->getClientOriginalName(),
                        'message' => "Upload thành công",
                        'url' => $url
                    ];
                } catch (\Exception $e) {
                    // Ghi log lỗi
                    Log::error("Upload thất bại: {$image->getClientOriginalName()} - Lỗi: " . $e->getMessage());
    
                    $invalidImages[] = [
                        'file' => $image->getClientOriginalName(),
                        'error' => "Upload thất bại: " . $e->getMessage()
                    ];
                }
            }
    
            return response()->json([
                'success' => $validImages,
                'errors' => $invalidImages
            ]);
    
        } catch (\Throwable $th) {
            Log::error("Lỗi hệ thống: " . $th->getMessage());
    
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
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
        try {
            //code...
            $image = Library::findOrFail($id);
            Cloudinary::destroy($image['public_id']);
            $image->delete();
            return response()->json(['message'=>'Xóa ảnh thành công'],200);
        } 
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Ảnh không tồn tại'], 404);
        }
        catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }

    }
}
