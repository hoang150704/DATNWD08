<?php
namespace App\Services\Orders\Client;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ShippingStatusEnum;
use App\Events\OrderEvent;
use App\Jobs\SendMailOrderCancelled;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentStatus;
use App\Models\RefundRequest;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
use App\Models\Transaction;
use App\Models\OrderStatusLog;
use App\Services\GhnApiService;
use App\Services\PaymentVnpay;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelOrderService
{
    protected $paymentMedthodVnpay = 'vnpay';
    protected $paymentVnpay;
    protected $ghn;

    public function __construct(PaymentVnpay $paymentVnpay, GhnApiService $ghn)
    {
        $this->paymentVnpay = $paymentVnpay;
        $this->ghn = $ghn;
    }

    public function handle(Order $order, string $reason, string $ip, string $cancelBy = 'user'): array
    {
        DB::beginTransaction();
        try {
            $fromStatusId = $this->updateOrderStatuses($order, $reason, $cancelBy);
            $this->logStatusChange($order, $cancelBy, $fromStatusId);

            if ($this->checkRefund($order)) {
                $this->createRefundAndTransactions($order, $reason, $ip);
            }

            if ($this->checkCancelShipment($order)) {
                $this->cancelShipmentViaGhn($order, $reason);
            }
            SendMailOrderCancelled::dispatch($order);
            DB::commit();
            return ['success' => true, 'message' => 'Đơn hàng đã được huỷ'];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Cancel Order Error: ' . $th->getMessage());
            return ['success' => false, 'message' => 'Lỗi khi huỷ đơn', 'error' => $th->getMessage()];
        }
    }

    private function updateOrderStatuses(Order $order, string $reason, string $cancelBy)
    {
        $fromStatusId = $order->order_status_id;
        if ($order->payment_method !== $this->paymentMedthodVnpay) {
            $order->payment_status_id = PaymentStatus::idByCode(PaymentStatusEnum::CANCELLED);
        }

        $order->update([
            'order_status_id' => OrderStatus::idByCode(OrderStatusEnum::CANCELLED),
            'shipping_status_id' => ShippingStatus::idByCode(ShippingStatusEnum::CANCELLED),
            'cancel_reason' => $reason,
            'cancel_by' => $cancelBy,
            'cancelled_at' => now(),
        ]);

        broadcast(new OrderEvent($order, null));
        return $fromStatusId;
    }

    private function logStatusChange(Order $order, string $cancelBy, $fromStatusId): void
    {
        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => OrderStatus::idByCode(OrderStatusEnum::CANCELLED),
            'changed_by' => $cancelBy,
            'changed_at' => now(),
        ]);
    }

    private function checkRefund(Order $order): bool
    {
        return $order->payment_method === $this->paymentMedthodVnpay && $order->paymentStatus->code === PaymentStatusEnum::PAID;
    }

    private function createRefundAndTransactions(Order $order, string $reason, string $ip): void
    {
        RefundRequest::create([
            'order_id' => $order->id,
            'type' => 'not_received',
            'reason' => $reason,
            'amount' => $order->final_amount,
            'status' => 'approved',
            'approved_by' => 'system',
            'approved_at' => now(),
        ]);

        $paymentTransaction = $order->transactions()
            ->where('method', $this->paymentMedthodVnpay)
            ->where('type', 'payment')
            ->where('status', 'success')
            ->latest()
            ->first();

        $this->createPendingRefundTransaction($order);

        $refundData = [
            'txn_ref' => $paymentTransaction->transaction_code,
            'amount' => $paymentTransaction->amount,
            'txn_date' => optional($paymentTransaction->vnp_pay_date)->format('YmdHis'),
            'txn_no' => $paymentTransaction->vnp_transaction_no,
            'type' => '02',
            'create_by' => 'system',
            'ip' => $ip,
            'order_info' => 'Khách huỷ đơn hàng chưa nhận'
        ];

        $result = $this->paymentVnpay->refundTransaction($refundData);

        Transaction::create([
            'order_id' => $order->id,
            'method' => $this->paymentMedthodVnpay,
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

        if ($result['response_data']['vnp_ResponseCode'] === '00') {
            $order->update(['payment_status_id' => PaymentStatus::idByCode('refunded')]);
        }
    }

    private function createPendingRefundTransaction(Order $order): void
    {
        Transaction::create([
            'order_id' => $order->id,
            'method' => $this->paymentMedthodVnpay,
            'type' => 'refund',
            'amount' => $order->final_amount,
            'status' => 'pending',
            'note' => 'Yêu cầu hoàn tiền khi khách huỷ đơn chưa nhận hàng',
            'created_at' => now()
        ]);
    }

    private function checkCancelShipment(Order $order): bool
    {
        return $order->shipment && !in_array($order->shippingStatus->code, [ShippingStatusEnum::NOT_CREATED, ShippingStatusEnum::CANCELLED]);
    }

    private function cancelShipmentViaGhn(Order $order, string $reason): void
    {
        $res = $this->ghn->cancelOrder([$order->shipment->shipping_code]);

        if ($res['code'] === 200 && !empty($res['data'])) {
            foreach ($res['data'] as $item) {
                ShippingLog::create([
                    'shipment_id' => $order->shipment->id,
                    'ghn_status' => 'cancel',
                    'mapped_status_id' => ShippingStatus::idByCode(ShippingStatusEnum::CANCELLED),
                    'location' => null,
                    'note' => $item['message'] ?? 'Đã huỷ qua GHN',
                    'timestamp' => now(),
                ]);

                $order->shipment->update([
                    'shipping_status_id' => ShippingStatus::idByCode(ShippingStatusEnum::CANCELLED),
                    'cancel_reason' => $reason
                ]);
            }
        } else {
            Log::error('GHN Cancel Failed', [
                'order_code' => $order->shipment->shipping_code,
                'message' => $res['message'] ?? 'Không rõ lỗi'
            ]);
        }
    }
}
