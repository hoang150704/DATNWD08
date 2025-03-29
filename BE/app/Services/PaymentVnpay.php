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
        $vnp_OrderInfo = "Thanh toán hóa đơn " . $order->order_code;
        $vnp_OrderType = "100002";
        $vnp_Amount = ($order->total_amount - $order->discount_amount) * 100;
        $vnp_Locale = "VN";
        $vnp_IpAddr = request()->ip();

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => 'other',
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
        $vnp_TransactionNo = (string) ($data['txn_no'] ?? "");
        $vnp_TransactionDate = $data['txn_date'];
        $vnp_CreateBy = $data['create_by'] ?? 'admin';
        $vnp_CreateDate = now()->format('YmdHis');
        $vnp_IpAddr = $data['ip'] ?? $this->vnp_IpAddr;
        $vnp_OrderInfo = $data['order_info'] ?? "Hoàn tiền cho giao dịch $vnp_TxnRef";
    
        // Tạo chuỗi hash theo thứ tự yêu cầu của VNPAY
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
    
        $vnp_SecureHash = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);
    
        $requestData = [
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
    
        // Gọi API hoàn tiền
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://sandbox.vnpayment.vn/merchant_webapi/api/transaction", $requestData);
    
        $responseData = $response->json();
    
        // Tạo chuỗi xác thực chữ ký từ VNPAY
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
    
        $verifyHash = hash_hmac('sha512', $verifyData, $this->vnp_HashSecret);
        $isValid = $verifyHash === ($responseData['vnp_SecureHash'] ?? '');
    
        $error = null;
        if (!$isValid) {
            $error = 'Chữ ký xác thực không hợp lệ.';
        } elseif (($responseData['vnp_ResponseCode'] ?? '') !== '00') {
            $error = 'Lỗi hoàn tiền từ VNPAY: ' . ($responseData['vnp_Message'] ?? 'Không rõ lỗi');
        }
    
        return [
            'request_data' => $requestData,
            'response_data' => $responseData,
            ' $hashData '=> $hashData ,
            'valid_signature' => $isValid,
            'verifyData' =>   $verifyData,
            'error' => $error,
            'success' => $isValid && ($responseData['vnp_ResponseCode'] ?? '') === '00',
        ];
    }
    
    public function mapVnpResponseCode($code)
    {
        return match ($code) {
            '00' => 'Yêu cầu thành công',
            '02' => 'Mã định danh kết nối không hợp lệ (kiểm tra lại TmnCode)',
            '03' => 'Dữ liệu gửi sang không đúng định dạng',
            '91' => 'Không tìm thấy giao dịch yêu cầu hoàn trả',
            '94' => 'Giao dịch đã được gửi yêu cầu hoàn tiền trước đó. Yêu cầu này VNPAY đang xử lý',
            '95' => 'Giao dịch này không thành công bên VNPAY. VNPAY từ chối xử lý yêu cầu',
            '97' => 'Checksum không hợp lệ',
            '99' => 'Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê)',
            default => 'Giao dịch thất bại (mã: ' . $code . ')',
        };
    }    
}
