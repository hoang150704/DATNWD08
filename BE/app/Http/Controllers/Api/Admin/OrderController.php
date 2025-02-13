<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::orderByDesc('created_at')->paginate(10);

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
            $userName = request()->username;
            $orderDate = request()->orderDate;
            $status = request()->status;

            $query = Order::query();

            if ($userName) {
                $query->where('o_name', 'like', "%{$userName}%");
            }

            if ($orderDate) {
                $query->whereDate('created_at', '=', $orderDate);
            }

            if ($status) {
                $query->where('stt_track', $status);
            }

            $orders = $query->paginate(10);

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
            $id = request()->id;
            $statusId = request()->statusId;

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
}
