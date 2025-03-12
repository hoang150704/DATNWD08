<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Requests\User\OrderClientRequest;
use App\Jobs\SendMailSuccessOrderJob;
use App\Models\Order;
use App\Models\OrderHistory;
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
            $order = Order::create([
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
                'stt_payment' => 1,
                'stt_track' => 1,
                // Lưu thông tin thời gian giao hàng nếu có
                // 'from_estimate_date' => $validatedData['time']['from_estimate_date'] ?? null,
                // 'to_estimate_date' => $validatedData['time']['to_estimate_date'] ?? null,
            ]);
    
            if (!$order) {
                DB::rollBack();
                return response()->json(['message' => 'Tạo đơn hàng thất bại!'], 500);
            }
            // Lưu lịch sử trạng thái
            $orderHistoryTrack = OrderHistory::create(
                [
                    'order_id'=>$order->id,
                    'type'=>'paid',
                    'status'=>1
                ],
                [
                    'order_id'=>$order->id,
                    'type'=>'tracking',
                    'status'=>1
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
                $variation =  $variant->getFormattedVariation();
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
            //
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
    

    public function callbackPayment(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRET'); // Khóa bảo mật
        $data = $request->all();

        // Tạo chuỗi kiểm tra chữ ký
        $vnp_SecureHash = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        ksort($data);
        $hashData = urldecode(http_build_query($data));
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash === $vnp_SecureHash) {
            if ($request->get('vnp_ResponseCode') === '00') {
                // Giao dịch thành công, cập nhật trạng thái đơn hàng
                $order = Order::where('code', $request->get('vnp_TxnRef'))->first();
                if ($order) {
                    $order->update(['stt_payment' => 2]);
                    $orderHistoryTrack = OrderHistory::create(
                        [
                            'order_id'=>$order->id,
                            'type'=>'paid',
                            'status'=>2
                        ]
                    );
                    return response()->json(['success' => true, 'message' => 'Thanh toán thành công']);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Thanh toán thất bại']);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Chữ ký không hợp lệ']);
        }
    }
}
