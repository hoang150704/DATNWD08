<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\SendEmailVerificationUserJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\UploadTraits;

class UserController extends Controller
{
    use UploadTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = User::latest('id')->paginate(10);
        foreach ($data as $key => $value) {

            if ($value->avatar == null) {
                $data[$key]['urlImg'] = null;
            } else {
                $url = $this->convertImage($value->library->url, 100, 100, 'thumb');
                $data[$key]['urlImg'] = $url;
            }
        }
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
                    'avatar'      => $request->avatar,
                    'username'  => $request->username,
                    'email'     => $request->email,
                    'password'  => bcrypt($request->password),
                    'role'   => $request->role,
                ];
        
                $user = User::query()->create($data);
                SendEmailVerificationUserJob::dispatch($user);
            });
            
            return response()->json([
                'message' => 'Thêm mới thành công!',
            ], 201);

        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
                'errors' => $e->getMessage(),
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
            // Kiểm tra xem người dùng có đổi email không
            if($user->email != $data['email']){
                $data['email_verified_at'] = null;
                $user->update($data);
                SendEmailVerificationUserJob::dispatch($user);
                return response()->json([
                    'message' => 'Cập nhật thành công! Vui lòng xác thực email',
                    'user' => $user,
                ], 200);
            };
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

    //
    public function changeActive(Request $request,User $user){
        try {
            //code...
            $data = [
                "is_active"=>!$user->is_active,
                "reason" => $user->is_active ? $request->reason ?? null : null,
            ];
            $user->update($data);
            return response()->json(['message'=>'Bạn đã thay đổi trạng thái thành công'],200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message'=>'Bạn đã thay đổi trạng thái thất bại','errors'=>$th->getMessage()],500);
        }

    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
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
