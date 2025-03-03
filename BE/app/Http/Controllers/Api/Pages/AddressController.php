<?php

namespace App\Http\Controllers\Api\Pages;

use App\Http\Controllers\Controller;
use App\Models\AddressBook;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
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
                'addresses' => $addresses->isEmpty() ? null : $addresses
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy thông tin địa chỉ!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:15',
                'address' => 'required|string|max:255',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
            }

            $address = AddressBook::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => $request->is_active ?? 0
            ]);

            return response()->json([
                'message' => 'Thêm địa chỉ thành công!',
                'address' => $address
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi thêm địa chỉ!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
            }

            // Tìm địa chỉ cần cập nhật
            $address = AddressBook::where('user_id', $user->id)->where('id', $id)->first();

            if (!$address) {
                return response()->json(['message' => 'Địa chỉ không tồn tại!'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:15',
                'address' => 'required|string|max:255',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Cập nhật dữ liệu
            $address->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => $request->is_active ?? 0
            ]);

            return response()->json([
                'message' => 'Cập nhật địa chỉ thành công!',
                'address' => $address
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật địa chỉ!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
