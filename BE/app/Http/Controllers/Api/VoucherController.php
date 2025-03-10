<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
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
}
