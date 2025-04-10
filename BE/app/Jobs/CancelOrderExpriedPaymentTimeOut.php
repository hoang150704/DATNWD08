<?php

namespace App\Jobs;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ShippingStatusEnum;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentStatus;
use App\Models\ShippingStatus;
use App\Models\OrderStatusLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelOrderExpriedPaymentTimeOut implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Kiểm tra trạng thái đơn hàng hiện tại
            $paymentStatusId = PaymentStatus::idByCode(PaymentStatusEnum::PAID);
            $order = Order::find($this->order->id);

            // Nếu đơn hàng không tồn tại hoặc đã thanh toán thì không làm gì
            if (!$order || $order->payment_status_id === $paymentStatusId) {
                return;
            }

            DB::beginTransaction();
            try {
                $fromStatusId = $order->order_status_id;
                $cancelStatusId = OrderStatus::idByCode(OrderStatusEnum::CANCELLED);
                $cancelStatusShipId = ShippingStatus::idByCode(ShippingStatusEnum::CANCELLED);

                // Xử lý trạng thái thanh toán
                $paymentStatus = PaymentStatus::idByCode('cancelled');

                $order->payment_status_id = $paymentStatus;

                // Cập nhật trạng thái đơn hàng
                $order->update([
                    'shipping_status_id' => $cancelStatusShipId,
                    'order_status_id' => $cancelStatusId,
                    'cancel_reason' => 'Hết thời gian thanh toán',
                    'cancel_by' => 'system',
                    'cancelled_at' => now()
                ]);

                // Ghi log trạng thái
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $cancelStatusId,
                    'changed_by' => 'system',
                    'changed_at' => now(),
                ]);
                SendMailOrderCancelled::dispatch($order);
                DB::commit();
                Log::info('Đã hủy đơn hàng #' . $order->id . ' do hết thời gian thanh toán');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Lỗi khi hủy đơn hàng #' . $order->id . ': ' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Lỗi trong CancelOrderExpriedPaymentTimeOut job: ' . $e->getMessage());
            throw $e;
        }
    }
}
