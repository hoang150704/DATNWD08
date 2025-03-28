<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Resources\TransactionResource;
use App\Jobs\SendMailSuccessOrderJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use App\Models\PaymentStatus;
use App\Models\ProductVariation;
use App\Models\RefundRequest;
use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
use App\Models\Transaction;
use App\Services\GhnApiService;
use App\Services\OrderActionService;
use App\Services\OrderStatusFlowService;
use App\Services\PaymentVnpay;
use App\Traits\MaskableTraits;
use App\Traits\OrderTraits;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use MaskableTraits;
    use OrderTraits;
    protected $paymentVnpay;
    protected $ghn;

    public function __construct(PaymentVnpay $paymentVnpay, GhnApiService $ghn)
    {
        $this->paymentVnpay = $paymentVnpay;
        $this->ghn = $ghn;
    }

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
                'shipment.shippingLogs',
                'shipment.shippingLogsTimeline',
                'refundRequests',
                'statusLogs.fromStatus',
                'statusLogs.toStatus',
            ])->findOrFail($id);
    
            // Convert dữ liệu
            $data = [
                'order_id' => $order->id,
                'order_code' => $order->code,
    
                // Trạng thái + phụ đề mô tả chi tiết
                'status' => $order->status->name,
                'subtitle' => $this->generateOrderSubtitle($order), 
    
                // Trạng thái thanh toán + giao hàng
                'payment_status' => $order->paymentStatus->name ?? null,
                'shipping_status' => $order->shippingStatus->name ?? null,
    
                // Thông tin số tiền
                'total_amount' => $order->total_amount,
                'final_amount' => $order->final_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_fee' => $order->shipping,
    
                // Thông tin người nhận
                'payment_method' => $order->payment_method,
                'o_name' => $order->o_name,
                'o_phone' => $order->o_phone,
                'o_email' => $order->o_mail,
                'o_address' => $order->o_address,
    
                // Danh sách sản phẩm
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image' => $item->image,
                        'variation' => $item->variation
                    ];
                }),
    
                // Giao dịch thanh toán vag hoàn tiền dùng resource để convert dữ liệu
                'transactions' => TransactionResource::collection(
                    $order->transactions->sortBy('created_at') 
                ),
    
                // Lịch sử vận chuyển theo đúng thứ tự thời gian
                'shipping_logs' => $order->shipment?->shippingLogsTimeline->map(function ($value) {
                    return [
                        'status' => $value->ghn_status,
                        'location' => $value->location,
                        'note' => $value->note,
                        'created_at' => $value->timestamp
                    ];
                }),
    
                // Yêu cầu hoàn hàng
                'refund_requests' => $order->refundRequests->map(function ($refund) {
                    return [
                        'status' => $refund->status,
                        'reason' => $refund->reason,
                        'amount' => $refund->amount,
                        'approved_by' => $refund->approved_by,
                        'approved_at' => optional($refund->approved_at),
                    ];
                }),
    
                // Timeline trạng thái đơn hàng
                'status_timelines' => $order->statusLogs->map(function ($statusTimeLine) {
                    return [
                        'from' => $statusTimeLine->fromStatus->name ?? null,
                        'to' => $statusTimeLine->toStatus->name ?? null,
                        'changed_by' => $statusTimeLine->changed_by,
                        'changed_at' => $statusTimeLine->changed_at,
                    ];
                }),
    
                // Hành động admin có thể làm
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

    //Xác nhận đơn hàng
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
    // Hủy đơn hàng
    public function cancelOrderByAdmin(Request $request, $code)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:1000'
        ]);

        $order = Order::with('shipment')
            ->where('code', $code)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền huỷ đơn này'
            ], 404);
        }

        if (!in_array($order->status->code, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Không thể hủy đơn hàng ở trạng thái hiện tại!'], 400);
        }

        DB::beginTransaction();

        try {
            // // Cập nhật trạng thái đơn hàng & shipping
            $fromStatusId = $order->order_status_id;
            $cancelStatusId = OrderStatus::idByCode('cancelled');
            $cancelStatusShipId = ShippingStatus::idByCode('cancelled');
            if ($order->payment_method === 'ship_cod') {
                $paymentStatus = PaymentStatus::idByCode('cancelled');
                $order->payment_status_id = $paymentStatus;
            }
            $order->update([
                'shipping_status_id' => $cancelStatusShipId,
                'order_status_id' => $cancelStatusId,
                'cancel_reason' => $validated['cancel_reason'],
                'cancel_by' => 'admin',
                'cancelled_at' => now()
            ]);

            // Ghi log trạng thái
            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $cancelStatusId,
                'changed_by' => 'admin',
                'changed_at' => now(),
            ]);

            // Nếu thanh toán online (VNPAY) & đã thanh toán
            if ($order->payment_method === 'vnpay' && $order->paymentStatus->code === 'paid') {
                // Tạo bản ghi refund_requests
                RefundRequest::create([
                    'order_id' => $order->id,
                    'type' => 'not_received',
                    'reason' => $validated['cancel_reason'],
                    'amount' => $order->final_amount,
                    'status' => 'approved',
                    'approved_by' => 'system',
                    'approved_at' => now()
                ]);

                // Lấy transaction gốc (thanh toán thành công)
                $paymentTransaction = $order->transactions()
                    ->where('method', 'vnpay')
                    ->where('type', 'payment')
                    ->where('status', 'success')
                    ->latest()
                    ->first();

                // Tạo transaction hoàn tiền mới (trạng thái pending)
                Transaction::create([
                    'order_id' => $order->id,
                    'method' => 'vnpay',
                    'type' => 'refund',
                    'amount' => $order->final_amount,
                    'status' => 'pending',
                    'note' =>  $validated['cancel_reason'],
                    'created_at' => now()
                ]);

                // Chuẩn bị dữ liệu gọi API hoàn tiền
                $refundData = [
                    'txn_ref' => $paymentTransaction->transaction_code,
                    'amount' => $paymentTransaction->amount,
                    'txn_date' => optional($paymentTransaction->vnp_pay_date)->format('YmdHis'),
                    'txn_no' => $paymentTransaction->vnp_transaction_no,
                    'type' => '02',
                    'create_by' => 'system',
                    'ip' => $request->ip(),
                    'order_info' => 'Khách huỷ đơn hàng chưa nhận'
                ];

                // Gọi API hoàn tiền
                $result = $this->paymentVnpay->refundTransaction($refundData);

                // Xử lý kết quả hoàn tiền
                if (isset($result['vnp_ResponseCode']) && $result['vnp_ResponseCode'] === '00') {
                    $order->update([
                        'payment_status_id' => PaymentStatus::idByCode('refunded'),
                    ]);
                }
                Transaction::create([
                    'order_id' => $order->id,
                    'method' => 'vnpay',
                    'type' => 'refund',
                    'amount' => $order->final_amount,
                    'status' => $result['success'] ? 'success' : 'failed',
                    'transaction_code' => $order->code,
                    'vnp_transaction_no' => $result['response_data']['vnp_TransactionNo'] ?? null,
                    'vnp_bank_code' => $result['response_data']['vnp_BankCode'] ?? null,
                    'vnp_response_code' => $result['response_data']['vnp_ResponseCode'] ?? null,
                    'vnp_transaction_status' => $result['response_data']['vnp_TransactionStatus'] ?? null,
                    'vnp_refund_request_id' => $result['response_data']['vnp_ResponseId'] ?? null,
                    'vnp_pay_date' => isset($result['response_data']['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $result['response_data']['vnp_PayDate']) : now(),
                    'vnp_create_date' => now(),
                    'note' => $result['error'] ?? 'Hoàn tiền thành công từ VNPAY',
                ]);
            }

            // Nếu đã có vận đơn GHN
            if (!in_array($order->shippingStatus->code, ['not_created', 'cancelled'])) {
                $dataCancelOrderGhn[] = $order->shipment->shipping_code;
            }

            DB::commit();
            return response()->json(['message' => 'Đơn hàng đã được hủy'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Cancel Order Error: ' . $th->getMessage());
            return response()->json(['message' => 'Lỗi khi hủy đơn hàng', 'errors' => $th->getMessage()], 500);
        }
    }
    //Xử lí yêu cầu trả hàng
    //1. Đồng ý 
    public function approveReturn($code)
    {
        $order = Order::with('refundRequest')->where('code', $code)->firstOrFail();

        if ($order->status->code !== 'return_requested') {
            return response()->json(['message' => 'Không thể duyệt yêu cầu ở trạng thái hiện tại'], 400);
        }

        $fromStatusId = $order->order_status_id;
        $toStatusId = OrderStatus::idByCode('return_approved');

        DB::beginTransaction();

        try {
            $order->update(['order_status_id' => $toStatusId]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatusId,
                'changed_by' => 'admin',
                'changed_at' => now(),
            ]);

            $order->refundRequest?->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 'admin',
            ]);

            Transaction::create([
                'order_id'   => $order->id,
                'method'     => $order->payment_method,
                'type'       => 'refund',
                'amount'     => $order->final_amount,
                'status'     => 'pending',
                'note'       => 'Duyệt hoàn tiền sau khi nhận hàng',
                'created_at' => now(),
            ]);
            DB::commit();
            return response()->json(['message' => 'Đã duyệt yêu cầu hoàn tiền'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi duyệt hoàn tiền'], 500);
        }
    }

    // 2. TỪ chối
    public function rejectReturn(Request $request, $code)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:1000'
        ]);

        $order = Order::with('refundRequest')->where('code', $code)->firstOrFail();

        if ($order->status->code !== 'return_requested') {
            return response()->json(['message' => 'Không thể từ chối ở trạng thái hiện tại'], 400);
        }

        $fromStatusId = $order->order_status_id;
        $toStatusId = OrderStatus::idByCode('completed');

        DB::beginTransaction();

        try {
            $order->update(['order_status_id' => $toStatusId]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatusId,
                'changed_by' => 'admin',
                'changed_at' => now(),
            ]);

            $order->refundRequest?->update([
                'status' => 'rejected',
                'reject_reason' => $request->reject_reason,
                'rejected_at' => now(),
                'rejected_by' => 'admin',
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã từ chối yêu cầu hoàn tiền'],200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi từ chối hoàn tiền'], 500);
        }
    }

    //Hoàn tiền
    // 1 Hoàn tiền vnpay

    public function refundAuto($code)
    {
        $order = Order::with(['transactions', 'refundRequest'])->where('code', $code)->firstOrFail();

        if ($order->payment_method !== 'vnpay' || $order->paymentStatus->code !== 'paid') {
            return response()->json(['message' => 'Đơn hàng không hợp lệ để hoàn tiền tự động'], 400);
        }

        DB::beginTransaction();

        try {
            // Tìm transaction hoàn tiền đang chờ xử lý
            $refundTransaction = $order->transactions()
                ->where('type', 'refund')
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$refundTransaction) {
                return response()->json(['message' => 'Không tìm thấy giao dịch hoàn tiền đang chờ xử lý'], 404);
            }

            // Tìm transaction thanh toán gốc
            $paymentTransaction = $order->transactions()
                ->where('type', 'payment')
                ->where('status', 'success')
                ->where('method', 'vnpay')
                ->latest()
                ->first();

            if (!$paymentTransaction) {
                return response()->json(['message' => 'Không tìm thấy giao dịch thanh toán gốc'], 404);
            }

            // Gọi API hoàn tiền
            $refundData = [
                'txn_ref'    => $paymentTransaction->transaction_code,
                'amount'     => $paymentTransaction->amount,
                'txn_date'   => optional($paymentTransaction->vnp_pay_date)?->format('YmdHis'),
                'txn_no'     => $paymentTransaction->vnp_transaction_no,
                'type'       => '02',
                'create_by'  => 'admin',
                'ip'         => request()->ip(),
                'order_info' => 'Hoàn tiền sau hoàn hàng',
            ];

            $result = $this->paymentVnpay->refundTransaction($refundData);

            Transaction::create([
                'order_id' => $order->id,
                'method' => 'vnpay',
                'type' => 'refund',
                'amount' => $order->final_amount,
                'status' => $result['success'] ? 'success' : 'failed',
                'transaction_code' => $order->code,
                'vnp_transaction_no' => $result['response_data']['vnp_TransactionNo'] ?? null,
                'vnp_bank_code' => $result['response_data']['vnp_BankCode'] ?? null,
                'vnp_response_code' => $result['response_data']['vnp_ResponseCode'] ?? null,
                'vnp_transaction_status' => $result['response_data']['vnp_TransactionStatus'] ?? null,
                'vnp_refund_request_id' => $result['response_data']['vnp_ResponseId'] ?? null,
                'vnp_pay_date' => isset($result['response_data']['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $result['response_data']['vnp_PayDate']) : now(),
                'vnp_create_date' => now(),
                'note' => $result['error'] ?? 'Hoàn tiền thành công qua VNPAY',
            ]);

            if ($result['success']) {
                $fromStatusId = $order->order_status_id;
                $refundedStatusId = OrderStatus::idByCode('refunded');

                $order->update([
                    'order_status_id' => $refundedStatusId,
                    'payment_status_id' => PaymentStatus::idByCode('refunded'),
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $refundedStatusId,
                    'changed_by' => 'admin',
                    'changed_at' => now(),
                    'note' => 'Hoàn tiền tự động thành công qua VNPAY',
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => $result['success'] ? 'Hoàn tiền thành công' : 'Hoàn tiền thất bại'
            ], $result['success'] ? 200 : 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Refund Auto Error: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi khi hoàn tiền tự động'], 500);
        }
    }



    //2. Hoàn tiền thủ công
    public function refundManual(Request $request, $code)
    {
        $request->validate([
            'proof_image' => 'required|url',
            'note' => 'nullable|string|max:1000',
            'transfer_reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:1', // nếu cho hoàn 1 phần
        ]);
    
        $order = Order::where('code', $code)->firstOrFail();
    
        // Tạo mới transaction sucess
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'method' => $order->payment_method,
            'type' => 'refund',
            'amount' => $request->amount,
            'status' => 'success', 
            'proof_images' => $request->proof_image,
            'note' => $request->note,
            'transfer_reference' => $request->transfer_reference,
            'created_at' => now(),
        ]);
    
        //Cập nhật trạng thái đơn 
        $fromStatusId = $order->order_status_id;
        $refundedStatusId = OrderStatus::idByCode('refunded');
    
        $order->update([
            'order_status_id' => $refundedStatusId,
            'payment_status_id' => PaymentStatus::idByCode('refunded'),
        ]);
    
        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $refundedStatusId,
            'changed_by' => 'admin',
            'changed_at' => now(),
            'note' => 'Hoàn tiền thủ công đã được thực hiện thành công',
        ]);
    
        return response()->json([
            'message' => 'Đã hoàn tiền thủ công thành công',
            'transaction_id' => $transaction->id,
        ], 200);
    }
    

    // 3. Hoàn tiền 1 phần
    public function refundPartial($code, Request $request)
    {
        $order = Order::with(['transactions', 'refundRequest'])->where('code', $code)->firstOrFail();
    
        if ($order->payment_method !== 'vnpay' || $order->paymentStatus->code !== 'paid') {
            return response()->json(['message' => 'Đơn hàng không hợp lệ để hoàn tiền tự động'], 400);
        }
    
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $order->final_amount,
        ]);
    
        DB::beginTransaction();
    
        try {
            $paymentTransaction = $order->transactions()
                ->where('type', 'payment')
                ->where('status', 'success')
                ->where('method', 'vnpay')
                ->latest()
                ->first();
    
            if (!$paymentTransaction) {
                return response()->json(['message' => 'Không tìm thấy giao dịch thanh toán gốc'], 404);
            }
    
            $refundData = [
                'txn_ref'    => $paymentTransaction->transaction_code,
                'amount'     => $validated['amount'],
                'txn_date'   => optional($paymentTransaction->vnp_pay_date)?->format('YmdHis'),
                'txn_no'     => $paymentTransaction->vnp_transaction_no,
                'type'       => '02',
                'create_by'  => 'admin',
                'ip'         => request()->ip(),
                'order_info' => 'Hoàn tiền một phần qua VNPAY',
            ];
    
            $result = $this->paymentVnpay->refundTransaction($refundData);
    
            Transaction::create([
                'order_id' => $order->id,
                'method' => 'vnpay',
                'type' => 'refund', // Hoặc 'refund_partial' nếu bạn muốn phân biệt
                'amount' => $validated['amount'],
                'status' => $result['success'] ? 'success' : 'failed',
                'transaction_code' => $order->code,
                'vnp_transaction_no' => $result['response_data']['vnp_TransactionNo'] ?? null,
                'vnp_bank_code' => $result['response_data']['vnp_BankCode'] ?? null,
                'vnp_response_code' => $result['response_data']['vnp_ResponseCode'] ?? null,
                'vnp_transaction_status' => $result['response_data']['vnp_TransactionStatus'] ?? null,
                'vnp_refund_request_id' => $result['response_data']['vnp_ResponseId'] ?? null,
                'vnp_pay_date' => isset($result['response_data']['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $result['response_data']['vnp_PayDate']) : now(),
                'vnp_create_date' => now(),
                'note' => $result['error'] ?? 'Hoàn tiền một phần qua VNPAY',
            ]);
    
            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => $order->order_status_id,
                'to_status_id' => $order->order_status_id, // không đổi
                'changed_by' => 'admin',
                'changed_at' => now(),
                'note' => 'Hoàn tiền một phần qua VNPAY số tiền: ' . number_format($validated['amount']),
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => $result['success'] ? 'Hoàn tiền một phần thành công' : 'Hoàn tiền thất bại'
            ], $result['success'] ? 200 : 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Refund Partial Error: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi khi hoàn tiền một phần'], 500);
        }
    }
    


    //Xác nhận đã nhận tiền
    public function confirmReturnReceived($shipmentId)
{
    $shipment = Shipment::with('order', 'shippingStatus')->find($shipmentId);

    if (!$shipment) {
        return response()->json(['message' => 'Không tìm thấy vận đơn'], 404);
    }

    if ($shipment->shippingStatus->code !== 'returned') {
        return response()->json(['message' => 'Đơn hàng chưa được GHN hoàn về, không thể xác nhận'], 400);
    }

    if ($shipment->return_confirmed) {
        return response()->json(['message' => 'Đơn hàng đã được xác nhận hoàn về trước đó'], 400);
    }

    $shipment->update([
        'return_confirmed' => true,
        'return_confirmed_at' => now(),
    ]);

    ShippingLog::create([
        'shipment_id' => $shipment->id,
        'ghn_status' => 'manual_return_confirmed',
        'mapped_status_id' => $shipment->shipping_status_id,
        'note' => 'Admin đã xác nhận hàng hoàn về kho',
        'location' => 'Kho Shine Light',
        'timestamp' => now(),
    ]);

    return response()->json(['message' => 'Xác nhận hoàn hàng thành công'],200);
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
