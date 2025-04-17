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
use Illuminate\Support\Facades\Log;

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
            'add' => 1
        ];
    }
}