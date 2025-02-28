<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\Admin\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Models\StatusTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::select('id', 'code', 'o_name', 'o_phone', 'final_amount', 'payment_method', 'stt_payment', 'stt_track', 'created_at')
                ->with([
                    'stt_track:id,name,next_status_allowed',
                    'stt_payment:id,name'
                ])
                ->orderByDesc('orders.created_at')
                ->paginate(40);

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

    public function store(StoreOrderRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Xử lý tạo đơn hàng
            $order = Order::create([
                'code' => 'DH!' . time(),
                'total_amount' => $validatedData['total_amount'], // tổng tiền đơn hàng
                'discount_amount' => $validatedData['discount_amount'] ?? 0, // số tiền được giảm
                'final_amount' => $validatedData['final_amount'], // tổng tiền sau khi trừ giảm giá + phí ship
                'payment_method' => $validatedData['payment_method'], // phương thức thanh toán
                'shipping' => $validatedData['shipping'], // phí shipp
                'o_name' => $validatedData['o_name'],  // tên người nhận
                'o_address' => $validatedData['o_address'], // địa chỉ
                'o_phone' => $validatedData['o_phone'], // số điện thoại
                'o_mail' => $validatedData['o_mail'], // email
            ]);

            // Thêm sản phẩm vào đơn hàng
            foreach ($validatedData['products'] as $product) {
                $order->orderDetails()->create([
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'variation' => $product['variation']
                ]);
            }

            return response()->json([
                'message' => 'Success',
                'order' => $order
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        try {
            $validatedData = $request->validated();

            $order->update([
                'o_name' => $validatedData['o_name'],
                'o_address' => $validatedData['o_address'],
                'o_phone' => $validatedData['o_phone'],
                'o_mail' => $validatedData['o_mail'],
            ]);

            return response()->json([
                'message' => 'Success',
                'order' => $order
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
            $order = Order::findOrFail($order->id);

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

            $orders = $query->
                select('id', 'code', 'o_name', 'o_phone', 'final_amount', 'payment_method', 'stt_payment', 'stt_track', 'created_at')
                ->with([
                    'stt_track:id,name,next_status_allowed',
                    'stt_payment:id,name'
                ])
                ->orderByDesc('orders.created_at')
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
            $newTrackStatus = request('track_status');
            $newPaymentStatus = request('payment_status');

            $order = Order::where('id', $id)->first();

            if ($newTrackStatus) {
                $oldTrackStatus = $order->stt_track;

                $oldTrackStatusInfo = StatusTracking::where('id', $oldTrackStatus)->first();

                $allowedStatuses = $oldTrackStatusInfo->next_status_allowed;

                // Luồng trạng thái ship_cod
                if ($order->payment_method == "ship_cod") {

                    if (!in_array($newTrackStatus, $allowedStatuses)) {
                        return response()->json([
                            'message' => 'Không thể chuyển trạng thái này!'
                        ], 400);
                    }

                    $order->stt_track = $newTrackStatus;

                    if ($newTrackStatus == 6) {
                        $order->stt_payment = 2;
                    }
                    $order->save();

                    // Luồng trạng thái chuyển khoản
                } else if ($order->payment_method == "bank_transfer") {

                    if ($order->stt_payment == 1) {

                        // Chưa thanh toán nhưng được phép huỷ đơn
                        if ($newTrackStatus != 7) {
                            return response()->json([
                                'message' => 'Khách hàng chưa thanh toán',
                            ], 400);
                        }

                    } elseif ($order->stt_payment == 2) {

                        if ($newTrackStatus == 7) {

                            if (!in_array($order->stt_track, [1, 2])) {

                                return response()->json([
                                    'message' => 'Chỉ có thể huỷ đơn khi đơn hàng ở trạng thái chờ xử lý hoặc đã xử lý',
                                ], 400);

                            }
                        } else {
                            if (!in_array($newTrackStatus, $allowedStatuses)) {
                                return response()->json([
                                    'message' => 'Không thể chuyển trạng thái này',
                                ], 400);
                            }
                        }
                    }

                    $order->stt_track = $newTrackStatus;
                }
            }

            if ($newPaymentStatus) {
                if (!in_array($newPaymentStatus, [1, 2])) {
                    return response()->json([
                        'message' => 'Trạng thái thanh toán không hợp lệ!',
                    ], 400);
                }
                $order->stt_payment = $newPaymentStatus;
            }

            $order->save();

            return response()->json([
                'message' => 'Success',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    // public function destroy()
    // {
    //     try {
    //         $id = request('id');

    //         $orders = Order::whereIn('id', $id)->get();

    //         foreach ($orders as $order) {
    //             if ($order->stt_track != 9) {
    //                 return response()->json([
    //                     'message' => 'Chỉ có thể xoá những đơn hàng bị huỷ',
    //                 ], 400);
    //             }
    //         }

    //         Order::whereIn('id', $id)->delete();

    //         return response()->json([
    //             'message' => 'Success',
    //         ], 200);

    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'Failed',
    //         ], 500);
    //     }
    // }

    // Xoá trắng toàn bộ tất cả order
    // public function bulk()
    // {
    //     Order::truncate();
    // }
}
