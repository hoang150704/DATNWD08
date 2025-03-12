<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Models\Library;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    //
    public function uploadImage(Request $request)
    {
        try {
            // Xác thực dữ liệu
            $request->validate([
                'image' => 'required|file|mimes:jpeg,png,jpg,webp|max:5120', // Chỉ chấp nhận ảnh <= 5MB
            ]);

            $image = $request->file('image');

            if ($image->isValid()) {
                // Upload ảnh lên Cloudinary
                $result = cloudinary()->upload($image->getRealPath());
                $url = $result->getSecurePath();

                return response()->json([
                    'message' => 'Upload thành công!',
                    'url' => $url
                ], 200);
            } else {
                return response()->json(['error' => 'File không hợp lệ'], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
