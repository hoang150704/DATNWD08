<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\Admin\Order\UpdateOrderRequest;
use App\Jobs\SendMailSuccessOrderJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariation;
use App\Models\StatusTracking;
use App\Services\OrderActionService;
use App\Services\OrderStatusFlowService;
use App\Traits\MaskableTraits;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class OrderController extends Controller
{
    use MaskableTraits;
    public function index()
    {
        try {
            $orders = Order::select('id', 'code', 'o_name', 'o_phone', 'final_amount', 'payment_method', 'order_status_id', 'payment_status_id', 'shipping_status_id', 'created_at')
                ->with('status:id,code,name', 'paymentStatus:id,code,name', 'shippingStatus:id,code,name')
                ->orderByDesc('created_at')
                ->paginate(30);
            $orders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'o_name' => $order->o_name,
                    'phone' => $order->o_phone,
                    'final_amount' => $order->final_amount,
                    'payment_method' => $order->payment_method,
                    'order_status' => $order->status->name ?? '',
                    'payment_status' => $order->paymentStatus->name ?? '',
                    'shipping_status' => $order->shippingStatus->name ?? '',
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                ];
            });
            return response()->json([
                'message' => 'Success',
                'data' => $orders
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    private function generateUniqueOrderCode()
    {
        do {
            // Lấy ngày hiện tại
            $date = now()->format('Ymd');

            // Tạo mã số ngẫu nhiên (6 ký tự)
            $randomCode = strtoupper(Str::random(6));

            // Tạo mã đơn hàng
            $codeOrder = "ORD-{$date}-{$randomCode}";

            // Kiểm tra xem mã có tồn tại trong database không
            $exists = Order::where('code', $codeOrder)->exists();
        } while ($exists); // Nếu trùng, tạo lại

        return $codeOrder;
    }
    public function store(StoreOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $validatedData = $request->validated();

            // Kiểm tra nếu không có sản phẩm trong đơn hàng
            if (empty($validatedData['products'])) {
                return response()->json([
                    'message' => 'Không có sản phẩm nào trong đơn hàng!'
                ], 400);
            }

            // Tạo đơn hàng
            $order = Order::create([
                'code' => $this->generateUniqueOrderCode(),
                'total_amount' => $validatedData['total_amount'],
                'discount_amount' => $validatedData['discount_amount'] ?? 0,
                'final_amount' => $validatedData['final_amount'],
                'payment_method' => 'ship_cod',
                'shipping' => $validatedData['shipping'],
                'o_name' => $validatedData['o_name'],
                'o_address' => $validatedData['o_address'],
                'o_phone' => $validatedData['o_phone'],
                'o_mail' => $validatedData['o_mail'] ?? null,
                'note'  => $validatedData['note'] ?? null,
                'stt_payment' => 1,
                'stt_track' => 1
            ]);

            // Danh sách các sản phẩm trong đơn hàng
            $orderItems = [];

            foreach ($validatedData['products'] as $product) {
                $variant = ProductVariation::findOrFail($product['variation_id']);

                // Kiểm tra tồn kho trước khi trừ
                if ($variant->stock_quantity < $product['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Sản phẩm "' . $product['name'] . '" không đủ hàng tồn kho!'
                    ], 400);
                }
                //
                $variation =  $variant->getFormattedVariation();
                // Thêm sản phẩm vào danh sách orderItems
                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'weight' => $product['weight'],
                    'image' => $product['image'],
                    'variation' => json_encode($variation),
                    'product_name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                ];

                // Cập nhật lại số lượng tồn kho
                $variant->updateOrFail([
                    'stock_quantity' => (int) $variant->stock_quantity - (int) $product['quantity']
                ]);
            }
            // Thêm nhiều sản phẩm 
            OrderItem::insert($orderItems);
            SendMailSuccessOrderJob::dispatch($order);
            DB::commit();
            return response()->json([
                'message' => 'Bạn đã thêm đơn hàng thành công!',
                'order_code' => $order->code
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed',
                'errors' => $th->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $order = Order::with([
                'items',
                'status',
                'paymentStatus',
                'shippingStatus',
                'transactions',
                'shipment.shippingLogsTimeline',
                'refundRequests',
                'statusLogs.fromStatus',
                'statusLogs.toStatus',
            ])->findOrFail($id);

            // Convert dữ liệu
            $data = [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'status' => $order->status->name,
                'payment_status' => $order->paymentStatus->name ?? null,
                'shipping_status' => $order->shippingStatus->name ?? null,
                'total_amount' => $order->total_amount,
                'final_amount' => $order->final_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_fee' => $order->shipping,
                'payment_method' => $order->payment_method,
                'o_name' => $order->o_name,
                'o_phone' => $order->o_phone,
                'o_email' => $order->o_mail,
                'o_address' => $order->o_address,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image' => $item->image,
                        'variation' => $item->variation
                    ];
                }),
                'transactions' => $order->transactions->map(function ($tran) {
                    return [
                        'type' => $tran->type,
                        'method' => $tran->method,
                        'amount' => $tran->amount,
                        'status' => $tran->status,
                        'note' => $tran->note,
                        'pay_date' => $tran->vnp_pay_date,
                        'transaction_code' => $tran->transaction_code,
                        'created_at' => $tran->created_at,
                    ];
                }),
                'shipping_logs' => $order->shipment?->shippingLogs->map(function ($log) {
                    return [
                        'status' => $log->ghn_status,
                        'location'=>$log->location,
                        'note' => $log->note,
                        'created_at' => $log->timestamp
                    ];
                }),
                'refund_requests' => $order->refundRequests->map(function ($refund) {
                    return [
                        'status' => $refund->status,
                        'reason' => $refund->reason,
                        'amount' => $refund->amount,
                        'approved_by' => $refund->approved_by,
                        'approved_at' => optional($refund->approved_at),
                    ];
                }),
                'status_timelines' => $order->statusLogs->map(function ($log) {
                    return [
                        'from' => $log->fromStatus->name ?? null,
                        'to' => $log->toStatus->name ?? null,
                        'changed_by' => $log->changed_by,
                        'changed_at' => $log->changed_at,
                    ];
                }),
                'actions' => OrderActionService::availableActions($order, 'admin') 
            ];

            return response()->json([
                'message' => 'Lấy chi tiết đơn hàng thành công!',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Không thể lấy đơn hàng!',
                'error' => $th->getMessage()
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

            $orders = $query->select('id', 'code', 'o_name', 'o_phone', 'final_amount', 'payment_method', 'stt_payment', 'stt_track', 'created_at')
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

    //
    public function confirmOrder($code)
    {
        $order = Order::with('status')->where('code', $code)->firstOrFail();

        // Kiểm tra trạng thái hiện tại có thể chuyển sang 'confirmed' không
        if (!OrderStatusFlowService::canChange($order, 'confirmed')) {
            return response()->json([
                'message' => 'Không thể xác nhận đơn hàng ở trạng thái hiện tại!',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $changed = OrderStatusFlowService::change($order, 'confirmed', 'admin');

            if (!$changed) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Lỗi khi xác nhận đơn hàng!',
                ], 500);
            }

            DB::commit();
            return response()->json([
                'message' => 'Đã xác nhận đơn hàng thành công!',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đã xảy ra lỗi!',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    // Hủy order

















    //
    // public function update(UpdateOrderRequest $request, Order $order)
    // {
    //     try {
    //         $validatedData = $request->validated();

    //         $order->update([
    //             'o_name' => $validatedData['o_name'],
    //             'o_address' => $validatedData['o_address'],
    //             'o_phone' => $validatedData['o_phone'],
    //             'o_mail' => $validatedData['o_mail'],
    //         ]);

    //         return response()->json([
    //             'message' => 'Success',
    //             'order' => $order
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'Failed',
    //             'errors' => $th->getMessage(),
    //         ], 500);
    //     }
    // }














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
