<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressBookRequest;
use App\Http\Requests\UpdateAddressBookRequest;
use App\Models\AddressBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressBookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = AddressBook::latest('id')->paginate(10);
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAddressBookRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
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

                // Nếu is_active là 1, cập nhật tất cả các bản ghi khác của cùng user_id về 0
                if ($data['is_active'] == 1) {
                    AddressBook::where('user_id', $request->user_id)
                        ->where('id', '!=', $address->id) // Loại trừ bản ghi vừa tạo
                        ->update(['is_active' => 0]);
                }
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
    public function show(AddressBook $addressBook)
    {
        return response()->json($addressBook);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressBookRequest $request, AddressBook $addressBook)
    {
        try {
            $data = $request->validated();
    
            // Kiểm tra nếu is_active là 1, cập nhật tất cả các bản ghi khác của cùng user_id về 0
            if ($request->has('is_active') && $request->is_active == 1) {
                AddressBook::where('user_id', $addressBook->user_id)
                    ->where('id', '!=', $addressBook->id) // Đảm bảo không cập nhật chính bản ghi này
                    ->update(['is_active' => 0]);
            }
    
            $addressBook->update($data);
    
            return response()->json([
                'message' => 'Cập nhật thành công!',
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
    public function destroy(AddressBook $addressBook)
    {
        try {
            $addressBook->delete();
            
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
