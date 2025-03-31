<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredVnpayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-expired-vnpay-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::where('payment_method', 'vnpay')
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'unpaid'))
            ->where('created_at', '<=', now()->subHour())
            ->where('order_status_id', OrderStatus::idByCode('pending'))
            ->get();
    
        foreach ($expiredOrders as $order) {
            $order->update([
                'order_status_id' => OrderStatus::idByCode('cancelled'),
                'cancel_reason' => 'Hết thời gian thanh toán',
                'cancel_by' => 'system',
                'cancelled_at' => now(),
            ]);
    
            // Optionally: log lại
            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status_id' => OrderStatus::idByCode('pending'),
                'to_status_id' => OrderStatus::idByCode('cancelled'),
                'changed_by' => 'system',
                'changed_at' => now(),
            ]);
    
            Log::info("Đã huỷ đơn quá hạn thanh toán: {$order->code}");
        }
    }
    
}
