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
            $order = Order::with(
                'items',
                'status',
                'paymentStatus',
                'shippingStatus',
                'transactions',
                'shipment.shippingLogs',
            )->findOrFail($id);

            return response()->json([
                'message' => 'Lấy chi tiết đơn hàng thành công!',
                'data' => $order
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

