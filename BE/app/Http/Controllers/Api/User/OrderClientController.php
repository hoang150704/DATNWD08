<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\User\OrderClientRequest;
use App\Jobs\SendMailSuccessOrderJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PaymentVnpay;
use Illuminate\Support\Str;

class OrderClientController extends Controller
{
    protected $paymentVnpay;

    public function __construct(PaymentVnpay $paymentVnpay)
    {
        $this->paymentVnpay = $paymentVnpay;
    }
    //
    private function generateOrderCode()
    {
        $prefix = 'ORD';
        $timestamp = time();
        $randomString = strtoupper(substr(md5(uniqid(mt_rand(), true)), 1, 5));

        return $prefix . $timestamp . $randomString;
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
    
            // Kiểm tra người dùng đăng nhập
            $userId = auth()->check() ? auth()->id() : null;
    
            // Tạo đơn hàng
            $order = Order::create([
                'user_id' => $userId, // Lưu ID người dùng nếu có đăng nhập
                'code' => 'DH_' . Str::uuid(),
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
                'stt_payment' => 1,
                'stt_track' => 1
            ]);
    
            if (!$order) {
                DB::rollBack();
                return response()->json(['message' => 'Tạo đơn hàng thất bại!'], 500);
            }
    
            $orderItems = [];
    
            foreach ($validatedData['products'] as $product) {
                $variant = ProductVariation::find($product['variation_id']);
                if (!$variant) {
                    DB::rollBack();
                    return response()->json(['message' => 'Sản phẩm không tồn tại!'], 400);
                }
    
                // Kiểm tra tồn kho trước khi trừ
                if ($variant->stock_quantity < $product['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Sản phẩm "' . $product['name'] . '" không đủ hàng tồn kho!'
                    ], 400);
                }
    
                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'weight' => $product['weight'],
                    'image' => $product['image'],
                    'variation' => json_encode($product['variation']),
                    'product_name' => strip_tags($product['name']),
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                ];
                $variant->decrement('stock_quantity', (int) $product['quantity']);
            }
    
            // Thêm nhiều sản phẩm vào bảng `order_items`
            OrderItem::insert($orderItems);
    
            // Gửi email xác nhận đơn hàng (background job)
            SendMailSuccessOrderJob::dispatch($order);
    
            DB::commit();
    
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
                'order_code' => $order->code
            ], 201);
    
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi trong quá trình tạo đơn hàng!',
                'errors' => $th->getMessage(),
            ], 500);
        }
    }
    
    public function callbackPayment(){

    }
}
