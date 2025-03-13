<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\VoucherEvent;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        try {
            $vouchers = Voucher::paginate(10);
            return response()->json($vouchers, 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Lỗi", 500]);
        }
    }

    public function store(Request $request)
    {
        try {
            $voucher = $request->validate([
                'code' => 'required|unique:vouchers',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_percent' => 'nullable|integer|required_without:amount|max:100',
                'amount' => 'nullable|integer|required_without:discount_percent',
                'type' => 'required|integer',
                'for_logged_in_users' => 'required|boolean',
                'max_discount_amount' => 'nullable',
                'min_product_price' => 'nullable',
                'usage_limit' => 'nullable|integer',
                'expiry_date' => 'required|date',
                'start_date' => 'required|date',
            ]);
            $voucher = Voucher::create($voucher);
            // Phát sự kiện

            broadcast(new VoucherEvent('created', $voucher))->toOthers();
            return response()->json($voucher, 201);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Nhập đầy đủ và đúng thông tin", "errors" => $e->errors()], 422);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra khi thêm voucher', 'error' => $th->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $voucher = Voucher::findOrFail($id);
            return response()->json($voucher, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_percent' => 'nullable|integer|required_without:amount|max:100',
                'amount' => 'nullable|integer|required_without:discount_percent',
                'type' => 'required|integer',
                'for_logged_in_users' => 'required|boolean',
                'max_discount_amount' => 'nullable',
                'min_product_price' => 'nullable',
                'usage_limit' => 'required|integer',
                'expiry_date' => 'required|date',
                'start_date' => 'required|date',
            ]);

            // Kiểm tra và xử lý dữ liệu dựa trên type
            if ($data['type'] == 1) {
                unset($data['amount']);
                unset($data['min_product_price']);
            } else {
                unset($data['discount_percent']);
                unset($data['max_discount_amount']);
            }

            // Tìm và cập nhật voucher
            $voucher = Voucher::findOrFail($id);
            $voucher->update($data);

            // Phát sự kiện
            broadcast(new VoucherEvent('updated', $voucher))->toOthers();

            return response()->json($voucher, 200);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Nhập đầy đủ và đúng thông tin", "errors" => $e->errors()], 422);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['error' => 'Lỗi cập nhật: ' . $th->getMessage()], 500);
        }
    }


    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:vouchers,id'
            ]);
            $ids = $data['ids'];
            Voucher::whereIn('id', $ids)->delete();
            // Phát sự kiện
            broadcast(new VoucherEvent('deleted', $ids))->toOthers();
            return response()->json(['message' => 'Các voucher đã được xóa'], 200);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Nhập đầy đủ và đúng thông tin", "errors" => $e->errors()], 422);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Có lỗi xảy ra'], 500);
        }
    }
}
