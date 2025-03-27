<?php

namespace App\Listeners;

use App\Events\OrderEvent;
use App\Events\VoucherEvent;
use App\Models\Notification;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SaveOrderNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderEvent $event): void
    {
        Notification::create([
            'title' => 'Có đơn hàng mới: ' . $event->order->code,
            'message' => 'Khách hàng ' . $event->order->o_name . ' vừa đặt đơn hàng trị giá ' . $event->order->final_amount . 'đ' . ' bằng phương thức ' . $event->order->payment_method,
            'created_at' => $event->order->created_at
        ]);
        if ($event->voucher) {
            if ($event->voucher->usage_limit == 0) {
                Notification::create([
                    'title' => 'Voucher hết lượt dùng: ' . $event->voucher->code,
                    'message' => 'Voucher ' . $event->voucher->code . ' đã hết lượt dùng',
                    'created_at' => $event->order->created_at
                ]);
            }
        }
    }
}
