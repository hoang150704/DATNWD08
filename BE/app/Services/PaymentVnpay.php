<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentVnpay
{
    protected $vnp_TmnCode;
    protected $vnp_HashSecret;
    protected $vnp_Url;
    protected $vnp_ReturnUrl;
    protected $vnp_IpAddr;

    public function __construct()
    {
        $this->vnp_TmnCode = env('VNP_TMN_CODE');
        $this->vnp_HashSecret = env('VNP_HASH_SECRET');
        $this->vnp_Url = env('VNP_URL');
        $this->vnp_ReturnUrl = env('VNP_RETURN_URL');
        $this->vnp_IpAddr = request()->ip();
    }

    public function createPaymentUrl($order)
    {
        $vnp_TxnRef = $order->code;
        $vnp_OrderInfo = "Thanh toán hóa đơn " . $order->code;
        $vnp_OrderType = "other";
        $vnp_Amount = ($order->total_amount - $order->discount_amount) * 100;
        $vnp_Locale = "VN";

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $this->vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $this->vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef
        ];

        ksort($inputData);
        $query = "";
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            $hashdata .= ($hashdata ? '&' : '') . urlencode($key) . "=" . urlencode($value);
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        $query .= 'vnp_SecureHash=' . $vnpSecureHash;

        return $this->vnp_Url . "?" . $query;
    }

    public function refund($order, $transaction, $amount, $reason = 'Yêu cầu hoàn tiền', $isPartial = false)
    {
        $now = Carbon::now();

        $data = [
            "vnp_RequestId" => Str::uuid()->toString(),
            "vnp_Version" => "2.1.0",
            "vnp_Command" => "refund",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_TransactionType" => $isPartial ? "03" : "02", // 03: hoàn 1 phần, 02: hoàn toàn
            "vnp_TxnRef" => $transaction->transaction_code,
            "vnp_Amount" => $amount * 100,
            "vnp_TransactionNo" => $transaction->vnp_transaction_no,
            "vnp_TransactionDate" => $transaction->vnp_pay_date->format('YmdHis'),
            "vnp_CreateBy" => auth('sanctum')->user()->name ?? 'admin',
            "vnp_CreateDate" => $now->format('YmdHis'),
            "vnp_IpAddr" => $this->vnp_IpAddr,
            "vnp_OrderInfo" => $reason
        ];

        $hashData = implode('|', [
            $data['vnp_RequestId'],
            $data['vnp_Version'],
            $data['vnp_Command'],
            $data['vnp_TmnCode'],
            $data['vnp_TransactionType'],
            $data['vnp_TxnRef'],
            $data['vnp_Amount'],
            $data['vnp_TransactionNo'],
            $data['vnp_TransactionDate'],
            $data['vnp_CreateBy'],
            $data['vnp_CreateDate'],
            $data['vnp_IpAddr'],
            $data['vnp_OrderInfo']
        ]);

        $data['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post('https://sandbox.vnpayment.vn/merchant_webapi/api/transaction', $data);

            return $response->json();
        } catch (\Throwable $th) {
            Log::error('Lỗi gửi yêu cầu hoàn tiền VNPAY: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi kết nối đến VNPAY',
                'error' => $th->getMessage()
            ];
        }
    }
}
