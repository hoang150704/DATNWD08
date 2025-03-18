<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressBookRequest;
use App\Models\AddressBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    //
    public function info()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $user,
            'code' => 200
        ], 200);
    }
    //
    public function changeProfile(Request $request){
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
        }
        $data = $request->validate(
            [
                'name'=>'required|string|max:255',
                'avatar'=>'nullable|url'
            ]
        );
        $infoUser = User::findOrFail($user->id);
        $updateInfo = $infoUser->update($data);
        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'data'=>$updateInfo,
            'code'=>200
        ],200);
    }
    //Địa chỉ
    public function store(StoreAddressBookRequest $request)
    {
        try {
            DB::beginTransaction();
                // Kiểm tra nếu là lần đầu tiên tạo địa chỉ của user_id này
                $isFirstAddress = AddressBook::where('user_id', $request->user_id)->count() == 0;
    
                $data = [
                    'user_id'   => $request->user_id,
                    'name'      => $request->name,
                    'phone'     => $request->phone,
                    'address'   => $request->address,
                    'is_active' => $isFirstAddress ? 1 : ($request->has('is_active') && $request->is_active == 1 ? 1 : 0)
                ];
    
                $address = AddressBook::query()->create($data);
                if ($data['is_active'] == 1) {
                    AddressBook::where('user_id', $request->user_id)
                        ->where('id', '!=', $address->id) 
                        ->update(['is_active' => 0]);
                }
          
                DB::commit();
            return response()->json([
                'message' => 'Thêm mới thành công!',
            ], 201);
    
        } catch (\Exception $e) {
            Log::error($e->getMessage());
    
            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
            ], 500);
        }
    }  


}
