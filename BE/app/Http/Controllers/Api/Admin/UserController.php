<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = User::latest('id')->paginate(10);
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = [
                    'name'      => $request->name,
                    'username'  => $request->username,
                    'email'     => $request->email,
                    'password'  => bcrypt($request->password),
                    'phone'     => $request->phone,
                    'role_id'   => $request->role_id,
                    'is_active' => $request->has('is_active') ?? 0
                ];
    
                if ($request->hasFile('avatar')) {
                    $data['avatar'] = Storage::put('users', $request->file('avatar')); 
                }
        
                User::query()->create($data);
            });
        
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

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $data = $request->validated();
            
            // Nếu không có giá trị 'is_active', gán giá trị mặc định là 0
            $data['is_active'] = $request->has('is_active') ?? 0;
            
            // Kiểm tra nếu có mật khẩu mới và mã hóa mật khẩu
            if ($request->has('password')) {
                $data['password'] = bcrypt($request->password);
            }

            // Kiểm tra xem có ảnh mới không
            if ($request->hasFile('avatar')) {
                // Xóa ảnh cũ nếu có
                if ($user->avatar) {
                    Storage::delete($user->avatar);
                }
                // Lưu ảnh mới
                $data['avatar'] = Storage::put('users', $request->file('avatar'));
            }
    
            $user->update($data);
    
            return response()->json([
                'message' => 'Cập nhật thành công!',
                'user' => $user,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error($e->getMessage());
    
            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
            ], 500);
        }
    }    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->avatar && Storage::delete($user->avatar);

            $user->delete();

            return response()->json([
                'message' => 'Xóa thành công!',
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
            ], 500);
        }
    }
}
