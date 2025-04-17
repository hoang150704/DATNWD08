<?php

namespace App\Events;

use App\Models\ConversationTransfer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class TransferRejectedEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $transfer;

    public function __construct(ConversationTransfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('staff.' . $this->transfer->from_staff_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transfer.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->transfer->conversation_id,
            'to_staff_id'     => $this->transfer->to_staff_id,
            'note'            => $this->transfer->note,
            'status'          => 'rejected',
            'message'         => 'Yêu cầu chuyển hội thoại đã bị từ chối',
        ];
    }
}


