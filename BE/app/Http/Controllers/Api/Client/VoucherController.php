<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    public function index()
    {
        try {
            $vouchers = Voucher::where('expiry_date', '>', now()) // Lấy voucher chưa hết hạn
                ->whereColumn('usage_limit', '>', 'times_used') // Lấy voucher chưa hết lượt sử dụng
                ->orderBy('start_date', 'asc') // Sắp xếp theo ngày bắt đầu
                ->get();


            return response()->json($vouchers, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra khi lấy danh sách voucher'], 500);
        }
    }


    public function show($id)
    {
        try {
            $voucher = Voucher::findOrFail($id);

            // Kiểm tra thời hạn voucher
            if ($voucher->expiry_date < now()) {
                return response()->json(['message' => 'Voucher này đã hết hạn'], 400);
            }

            return response()->json($voucher, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra khi lấy chi tiết voucher'], 500);
        }
    }


    public function search(Request $request)
    {
        $search = $request->input('query');
        try {
            $vouchers = Voucher::where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->where('expiry_date', '>', now())
                ->get();

            return response()->json($vouchers, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra khi tìm kiếm voucher'], 500);
        }
    }
    public function applyVoucher(Request $request)
{
    try {
        // Xác nhận dữ liệu đầu vào
        $validatedData = $request->validate([
            'voucher_code' => 'required|string|exists:vouchers,code', // Mã voucher
            'total_amount' => 'required|numeric|min:0', // Giá trị đơn hàng dự kiến
        ]);

        // Kiểm tra token xác thực
        $user = auth('sanctum')->user(); // Lấy người dùng thông qua Sanctum token
        $isLoggedIn = $user !== null; // Xác định người dùng đã đăng nhập hay chưa

        // Lấy thông tin voucher
        $voucher = Voucher::where('code', $validatedData['voucher_code'])->first();

        // Kiểm tra loại voucher
        if ($voucher->for_logged_in_users == 1 && !$isLoggedIn) {
            return response()->json(['message' => 'Voucher này chỉ dành cho người dùng đã đăng nhập'], 403);
        }

        // Kiểm tra hạn sử dụng
        if (!$voucher->expiry_date || Carbon::parse($voucher->expiry_date)->isBefore(now())) {
            return response()->json(['message' => 'Voucher đã hết hạn'], 400);
        }

        // Kiểm tra số lượt sử dụng còn lại
        if ($voucher->usage_limit && $voucher->times_used >= $voucher->usage_limit) {
            return response()->json(['message' => 'Voucher đã hết lượt sử dụng'], 400);
        }

        // Kiểm tra giá trị tối thiểu
        if ($voucher->min_product_price && $validatedData['total_amount'] < $voucher->min_product_price) {
            return response()->json(['message' => 'Giá trị đơn hàng không đủ điều kiện áp dụng voucher'], 400);
        }

        // Tính giảm giá
        $discount = $voucher->type == 1
            ? min(($validatedData['total_amount'] * $voucher->discount_percent) / 100, $voucher->max_discount_amount ?? PHP_INT_MAX)
            : $voucher->amount;

        // Tính tổng giá trị sau giảm
        $finalAmount = max(0, $validatedData['total_amount'] - $discount);

        // Trả về kết quả
        return response()->json([
            'message' => 'Voucher áp dụng thành công',
            'discount' => $discount,
            'final_total' => $finalAmount,
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi áp dụng voucher',
            'error' => $th->getMessage(),
        ], 500);
    }
}


}
