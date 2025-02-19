<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::select('id', 'code', 'o_name', 'o_phone', 'final_amount', 'payment_method', 'stt_payment', 'stt_track', 'created_at')
                ->with('stt_track', 'stt_payment')
                ->orderByDesc('created_at')
                ->paginate(10);

            return response()->json([
                'message' => 'Success',
                'data' => $orders
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    public function show(Order $order)
    {
        try {
            $order = Order::with('items.product')->findOrFail($order->id);

            return response()->json([
                'message' => 'Success',
                'data' => $order
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    public function search()
    {
        try {
            $userName = request('username');
            $orderDate = request('orderDate');
            $status = request('status');

            $query = Order::query();

            if (!$userName && !$orderDate && !$status) {
                return response()->json([
                    'message' => 'Không tìm thấy kết quả'
                ], 404);
            } else {
                if ($userName) {
                    $query->where('o_name', 'like', "%{$userName}%");
                }

                if ($orderDate) {
                    $query->whereDate('created_at', '=', $orderDate);
                }

                if ($status) {
                    $query->where('stt_track', $status);
                }
            }

            $orders = $query
                ->with('stt_track', 'stt_payment')
                ->paginate(10);

            return response()->json([
                'message' => 'Success',
                'data' => $orders
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 404);
        }
    }

    public function changeStatus()
    {
        try {
            $id = request('id');
            $statusId = request('status');

            $order = Order::where('id', $id)->first();

            if ($order->stt_track == 1 || $order->stt_track == 2) {
                Order::where('id', $id)->update(['stt_track' => $statusId]);
            } else {
                if ($statusId == 9) {
                    return response()->json([
                        'message' => 'Không thể huỷ đơn hàng dựa trên trạng thái hiện tại',
                    ], 400);
                }
                Order::where('id', $id)->update(['stt_track' => $statusId]);
            }

            return response()->json([
                'message' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    public function destroy()
    {
        try {
            $id = request('id');

            $orders = Order::whereIn('id', $id)->get();

            foreach ($orders as $order) {
                if ($order->stt_track != 9) {
                    return response()->json([
                        'message' => 'Chỉ có thể xoá những đơn hàng bị huỷ',
                    ], 400);
                }
            }

            Order::whereIn('id', $id)->delete();

            return response()->json([
                'message' => 'Success',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    // Xoá trắng toàn bộ tất cả order
    // public function bulk()
    // {
    //     Order::truncate();
    // }
}
