<?php

namespace App\Http\Controllers\Api\User;

use App\Events\OrderEvent;
use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\User\OrderClientRequest;
use App\Jobs\SendMailSuccessOrderJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use App\Models\PaymentStatus;
use App\Models\ProductVariation;
use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentVnpay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderClientController extends Controller
{
    protected $paymentVnpay;

    public function __construct(PaymentVnpay $paymentVnpay)
    {
        $this->paymentVnpay = $paymentVnpay;
    }
    //
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
    //
    public function store(OrderClientRequest $request)
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

            //
            $user = auth('sanctum')->user();
            $userId = $user ? $user->id : null;
            // Kiểm tra phương thức thanh toán
            if ($validatedData['payment_method'] == 'vnpay' && $validatedData['final_amount'] == 0) {
                return response()->json([
                    'message' => 'Thanh toán VNPay không hợp lệ (số tiền phải lớn hơn 0)!'
                ], 400);
            }

            // Tạo mã đơn hàng
            $orderCode = $this->generateUniqueOrderCode();

            // Tạo đơn hàng
            $dataOrder = [
                'user_id' => $userId,
                'code' => $orderCode,
                'total_amount' => $validatedData['total_amount'],
                'discount_amount' => $validatedData['discount_amount'] ?? 0,
                'final_amount' => $validatedData['final_amount'],
                'payment_method' => $validatedData['payment_method'],
                'shipping' => $validatedData['shipping'],
                'o_name' => strip_tags($validatedData['o_name']),
                'o_address' => strip_tags($validatedData['o_address']),
                'o_phone' => $validatedData['o_phone'],
                'o_mail' => $validatedData['o_mail'] ?? null,
                'note' => strip_tags($validatedData['note'] ?? ''),
                'order_status_id' => OrderStatus::idByCode('pending'),
                'payment_status_id' => PaymentStatus::idByCode('unpaid'),
                'shipping_status_id' => ShippingStatus::idByCode('not_created'),
            ];
            //
            $order = Order::create($dataOrder);
            //

            if (!$order) {
                DB::rollBack();
                return response()->json(['message' => 'Tạo đơn hàng thất bại!'], 500);
            }

            // Broadcast và Event
            event(new OrderEvent($order));
            broadcast(new OrderEvent($order));
            Log::info('Broadcast completed');
            // Lưu bảng trạng thái đơn hàng orderstatus
            OrderStatusLog::create(
                [
                    'order_id' => $order->id,
                    'from_status_id' => null,
                    'to_status_id' => 1,
                    'changed_by' => 'system',
                    'changed_at' => now(),
                ]
            );
            // Lưu bảng thanh toán
            Transaction::create([
                'order_id' => $order->id,
                'method' => $order->payment_method,
                'type' => 'payment',
                'amount' => $order->final_amount,
                'status' => 'pending',
                'created_at' => now(),
            ]);
            // Lưu trạng thái đơn hàng shipping
            Shipment::create(
                [
                    'order_id' => $order->id,
                    'shipping_status_id' => ShippingStatus::idByCode('not_created'),
                    'shipping_fee' => $order->shipping,
                    'carrier' => 'ghn',
                    'from_estimate_date' => $validatedData['from_estimate_date'] ?? null,
                    'to_estimate_date' => $validatedData['to_estimate_date'] ?? null,
                ]
            );
            //
            $orderItems = [];

            foreach ($validatedData['products'] as $product) {
                $variant = ProductVariation::find($product['id']);

                if (!$variant) {
                    DB::rollBack();
                    return response()->json(['message' => 'Sản phẩm không tồn tại!'], 400);
                }
                $variation = $variant->getFormattedVariation();
                // Kiểm tra tồn kho trước khi trừ
                if ($variant->stock_quantity < $product['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Sản phẩm "' . $product['name'] . '" không đủ hàng tồn kho!'
                    ], 400);
                }

                // Lưu sản phẩm vào order_itemss
                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['id'] ?? null,
                    'weight' => $product['weight'],
                    'image' => $product['image_url'] ?? null,
                    'variation' => json_encode($variation),
                    'product_name' => strip_tags($product['name']),
                    'price' => $product['sale_price'] ?? $product['regular_price'], // Lấy giá khuyến mãi nếu có
                    'quantity' => $product['quantity'],
                ];

                // Giảm số lượng tồn kho
                $variant->decrement('stock_quantity', (int) $product['quantity']);
            }

            // Thêm nhiều sản phẩm vào bảng `order_items`
            OrderItem::insert($orderItems);


            // Sau khi hoàn tất việc tạo đơn hàng và trước khi commit transaction
            if (isset($validatedData['voucher_code'])) {
                $voucher = Voucher::where('code', $validatedData['voucher_code'])->first();

                if ($voucher) {
                    // Chỉ tăng số lượt sử dụng sau khi đơn hàng được tạo thành công
                    if ($voucher->usage_limit && $voucher->times_used < $voucher->usage_limit) {
                        // Tăng số lần sử dụng ngay trước khi commit
                        $voucher->increment('times_used');
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Voucher đã đạt giới hạn số lần sử dụng!'
                        ], 400);
                    }
                }
            }

            // Gửi email xác nhận đơn hàng (background job)
            SendMailSuccessOrderJob::dispatch($order);
            DB::commit();
            //Xóa giỏ hhangf
            if ($userId) {
                try {
                    // Lấy danh sách cart_id của user
                    $cart = Cart::where('user_id', $userId)->first();

                    if ($cart) {
                        // Xóa các cart_items có trong đơn hàng
                        Log::info('Deleting cart items with variation_ids:', array_column($orderItems, 'variation_id'));

                        CartItem::where('cart_id', $cart->id)
                            ->whereIn('variation_id', array_column($orderItems, 'variation_id'))
                            ->delete();
                    }
                } catch (\Throwable $th) {
                    Log::error("Lỗi khi xóa cart_items cho user_id {$userId}: " . $th->getMessage());
                }
            }

            // Nếu phương thức thanh toán là VNPay, trả về URL thanh toán
            if ($order->payment_method == "vnpay") {
                $paymentUrl = $this->paymentVnpay->createPaymentUrl($order);
                return response()->json([
                    'message' => 'Thành công',
                    'url' => $paymentUrl,
                    'code' => 200
                ], 201);
            }

            return response()->json([
                'message' => 'Bạn đã thêm đơn hàng thành công!',
                'order_code' => $order->code,
                'user' => $user,
                'code' => 201
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi trong quá trình tạo đơn hàng!',
                'errors' => $th->getMessage(),
                'data' => $dataOrder
            ], 500);
        }
    }

    // Xử lí dữ liệu nhận về khi thanh toán online
    public function callbackPayment(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_SecureHash = $request['vnp_SecureHash'];

        $data = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $data[$key] = $value;
            }
        }

        unset($data['vnp_SecureHash']);
        ksort($data);
        $hashData = urldecode(http_build_query($data));
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $order = Order::where('code', $request['vnp_TxnRef'])->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng!',
            ]);
        }

        $isSuccess = ($secureHash === $vnp_SecureHash && $request['vnp_ResponseCode'] == '00');

        // Cập nhật giao dịch
        $transaction = Transaction::create([
            'order_id'           => $order->id,
            'method'             => 'vnpay',
            'type'               => 'payment',
            'amount'             => ($request['vnp_Amount'] ?? 0) / 100,
            'transaction_code'   => $request['vnp_TxnRef'],
            'vnp_transaction_no' => $request['vnp_TransactionNo'] ?? null,
            'vnp_bank_code'      => $request['vnp_BankCode'] ?? null,
            'vnp_pay_date'       => now(),
            'status'             => $isSuccess ? 'success' : 'failed',
            'note'               => $isSuccess ? 'Thanh toán thành công từ VNPAY' : 'Thanh toán thất bại hoặc sai chữ ký',
        ]);

        // Nếu thanh toán thành công thì cập nhật order
        if ($isSuccess) {
            $order->update([
                'payment_status_id' => PaymentStatus::idByCode('paid'),
            ]);
        }

        return response()->json([
            'success' => $isSuccess,
            'message' => $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại hoặc sai chữ ký',
            'transaction_id' => $transaction->id,
        ]);
    }
    //Lấy ra danh sách sản phẩm
    public function getOrdersForUser(Request $request)
    {
        try {
            $status = $request->get('status'); // query param
            $userId = auth('sanctum')->user()->id;

            $query = Order::with(['items', 'status', 'paymentStatus'])
                ->where('user_id', $userId);

            switch ($status) {
                case 'waiting_payment':
                    $query->where('payment_method', 'vnpay')
                        ->whereHas('paymentStatus', fn($q) => $q->where('code', 'unpaid'));
                    break;

                case 'pending':
                    $query->whereHas('status', fn($q) => $q->where('code', 'pending'));
                    break;

                case 'confirmed':
                    $query->whereHas('status', fn($q) => $q->where('code', 'confirmed'));
                    break;

                case 'shipping':
                    $query->whereHas('status', fn($q) => $q->where('code', 'shipping'));
                    break;

                case 'completed':
                    $query->whereHas('status', fn($q) => $q->where('code', 'completed'));
                    break;

                case 'closed':
                    $query->whereHas('status', fn($q) => $q->where('code', 'closed'));
                    break;

                case 'cancelled':
                    $query->whereHas('status', fn($q) => $q->where('code', 'cancelled'));
                    break;

                case 'refund':
                    $query->whereHas(
                        'status',
                        fn($q) =>
                        $q->whereIn('code', ['return_requested', 'return_approved', 'refunded'])
                    );
                    break;

                default:
                    // Không lọc
                    break;
            }

            $orders = $query->latest()->paginate(10);

            $data = $orders->map(function ($order) {
                return [
                    'code' => $order->code,
                    'status' => $order->status->name ?? '',
                    'status_code' => $order->status->code ?? '',
                    'payment_status' => $order->paymentStatus->name ?? '',
                    'payment_code' => $order->paymentStatus->code ?? '',
                    'created_at' => $order->created_at->format('d-m-Y H:i'),
                    'final_amount' => $order->final_amount,
                    'products' => $order->items->map(function ($item) {
                        return [
                            'name' => $item->product_name,
                            'image' => $item->image,
                            'price' => $item->price,
                            'quantity' => $item->quantity,
                            'variation' => json_decode($item->variation, true),
                        ];
                    }),
                ];
            });

            return response()->json([
                'message' => 'Success',
                'data' => [
                    'orders' => $data,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách đơn hàng!',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    //
    public function getOrderDetail($orderCode)
    {
        try {
            //
            $order = Order::with('items', 'status', 'paymentStatus', 'refundRequests')
                ->where('code', $orderCode)
                ->firstOrFail();

            // COnvert lại dữ liệu
            $orderDetails = [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'status' => $order->status->name,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image_url' => $item->image
                    ];
                }),
                'total_amount' => $order->total_amount,
                'final_amount' => $order->final_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_fee' => $order->shipping, // phí ship
                'refund_requests' => $order->refundRequests->map(function ($refundRequest) { // Hoàn đơn
                    return [
                        'request_id' => $refundRequest->id,
                        'status' => $refundRequest->status,
                        'reason' => $refundRequest->reason,
                        'requested_amount' => $refundRequest->amount,
                        'approved_by' => $refundRequest->approved_by,
                        'approved_at' => $refundRequest->approved_at ? $refundRequest->approved_at->toDateTimeString() : null
                    ];
                })
            ];

            return response()->json([
                'message' => 'Success',
                'data' => $orderDetails
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng',
                'error' => $th->getMessage()
            ], 404);
        }
    }


    // Helper method to determine what actions are available based on order status
    private function determineAvailableActions($order)
    {
        $actions = [];
        switch ($order->status->code) {
            case 'pending':
                $actions = ['cancel'];
                break;
            case 'confirmed':
                $actions = ['cancel'];
                break;
            case 'shipping':
                $actions = [];
                break;
            case 'completed':
                $actions = ['return', 'close'];
                break;
            case 'closed':
                $actions = [];
                break;
            case 'return_requested':
                $actions = ['cancel_return'];
                break;
            case 'return_approved':
                $actions = [];
                break;
            case 'refunded':
                $actions = [];
                break;
            case 'cancelled':
                $actions = [];
                break;
        }
        return $actions;
    }
}
