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
    protected $vnp_Refund_Url;
    protected $vnp_ReturnUrl;
    protected $vnp_IpAddr;

    public function __construct()
    {
        $this->vnp_TmnCode = env('VNP_TMN_CODE');
        $this->vnp_HashSecret = env('VNP_HASH_SECRET');
        $this->vnp_Url = env('VNP_URL');
        $this->vnp_Refund_Url = env('VNP_REFUND_URL');
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

    public function refundTransaction(array $data)
    {
        $vnp_RequestId = uniqid();
        $vnp_Version = "2.1.0";
        $vnp_Command = "refund";
        $vnp_TransactionType = $data['type'] ?? '02'; // 02: hoàn toàn phần, 03: một phần
        $vnp_TxnRef = $data['txn_ref'];
        $vnp_Amount = $data['amount'] * 100;
        $vnp_TransactionNo = $data['txn_no'] ?? "";
        $vnp_TransactionDate = $data['txn_date'];
        $vnp_CreateBy = $data['create_by'] ?? 'admin';
        $vnp_CreateDate = now()->format('YmdHis');
        $vnp_IpAddr = $data['ip'] ?? $this->vnp_IpAddr;
        $vnp_OrderInfo = $data['order_info'] ?? "Hoàn tiền cho giao dịch $vnp_TxnRef";

        $hashData = implode('|', [
            $vnp_RequestId,
            $vnp_Version,
            $vnp_Command,
            $this->vnp_TmnCode,
            $vnp_TransactionType,
            $vnp_TxnRef,
            $vnp_Amount,
            $vnp_TransactionNo,
            $vnp_TransactionDate,
            $vnp_CreateBy,
            $vnp_CreateDate,
            $vnp_IpAddr,
            $vnp_OrderInfo
        ]);

        $vnp_SecureHash = hash_hmac('sha256', $hashData, $this->vnp_HashSecret);

        $data = [
            "vnp_RequestId" => $vnp_RequestId,
            "vnp_Version" => $vnp_Version,
            "vnp_Command" => $vnp_Command,
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_TransactionType" => $vnp_TransactionType,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_Amount" => $vnp_Amount,
            "vnp_TransactionNo" => $vnp_TransactionNo,
            "vnp_TransactionDate" => $vnp_TransactionDate,
            "vnp_CreateBy" => $vnp_CreateBy,
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_SecureHash" => $vnp_SecureHash,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://sandbox.vnpayment.vn/merchant_webapi/api/transaction", $data);

        $responseData = $response->json();

        $verifyData = implode('|', [
            $responseData['vnp_ResponseId'] ?? '',
            $responseData['vnp_Command'] ?? '',
            $responseData['vnp_ResponseCode'] ?? '',
            $responseData['vnp_Message'] ?? '',
            $responseData['vnp_TmnCode'] ?? '',
            $responseData['vnp_TxnRef'] ?? '',
            $responseData['vnp_Amount'] ?? '',
            $responseData['vnp_BankCode'] ?? '',
            $responseData['vnp_PayDate'] ?? '',
            $responseData['vnp_TransactionNo'] ?? '',
            $responseData['vnp_TransactionType'] ?? '',
            $responseData['vnp_TransactionStatus'] ?? '',
            $responseData['vnp_OrderInfo'] ?? ''
        ]);

        $verifyHash = hash_hmac('sha256', $verifyData, $this->vnp_HashSecret);
        $isValid = $verifyHash === ($responseData['vnp_SecureHash'] ?? '');

        $error = null;
        if (!$isValid) {
            $error = 'Chữ ký xác thực không hợp lệ.';
        } elseif (($responseData['vnp_ResponseCode'] ?? '') !== '00') {
            $error = 'Lỗi hoàn tiền từ VNPAY: ' . ($responseData['vnp_Message'] ?? 'Không rõ lỗi');
        }

        return [
            'request_data' => $data,
            'response_data' => $responseData,
            'valid_signature' => $isValid,
            'error' => $error,
        ];
    }
    public function mapVnpResponseCode($code)
    {
        return match ($code) {
            '00' => 'Giao dịch thành công',
            '07' => 'Giao dịch nghi ngờ (liên quan đến gian lận, bất thường)',
            '09' => 'Chưa đăng ký Internet Banking',
            '10' => 'Xác thực sai quá 3 lần',
            '11' => 'Hết hạn chờ thanh toán',
            '12' => 'Tài khoản bị khóa',
            '13' => 'Sai OTP',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Không đủ số dư',
            '65' => 'Vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng bảo trì',
            '79' => 'Sai mật khẩu quá số lần quy định',
            '99' => 'Lỗi không xác định từ VNPAY',
            default => 'Giao dịch thất bại (mã: ' . $code . ')',
        };
    }
}
