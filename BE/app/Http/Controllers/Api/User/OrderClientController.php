<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\ShippingStatusEnum;
use App\Events\CancelOrderEvent;
use App\Events\OrderEvent;
use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\User\OrderClientRequest;
use App\Http\Resources\RefundRequestResource;
use App\Jobs\CancelOrderExpriedPaymentTimeOut;
use App\Jobs\SendMailOrderCancelled;
use App\Jobs\SendMailSuccessOrderJob;
use App\Jobs\SendVerifyGuestOrderJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Comment as ModelsComment;
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
use App\Models\SpamLog;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\GhnApiService;
use App\Services\OrderActionService;
use App\Services\Orders\Client\CancelOrderService;
use App\Services\OrderStatusFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentVnpay;
use App\Services\SpamProtectionService;
use App\Services\TransactionFlowService;
use App\Traits\MaskableTraits;
use App\Traits\OrderTraits;
use Carbon\Carbon;
use Dom\Comment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderClientController extends Controller
{
    use OrderTraits;
    use MaskableTraits;

    protected $paymentVnpay;
    protected $ghn;
    protected int $maxWeightGhn = 20000;
    protected int $defaultServiceTypeId = 2;
    protected int $heavyServiceTypeId = 5;
    protected string $carrier = 'ghn';
    protected string $defaultOrderStatus = 'pending';
    protected string $defaultPaymentStatus = 'unpaid';
    protected string $defaultShippingStatus = ShippingStatusEnum::NOT_CREATED;
    public function __construct(PaymentVnpay $paymentVnpay, GhnApiService $ghn)
    {
        $this->paymentVnpay = $paymentVnpay;

        $this->ghn = $ghn;
    }

    // mã đơn hàng
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

    // Thanh toán
    public function store(OrderClientRequest $request)
    {
        $totalWeight = 0;
        $maxWeightGhn = 20000;
        $paymentTimeout = 60;
        try {
            DB::beginTransaction();
            $validatedData = $request->validated();

            // Kiểm tra nếu không có sản phẩm trong đơn hàng
            if (empty($validatedData['products'])) {
                return response()->json([
                    'message' => 'Không có sản phẩm nào trong đơn hàng'
                ], 400);
            }

            //
            $user = auth('sanctum')->user();
            $userId = $user ? $user->id : null;
            if (SpamProtectionService::isBanned()) {
                return response()->json([
                    'message' => 'Bạn đã bị hạn chế do hành vi đặt hàng bất thường.'
                ], 403);
            }

            if (!SpamProtectionService::checkSpamAndAutoBan()) {
                return response()->json([
                    'message' => 'Hệ thống phát hiện spam, bạn đã bị tạm khóa.'
                ], 429);
            }
            // Kiểm tra phương thức thanh toán
            if ($validatedData['payment_method'] == 'vnpay' && $validatedData['final_amount'] == 0) {
                return response()->json([
                    'message' => 'Thanh toán VNPay không hợp lệ (số tiền phải lớn hơn 0)'
                ], 400);
            }

            // Tạo mã đơn hàng
            $orderCode = $this->generateUniqueOrderCode();
            // Tạo đơn hàng
            $dataOrder = [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
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
                'shipping_status_id' => ShippingStatus::idByCode(ShippingStatusEnum::NOT_CREATED),
            ];
            //
            $order = Order::create($dataOrder);
            //

            if (!$order) {
                DB::rollBack();
                return response()->json(['message' => 'Tạo đơn hàng thất bại'], 500);
            }

            $voucher = null;
            if ($request->voucher_code) {
                $voucher = Voucher::where('code', $request->voucher_code)->first();

                // Kiểm tra nếu voucher tồn tại và còn lượt sử dụng
                if ($voucher && $voucher->usage_limit > 0) {
                    // Giảm số lần sử dụng ngay trước khi commit
                    $voucher->decrement('usage_limit');
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Voucher đã đạt giới hạn số lần sử dụng!'
                    ], 400);
                }
            }
            // Chỉ broadcast khi voucher hợp lệ hoặc không có voucher
            broadcast(new OrderEvent($order, $voucher));

            // Lưu bảng trạng thái đơn hàng orderstatus
            OrderStatusFlowService::createInitialStatus($order);
            // Lưu bảng thanh toán
            $transaction = TransactionFlowService::create(
                [
                    'order' => $order,
                    'method' => $order->payment_method,
                    'type' => 'payment',
                    'amount' => $order->final_amount,
                    'status' => 'pending'
                ]
            );
            if (!$transaction) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Không thể tạo giao dịch do không đúng luồng xử lý'
                ], 400);
            }
            // Lưu trạng thái đơn hàng shipping
            Shipment::create(
                [
                    'order_id' => $order->id,
                    'shipping_status_id' => ShippingStatus::idByCode(ShippingStatusEnum::NOT_CREATED),
                    'carrier' => 'ghn',
                    'from_estimate_date' => $validatedData['time']['from_estimate_date'] ?? null,
                    'to_estimate_date' => $validatedData['time']['to_estimate_date'] ?? null,
                ]
            );
            // Xử lí order_items
            $orderItems = [];

            foreach ($validatedData['products'] as $product) {
                $variant = ProductVariation::lockForUpdate()->find($product['id']);
                $totalWeight += $product['weight'] * $product['quantity'];

                if (!$variant) {
                    DB::rollBack();
                    return response()->json(['message' => 'Sản phẩm không tồn tại'], 400);
                }
                $variation = $variant->getFormattedVariation();
                // Kiểm tra tồn kho trước khi trừ
                if ($variant->stock_quantity < $product['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Sản phẩm "' . $product['name'] . '" không đủ hàng tồn kho'
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
            if ($totalWeight >= $maxWeightGhn) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Tổng cân nặng của đơn hạng đã vượt quá mức cho phép, vui lòng chia đơn hàng này thành 2 đơn nhỏ'
                ], 400);
            }
            // Thêm nhiều sản phẩm vào bảng `order_items`
            OrderItem::insert($orderItems);
            // Gửi email xác nhận đơn hàng

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
                //
                $paymentUrl = $this->paymentVnpay->createPaymentUrl($order, $paymentTimeout);
                $order->update(
                    [
                        'payment_url' => $paymentUrl,
                        'expiried_at' => now()->addMinutes($paymentTimeout)
                    ]
                );
                CancelOrderExpriedPaymentTimeOut::dispatch($order)->delay(now()->addMinutes($paymentTimeout));
                return response()->json([
                    'message' => 'Thành công',
                    'url' => $paymentUrl,
                    'code' => 200
                ], 201);
            }

            return response()->json([
                'message' => 'Bạn đã thêm đơn hàng thành công',
                'order_code' => $order->code,
                'user' => $user,
                'code' => 201
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi trong quá trình tạo đơn hàng',
                'errors' => $th->getMessage(),
            ], 500);
        }
    }

    //Call baack
    public function callbackPayment(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_SecureHash = $request['vnp_SecureHash'];
        //
        $order = Order::where('code', $request['vnp_TxnRef'])->first();
        if (!$order) {
            Transaction::create([
                'order_id' => null,
                'method' => 'vnpay',
                'type' => 'payment',
                'amount' => ($request['vnp_Amount'] ?? 0) / 100,
                'transaction_code' => $request['vnp_TxnRef'],
                'vnp_transaction_no' => $request['vnp_TransactionNo'] ?? null,
                'vnp_bank_code' => $request['vnp_BankCode'] ?? null,
                'vnp_bank_tran_no' => $request['vnp_BankTranNo'] ?? null,
                'vnp_card_type' => $request['vnp_CardType'] ?? null,
                'vnp_pay_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
                'vnp_response_code' => $request['vnp_ResponseCode'] ?? null,
                'vnp_transaction_status' => $request['vnp_TransactionStatus'] ?? null,
                'vnp_create_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
                'status' => 'failed',
                'note' => 'Không tìm thấy đơn hàng',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng',
            ]);
        }
        // xác thực vnpay
        $data = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $data[$key] = $value;
            }
        }

        unset($data['vnp_SecureHash']);
        ksort($data);
        $hashData = http_build_query($data, '', '&');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash !== $vnp_SecureHash) {
            Transaction::create([
                'order_id' => $order?->id, // Có thể null nếu đơn ko tìm thấy
                'method' => 'vnpay',
                'type' => 'payment',
                'amount' => ($request['vnp_Amount'] ?? 0) / 100,
                'transaction_code' => $request['vnp_TxnRef'],
                'vnp_transaction_no' => $request['vnp_TransactionNo'] ?? null,
                'vnp_bank_code' => $request['vnp_BankCode'] ?? null,
                'vnp_bank_tran_no' => $request['vnp_BankTranNo'] ?? null,
                'vnp_card_type' => $request['vnp_CardType'] ?? null,
                'vnp_pay_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
                'vnp_response_code' => $request['vnp_ResponseCode'] ?? null,
                'vnp_transaction_status' => $request['vnp_TransactionStatus'] ?? null,
                'vnp_create_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
                'status' => 'failed',
                'note' => 'Xác thực chữ ký thất bại',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sai chữ kí',
            ]);
        }
        // tránh tạo trùng đơn hàng
        $exists = Transaction::where('transaction_code', $request['vnp_TxnRef'])
            ->where('vnp_transaction_no', $request['vnp_TransactionNo'] ?? null)
            ->where('status', 'success')
            ->where('type', 'payment')
            ->where('method', 'vnpay')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'Giao dịch đã xử lý',
            ]);
        }

        $status = $request['vnp_ResponseCode'] === '00' ? 'success' : 'failed'; // xem nó thanh toán thành công hay thất bại

        Transaction::create([
            'order_id' => $order->id,
            'method' => 'vnpay',
            'type' => 'payment',
            'amount' => ($request['vnp_Amount'] ?? 0) / 100,
            'transaction_code' => $request['vnp_TxnRef'],
            'vnp_transaction_no' => $request['vnp_TransactionNo'] ?? null,
            'vnp_bank_code' => $request['vnp_BankCode'] ?? null,
            'vnp_bank_tran_no' => $request['vnp_BankTranNo'] ?? null,
            'vnp_card_type' => $request['vnp_CardType'] ?? null,
            'vnp_pay_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
            'vnp_response_code' => $request['vnp_ResponseCode'] ?? null,
            'vnp_transaction_status' => $request['vnp_TransactionStatus'] ?? null,
            'vnp_create_date' => isset($request['vnp_PayDate']) ? Carbon::createFromFormat('YmdHis', $request['vnp_PayDate']) : null,
            'status' => $status,
            'note' => $this->paymentVnpay->mapVnpResponseCode($request['vnp_ResponseCode'] ?? null),
        ]);

        if ($status === 'success') {
            if ($order->payment_status_id !== PaymentStatus::idByCode('paid')) {
                $order->update([
                    'payment_status_id' => PaymentStatus::idByCode('paid'),
                ]);
                broadcast(new OrderEvent($order, null));
            }

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thanh toán thất bại',
        ]);
    }

    //Lấy ra danh sách sản phẩm
    public function getOrdersForUser(Request $request)
    {
        try {
            $status = $request->get('status'); // query param
            $userId = auth('sanctum')->user()->id;

            $query = Order::with([
                'items',
                'status',
                'paymentStatus',
                'shippingStatus'
            ])->where('user_id', $userId);

            switch ($status) {
                case 'waiting_payment':
                    $query->where('payment_method', 'vnpay')
                        ->whereHas('paymentStatus', fn($q) => $q->where('code', 'unpaid')); // dành cho ai không biết thì fn($q) nó là kiểu function($q)
                    break;

                case 'pending':
                case 'confirmed':
                case 'shipping':
                case 'completed':
                case 'closed':
                case 'cancelled':
                    $query->whereHas('status', fn($q) => $q->where('code', $status));
                    break;

                case 'refund':
                    $query->where(function ($q) {
                        $q->whereHas('status', function ($s) {
                            $s->whereIn('code', ['return_requested', 'return_approved', 'refunded']);
                        })->orWhereHas('refundRequest', function ($r) {
                            $r->whereIn('status', ['pending', 'approved', 'rejected', 'refunded']);
                        });
                    });
                    break;

                default:
                    // Không lọc
                    break;
            }

            $orders = $query->latest()->paginate(10);

            // Nó đỏ nhưng ko lỗi, nó chưa xác định được $orders có phải 1 collection hay không,
            // ai thấy đỏ thì đừng hỏi bạn Hoàng nhé
            $data = $orders->map(function ($order) {
                $items = $order->items;

                return [
                    'code' => $order->code,
                    'status' => $order->status->name ?? '',
                    'status_code' => $order->status->code ?? '',
                    'status_type' => $order->status->type ?? '',
                    'subtitle' => $this->generateOrderSubtitle($order),

                    'payment_status' => $order->paymentStatus->name ?? '',
                    'payment_code' => $order->paymentStatus->code ?? '',

                    'shipping_status' => $order->shippingStatus->name ?? '',
                    'shipping_code' => $order->shippingStatus->code ?? '',

                    'created_at' => $order->created_at->format('d-m-Y H:i'),
                    'final_amount' => $order->final_amount,

                    // Sản phẩm: chỉ lấy 2 cái đầu
                    'products' => $items->take(2)->map(function ($item) {
                        return [
                            'name' => $item->product_name,
                            'image' => $item->image,
                            'price' => $item->price,
                            'quantity' => $item->quantity,
                            'variation' => json_decode($item->variation, true), // vì Tùng chưa cast
                        ];
                    }),

                    // Đếm số sản phẩm còn lại
                    'extra_products_count' => max(0, $items->count() - 2),

                    // Mô tả ngắn sản phẩm
                    'short_product_summary' => $items->pluck('product_name')->take(2)->implode(', ') .
                        ($items->count() > 2 ? ' +' . ($items->count() - 2) . ' sản phẩm khác' : ''),

                    // Các hành động user có thể làm
                    'actions' => OrderActionService::availableActions($order, 'user'),
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
                'message' => 'Lỗi khi lấy danh sách đơn hàng',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    //Tạo map các trạng thái hiển thị
    public function getOrderStatuses()
    {
        $statuses = [
            ['code' => null, 'label' => 'Tất cả '],
            ['code' => 'waiting_payment', 'label' => 'Chờ thanh toán'],
            ['code' => 'pending', 'label' => 'Chờ xác nhận'],
            ['code' => 'confirmed', 'label' => 'Đã xác nhận'],
            ['code' => 'shipping', 'label' => 'Đang giao'],
            ['code' => 'completed', 'label' => 'Đã giao'],
            ['code' => 'closed', 'label' => 'Hoàn thành'],
            ['code' => 'cancelled', 'label' => 'Đã hủy'],
            ['code' => 'refund', 'label' => 'Trả hàng/Hoàn tiền'],
        ];
        return response()->json([
            'message' => 'Success',
            'data' => $statuses,
        ]);
    }
    //
    private function isVerifiedOrder(Request $request, Order $order)
    {
        $user = auth('sanctum')->user();
        if ($user && $order->user_id === $user->id) {
            return true;
        }
        if ($request->hasHeader('X-Order-Access-Token')) {
            try {
                $decrypted = decrypt($request->header('X-Order-Access-Token'));
                if ($decrypted === "verified_order_{$order->code}") {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning("Xác thực token đơn hàng thất bại: " . $e->getMessage());
            }
        }
        return false;
    }
    //
    private function checkOrderAccessOrFail(?Order $order, bool $isVerified, string $action = 'thao tác'): void
    {
        if (!$order) {
            abort(404, 'Không tìm thấy đơn hàng.');
        }

        if (!$isVerified) {
            abort(403, "Bạn không có quyền {$action} đơn hàng này.");
        }
    }


    //Lấy chi tiết
    public function getOrderDetail($code, Request $request)
    {
        try {
            $order = Order::with([
                'items',
                'status',
                'paymentStatus',
                'shippingStatus',
                'shipment',
                'shipment.shippingLogs',
                'refundRequest',
                'statusLogs.fromStatus',
                'statusLogs.toStatus',
            ])->where('code', $code)->first();
            if (!$order) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
            }
            //Check quyền
            $isVerified = $this->isVerifiedOrder($request, $order);
            if (!$isVerified) {
                return response()->json(
                    [
                        'message' => "Success",
                        'data' => [
                            'order_code' => $order->code,
                            'status' => [
                                'code' => $order->status->code,
                                'name' => $order->status->name,
                                'type' => $order->status->type,
                            ],
                            'payment_status' => $order->paymentStatus->name ?? null,
                            'shipping_status' => $order->shippingStatus->name ?? null,
                            'is_verified' => false
                        ],
                        'code' => 200
                    ],
                    200
                );
            }
            //Data
            $data = [
                'order_id' => $order->id,
                'order_code' => $order->code,
                //Thời gian thanh toán đơn hàng hợp lệ để làm count dơ
                'expiried_at' => $order->paymentStatus->code == 'unpaid' ? $order->expiried_at : null,
                // Trạng thái và subtitle
                'status' => [
                    'code' => $order->status->code,
                    'name' => $order->status->name,
                    'type' => $order->status->type,
                ],
                //SHipment
                'shipment' => $order->shipment ? [
                    'shipping_code' => $order->shipment->shipping_code,
                    'carrier' => $order->shipment->carrier,
                    'from_estimate_date' => $order->shipment->from_estimate_date,
                    'to_estimate_date' => $order->shipment->to_estimate_date,
                ] : null,
                //Subtitle
                'subtitle' => $this->generateOrderSubtitle($order),
                // Thanh toán + vận chuyển
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
                // Sản phẩm
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image' => $item->image,
                        'variation' => $item->variation,
                        'review' => $item->productReview
                    ];
                }),
                // Lịch sử giao hàng
                'shipping_logs' => $order->shipment?->shippingLogs->map(function ($log) {
                    return [
                        'status' => $log->ghn_status,
                        'location' => $log->location,
                        'note' => $log->note,
                        'created_at' => $log->timestamp,
                    ];
                }),
                // Yêu cầu hoàn hàng (nếu có)
                'refund_request' => $order->refundRequest
                    ? new RefundRequestResource($order->refundRequest)
                    : null,
                // Timeline trạng thái
                'status_timelines' => $order->statusLogs->map(function ($log) {
                    return [
                        'from' => $log->fromStatus->name ?? null,
                        'to' => $log->toStatus->name ?? null,
                        'changed_at' => $log->changed_at,
                    ];
                }),
                // Các hành động khả dụng cho user
                'actions' => OrderActionService::availableActions($order, 'user'),
                //Đã xác thực thành công hay chưa
                'is_verified' => true
            ];

            return response()->json([
                'message' => 'Lấy chi tiết đơn hàng thành công',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Không thể lấy đơn hàng',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    //Hủy đơn hàng
    public function cancel(Request $request, $code)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:1000'
        ]);

        $order = Order::with('shipment')->where('code', $code)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        if (!$this->isVerifiedOrder($request, $order)) {
            return response()->json(['message' => 'Bạn không có quyền hủy đơn hàng này'], 403);
        }

        if (!in_array($order->status->code, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Không thể hủy đơn hàng ở trạng thái hiện tại'], 400);
        }

        $result = app(CancelOrderService::class)
            ->handle($order, $validated['cancel_reason'], $request->ip());

        return response()->json(['message' => $result['message']], $result['success'] ? 200 : 500);
    }
    //Yêu cầu hoàn tiền
    public function requestRefund(Request $request, $code)
    {
        $request->validate([
            'reason' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'url',
            'bank_name' => 'required|string|max:255',
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
        ]);


        $order = Order::where('code', $code)->firstOrFail();
        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng.'
            ], 404);
        }
        $isVerified = $this->isVerifiedOrder($request, $order);
        if (!$isVerified) {
            return response()->json([
                'message' => 'Bạn không có quyền yêu cầu hoàn tiền với đơn hàng này'
            ], 404);
        }
        if (!in_array($order->status->code, ['completed'])) {
            return response()->json(['message' => 'Không thể yêu cầu hoàn tiền ở trạng thái hiện tại'], 400);
        }
        //
        if (RefundRequest::where('order_id', $order->id)->exists()) {
            return response()->json(['message' => 'Đơn hàng này đã có yêu cầu hoàn rồi'], 400);
        }

        DB::beginTransaction();

        try {
            RefundRequest::create([
                'order_id' => $order->id,
                'type' => 'return_after_received',
                'reason' => $request->reason,
                'amount' => $order->final_amount,
                'status' => 'pending',
                'images' => $request->images ?? [],
                'bank_name' => $request->bank_name,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
            ]);


            $fromStatusId = $order->order_status_id;
            $toStatusId = OrderStatus::idByCode('return_requested');

            $order->update(['order_status_id' => $toStatusId]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatusId,
                'changed_by' => 'user',
                'changed_at' => now(),
            ]);
            SendMailOrderCancelled::dispatch($order);
            DB::commit();
            return response()->json(['message' => 'Đã gửi yêu cầu hoàn hàng thành công'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Refund Request Error: ' . $th->getMessage());
            return response()->json(['message' => 'Lỗi khi gửi yêu cầu hoàn hàng'], 500);
        }
    }
    //Hoàn thành đơn hàng

    //Xác nhận đơn hàng
    public function closeOrder($code, Request $request)
    {
        $order = Order::with('status')->where('code', $code)->firstOrFail();
        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng.'
            ], 404);
        }
        $isVerified = $this->isVerifiedOrder($request, $order);
        if (!$isVerified) {
            return response()->json([
                'message' => 'Bạn không có quyền hủy đơn hàng này'
            ], 404);
        }
        // Kiểm tra trạng thái hiện tại có thể chuyển sang 'closed' không
        if (!OrderStatusFlowService::canChange($order, 'closed')) {
            return response()->json([
                'message' => 'Không thể hoàn thành đơn hàng',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $changed = OrderStatusFlowService::change($order, 'closed', 'admin');

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
    // Thanh toán lại
    public function retryPaymentVnpay($orderCode, Request $request)
    {
        $order = Order::where('code', $orderCode)->first();
        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng.'
            ], 404);
        }
        $isVerified = $this->isVerifiedOrder($request, $order);
        if (!$isVerified) {
            return response()->json([
                'message' => 'Bạn không có quyền hủy đơn hàng này'
            ], 404);
        }

        if (!$order->payment_method == 'vnpay') {
            return response()->json(['message' => 'Đơn hàng không sử dụng phương thức thanh toán VNPAY'], 400);
        }

        if ($order->payment_status_id === PaymentStatus::idByCode('paid')) {
            return response()->json(['message' => 'Đơn hàng đã được thanh toán thành công'], 400);
        }
        return response()->json([
            'message' => 'Thành công',
            'url' => $order->payment_url,
            'code' => 200
        ], 201);
    }

    //Đánh giá
    public function reviewProduct($code, Request $request)
    {
        try {
            //code...
            $request->validate([
                'order_item_id' => 'required|integer|exists:order_items,id',
                'product_id' => 'required|integer|exists:products,id',
                'rating' => 'required|integer|min:1|max:5',
                'content' => 'required|string|min:5|max:3000',
                'images' => 'nullable|array',
                'images.*' => 'string|url',
            ], [
                'order_item_id.required' => 'Thiếu thông tin sản phẩm trong đơn hàng.',
                'order_item_id.integer' => 'Mã sản phẩm không hợp lệ',
                'order_item_id.exists' => 'Sản phẩm trong đơn hàng không tồn tại',

                'product_id.required' => 'Thiếu mã sản phẩm',
                'product_id.integer' => 'Mã sản phẩm không hợp lệ',
                'product_id.exists' => 'Sản phẩm không tồn tại',

                'rating.required' => 'Vui lòng chọn số sao đánh giá',
                'rating.integer' => 'Số sao phải là số nguyên',
                'rating.min' => 'Số sao tối thiểu là 1',
                'rating.max' => 'Số sao tối đa là 5',

                'content.required' => 'Vui lòng nhập nội dung đánh giá',
                'content.string' => 'Nội dung đánh giá không hợp lệ',
                'content.min' => 'Nội dung đánh giá quá ngắn (tối thiểu 5 ký tự)',
                'content.max' => 'Nội dung đánh giá không được vượt quá 3000 ký tự',

                'images.array' => 'Danh sách ảnh phải ở dạng mảng',
                'images.*.string' => 'Ảnh phải ở dạng đường dẫn hợp lệ',
                'images.*.url' => 'Ảnh phải là một đường dẫn URL hợp lệ',
            ]);

            $order = Order::with(['items'])->where('code', $code)->first();

            if (!$order) {
                return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
            }

            if (!$this->isVerifiedOrder($request, $order)) {
                return response()->json(['message' => 'Bạn không có quyền đánh giá đơn hàng này'], 403);
            }

            if (!in_array($order->status->code, ['completed', 'closed'])) {
                return response()->json(['message' => 'Bạn không thể đánh giá khi đơn hàng ở trạng thái này'], 400);
            }

            // Tìm item trong đơn
            $orderItem = $order->items->firstWhere('id', $request->order_item_id);
            if (!$orderItem) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong đơn hàng', 'order_item_id' => $request->order_item_id], 404);
            }

            // Kiểm tra đã đánh giá chưa
            $existing = ModelsComment::where('order_item_id', $orderItem->id)->first();
            if ($existing) {
                if ($existing->is_updated) {
                    return response()->json(['message' => 'Bạn đã chỉnh sửa đánh giá, không thể cập nhật thêm.'], 403);
                }

                // Cho phép chỉnh sửa 1 lần

                $existing->update([
                    'rating' => $request->rating,
                    'content' => $request->content,
                    'images' => $request->images,
                    'is_updated' => true,
                ]);
                $this->updateProductAverageRating($orderItem->product_id);
                return response()->json(['message' => 'Đã cập nhật đánh giá']);
            }

            // Tạo đánh giá mới
            $data = [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'order_item_id' => $orderItem->id,
                'product_id' => $request->product_id,
                'rating' => $request->rating,
                'content' => $request->content,
                'images' => $request->images,
                'customer_name' => $order->user_id === null ? $order->o_name : null,
                'customer_email' => $order->user_id === null ? $order->o_email : null,
                'is_updated' => false,
            ];

            ModelsComment::create($data);
            $this->updateProductAverageRating($orderItem->product_id);
            return response()->json(['message' => 'Đánh giá thành công'], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi gửi đánh giá',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    // tÍNH TRUNG BÌNH VÀ UPDATE SỐ SAO SẢN PHẨM
    private function updateProductAverageRating($productId)
    {
        // Tính điểm trung bình từ tất cả đánh giá được duyệt
        $avgRating = ModelsComment::where('product_id', $productId)
            ->avg('rating');

        // Làm tròn đến 1 chữ số thập phân
        $avgRating = round($avgRating, 1);

        // Cập nhật vào bảng products
        DB::table('products')
            ->where('id', $productId)
            ->update(['avg_rating' => $avgRating]);
    }

    //Xác thực đơn
    //Gưi mail
    public function sendVerifyOrderCode(Request $request)
    {
        $request->validate([
            'order_code' => 'required',
            'email' => 'required|email',
        ]);

        $order = Order::where('code', $request->order_code)->first();

        if (!$order || $order->o_mail !== $request->email) {
            return response()->json(['message' => 'Thông tin đơn hàng không khớp!', 'code' => 404], 404);
        }

        $cacheKey = "verify_order_{$order->code}_otp";
        $limitKey = "verify_order_{$order->code}_limit";

        // Kiểm tra giới hạn gửi trong 1 phút
        if (cache()->has($limitKey)) {
            return response()->json(['message' => 'Vui lòng chờ 1 phút để gửi lại mã', 'code' => 429], 429);
        }

        // Tạo mã OTP mới
        $otp = mt_rand(100000, 999999);

        // Ghi đè cache mã OTP (mã cũ bị xóa ngay lập tức)
        cache()->put($cacheKey, $otp, now()->addMinutes(5));

        // Lưu giới hạn gửi OTP (chỉ cho phép gửi lại sau 1 phút)
        cache()->put($limitKey, true, now()->addSeconds(10));

        // Gọi job gửi mail
        SendVerifyGuestOrderJob::dispatch($order->o_mail, $order->code, $otp);

        return response()->json(['message' => 'Đã gửi mã xác thực về email. Vui lòng kiểm tra hộp thư', 'code' => 200], 200);
    }

    //Xác thức
    public function verifyOrderCode(Request $request)
    {
        $request->validate([
            'order_code' => 'required',
            'otp' => 'required|digits:6',
        ]);

        $cacheKey = "verify_order_{$request->order_code}_otp"; // Đồng bộ với hàm gửi OTP
        $cachedOtp = cache()->get($cacheKey);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['message' => 'Mã xác thực không đúng hoặc đã hết hạn!', 'code' => 400], 400);
        }

        // Xóa OTP khỏi cache ngay sau khi xác thực thành công
        cache()->forget($cacheKey);

        // Tạo token xác thực đơn hàng
        $verifyToken = encrypt("verified_order_{$request->order_code}");

        return response()->json([
            'message' => 'Xác thực thành công!',
            'code' => 200,
            'token' => $verifyToken,
        ]);
    }
}
