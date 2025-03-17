<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VoucherEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $action;
    public $data;

    public function __construct($action, $data)
    {
        $this->action = $action; // Hành động: created, updated, deleted
        $this->data = $data; // Dữ liệu liên quan (voucher hoặc ID)
    }

    public function broadcastOn()
    {
        return new Channel('voucher-channel');
    }

    public function broadcastWith()
    {
        return [
            'action' => $this->action,
            'data' => $this->data,
        ];
    }
}
