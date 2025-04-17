<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Conversation $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation->load('staff');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("admin"),
            new Channel("staff.{$this->conversation->current_staff_id}"),
            new Channel("conversation.{$this->conversation->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'staff_id'        => $this->conversation->current_staff_id,
            'staff_name'      => $this->conversation->staff->name,
        ];
    }
}
