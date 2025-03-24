<?php

namespace App\Listeners;

use App\Events\OrderEvent;
use App\Models\Notification;
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
        if ($event->order->type == 1) {
            Notification::create([
                'title' => 'Có đơn hàng mới: ' . $event->order->code,
                'message' => json_encode([
                    'o_name' => $event->order->o_name,
                    'final_amount' => $event->order->final_amount,
                    'payment_method' => $event->order->payment_method,
                    'created_at' => $event->order->created_at->format('d/m/Y H:i:s')
                ])
            ]);
        }
    }
}
