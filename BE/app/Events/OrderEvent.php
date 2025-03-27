<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $order;
    public $voucher;
    public $connection = 'sync';

    public function __construct($order, $voucher)
    {
        $this->order = $order;
        $this->voucher = $voucher;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order-send';
    }

    public function broadcastWith()
    {
        return [
            'order_code' => $this->order->code,
            'o_name' => $this->order->o_name,
            'final_amount' => $this->order->final_amount,
            'payment_method' => $this->order->payment_method,
            'created_at' => $this->order->created_at,
            'voucher' => $this->voucher ? [
                'code' => $this->voucher->code,
                'usage_limit' => $this->voucher->usage_limit,
                'expiry_date' => $this->voucher->expiry_date
            ] : null, // Kiểm tra và lấy thông tin voucher nếu có
        ];
    }
}