<?php

namespace App\Http\Controllers\Api\Pages;

use App\Http\Controllers\Controller;
use App\Models\AddressBook;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
            }

            // Lấy danh sách địa chỉ
            $addresses = AddressBook::where('user_id', $user->id)->get();

            return response()->json([
                'message' => 'Lấy thông tin thành công',
                'user' => $user,
                'addresses' => $addresses->isEmpty() ? null : $addresses
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy thông tin tài khoản!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
            }

            // Xác thực dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Xử lý avatar (nếu có)
            $avatarPath = $user->avatar ?? null;
            if ($request->hasFile('avatar')) {
                // Xóa ảnh cũ nếu có
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Lưu ảnh mới vào thư mục "storage/app/public/avatars"
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }

            // Cập nhật thông tin người dùng
            $user->update([
                'name' => $request->name,
                'avatar' => $avatarPath,
            ]);

            return response()->json([
                'message' => 'Cập nhật thông tin thành công',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null, // Trả về URL đầy đủ
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật thông tin!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
