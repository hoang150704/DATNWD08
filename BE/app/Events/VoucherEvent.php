<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $voucher;

    public function __construct($action, $voucher)
    {
        $this->action = $action;
        $this->voucher = $voucher;
    }

    public function broadcastOn()
    {
        return new Channel('vouchers');
    }

    public function broadcastAs()
    {
        return 'voucher.' . $this->action;
    }
}
