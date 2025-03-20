<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadImageJob;
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
                 'images.*' => 'file|mimes:jpeg,png,jpg,webp|max:5120',
             ]);
     
             $images = $request->file('images');
             $uploadedImages = [];
     
             foreach ($images as $image) {
                 // Lưu ảnh vào storage
                 $storedPath = $image->store('temp_uploads');
                 $fullPath = storage_path("app/$storedPath");
             
                 if (!file_exists($fullPath)) {
                     Log::error("File không tồn tại trước khi xử lý: $fullPath");
                     continue;
                 }
             
                 if (count($images) === 1) {
                    // Nếu chỉ có 1 ảnh, chạy Job ngay lập tức
                    (new UploadImageJob($fullPath, $image->getClientOriginalName()))->handle();
                } else {
                    // Nếu có nhiều ảnh, đẩy vào queue
                    UploadImageJob::dispatch($fullPath, $image->getClientOriginalName())->onQueue('high');
                }
     
                 $uploadedImages[] = [
                     'file' => $image->getClientOriginalName(),
                     'message' => "Upload thành công"
                 ];
             }
     
             return response()->json([
                 'message' => 'Upload thành công',
                 'images' => $uploadedImages
             ], 200);
     
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
