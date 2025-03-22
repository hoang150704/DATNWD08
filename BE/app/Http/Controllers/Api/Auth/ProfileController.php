<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressBookRequest;
use App\Models\AddressBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
    public function changeProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Người dùng chưa đăng nhập!'], 401);
        }
        $data = $request->validate(
            [
                'name' => 'required|string|max:255',
                'avatar' => 'nullable|url'
            ]
        );
        $infoUser = User::findOrFail($user->id);
        $updateInfo = $infoUser->update($data);
        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'data' => $updateInfo,
            'code' => 200
        ], 200);
    }
    //
    public function getDefault()
    {
        $userId = Auth::id();
        $defaultAddress = AddressBook::where('user_id', $userId)->where('is_active', 1)->first();

        if (!$defaultAddress) {
            // Nếu ko có địa chỉ mặc định, lấy địa chỉ đầu tiên
            $defaultAddress = AddressBook::where('user_id', $userId)->first();
        }

        return response()->json([
            'message' => 'Địa chỉ mặc định',
            'data' => $defaultAddress
        ], 200);
    }

    public function index()
    {
        $userId = Auth::id();
        $addresses = AddressBook::where('user_id', $userId)->orderByDesc('is_active')->get();

        return response()->json([
            'message' => 'Danh sách địa chỉ',
            'data' => $addresses
        ], 200);
    }

    //Địa chỉ
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate
            $validated = $request->validate([
                'user_id'  => 'required|exists:users,id',
                'name'     => 'required|string|max:255',
                'phone'    => ['required', 'regex:/^0[0-9]{9}$/'],
                'address'  => 'required|string|max:500',
                'province' => 'required|integer',
                'district' => 'required|integer',
                'ward'     => 'required|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            // Kiểm tra xem user này đã có địa chỉ nào trước đó ko
            $hasAddresses = AddressBook::where('user_id', $validated['user_id'])->exists();
            $currentDefault = AddressBook::where('user_id', $validated['user_id'])->where('is_active', 1)->first(); // Lấy địa chỉ mặc định

            // Nếu là địa chỉ đầu tiên thì mặc định nó là địa chỉ mặc định
            if (!$hasAddresses) {
                $validated['is_active'] = 1;
            } else { // Ngược lại (ko phải địa chỉ đầu tiên)
                // Nếu khi thêm mới địa chỉ chọn là mặc định
                if ($request->boolean('is_active')) {
                    // Xóa mặc định ở địa chỉ mặc định trước đó đi
                    if ($currentDefault) {
                        $currentDefault->update(['is_active' => 0]);
                    }
                    $validated['is_active'] = 1;
                } else {
                    // Nếu không chọn mặc định giữ nguyên trạng thái cũ
                    $validated['is_active'] = 0;
                }
            }
            // Tạo địa chỉ mới
            $address = AddressBook::create($validated);

            DB::commit();

            return response()->json([
                'message' => 'Thêm mới thành công',
                'data'    => $address
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ!',
                'errors'  => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Lỗi truy vấn SQL: " . $e->getMessage(), ['request' => $request->all()]);

            return response()->json([
                'message' => 'Lỗi cơ sở dữ liệu, vui lòng thử lại!',
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Lỗi hệ thống: " . $th->getMessage(), ['request' => $request->all()]);

            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
                'errors' => $th->getMessage()
            ], 500);
        }
    }
    //Sửa

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Tìm địa chỉ cần cập nhật
            $address = AddressBook::findOrFail($id);
            if ($address->is_active == 1 && $request->has('is_active') && !$request->boolean('is_active')) {
                return response()->json([
                    'message' => 'Phải có ít nhất một địa chỉ mặc định. Vui lòng chọn địa chỉ khác làm mặc định trước khi bỏ mặc định địa chỉ này.',
                ], 422);
            }
            // Validate
            $validated = $request->validate([
                'name'     => 'sometimes|string|max:255',
                'phone'    => ['sometimes', 'regex:/^0[0-9]{9}$/'],
                'address'  => 'sometimes|string|max:500',
                'province' => 'sometimes|integer',
                'district' => 'sometimes|integer',
                'ward'     => 'sometimes|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            // Kiểm tra nếu đặt địa chỉ này làm mặc định
            if ($request->boolean('is_active')) {
                // Xóa mặc định của địa chỉ hiện tại
                AddressBook::where('user_id', $address->user_id)->where('is_active', 1)->update(['is_active' => 0]);
                $validated['is_active'] = 1;
            }

            // Cập nhật thông tin địa chỉ
            $address->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật thành công',
                'data'    => $address
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ!',
                'errors'  => $e->errors()
            ], 422);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Lỗi truy vấn SQL: " . $e->getMessage(), ['request' => $request->all()]);

            return response()->json([
                'message' => 'Lỗi cơ sở dữ liệu, vui lòng thử lại!',
            ], 500);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Lỗi hệ thống: " . $th->getMessage(), ['request' => $request->all()]);

            return response()->json([
                'message' => 'Đã có lỗi xảy ra!',
                'errors' => $th->getMessage()
            ], 500);
        }
    }
    //
    public function destroy($id)
    {
        $address = AddressBook::where('user_id', Auth::id())->findOrFail($id);

        // Nếu là địa chỉ mđ thì ko cho xóa
        if ($address->is_active) {
            return response()->json([
                'message' => 'Không thể xóa địa chỉ mặc định, hãy chọn địa chỉ khác làm mặc định trước.'
            ], 400);
        }

        $address->delete();

        return response()->json([
            'message' => 'Xóa địa chỉ thành công'
        ], 200);
    }
    public function setDefault($id)
    {
        $userId = Auth::id();
        $address = AddressBook::where('user_id', $userId)->findOrFail($id);

        // Bỏ mặc định địa chỉ cũ
        AddressBook::where('user_id', $userId)->where('is_active', 1)->update(['is_active' => 0]);

        // Đặt địa chỉ mới làm mặc định
        $address->update(['is_active' => 1]);

        return response()->json([
            'message' => 'Cập nhật địa chỉ mặc định thành công',
            'data' => $address
        ], 200);
    }
    public function selectAddressForOrder($id)
    {
        $userId = Auth::id();
        $address = AddressBook::where('user_id', $userId)->findOrFail($id);

        return response()->json([
            'message' => 'Địa chỉ được chọn cho đơn hàng',
            'data' => $address
        ], 200);
    }
}
