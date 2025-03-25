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
use App\Models\OrderHistory;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use App\Models\PaymentStatus;
use App\Models\ProductVariation;
use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
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

            // Sau khi hoàn tất việc tạo đơn hàng và trước khi commit transaction

            // Lưu lịch sử trạng thái
            // $orderHistoryTrack = OrderHistory::insert(
            //     [
            //         'order_id' => $order->id,
            //         'from_status_id' => null,
            //         'to_status_id' => 1,
            //         'changed_by' => 'system',
            //         'changed_at' => now(),
            //     ]
            // );
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


    public function callbackPayment(Request $request)
    {

        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_SecureHash = $request['vnp_SecureHash'];
        $data = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $data[$key] = $value;
            }
        }

        unset($data['vnp_SecureHash']);
        ksort($data);
        $i = 0;
        $hashData = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash === $vnp_SecureHash) {
            if ($request['vnp_ResponseCode'] == '00') {
                // Giao dịch thành công, cập nhật trạng thái đơn hàng
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Thanh toán thất bại',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sai chữ kí',
            ]);
        }
    }
}
