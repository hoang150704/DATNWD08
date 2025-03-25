<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VoucherEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $voucher;

    public $connection = 'sync';
    
    public function __construct($voucher)
    {
        $this->voucher = $voucher;
    }

    public function broadcastOn()
    {
        return new Channel('voucher-channel');
    }

    public function broadcastAs(): string
    {
        return 'voucher-event';
    }

    public function broadcastWith()
    {
        return [
            'voucher_code' => $this->voucher->code,
            'discount' => $this->voucher->discount,
            'expiry_date' => $this->voucher->expiry_date->toDateTimeString(),
        ];
    }
}
