<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('attachments'); 
    }

    public function broadcastOn(): Channel
    {
        return new Channel("conversation.{$this->message->conversation_id}");
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'content'    => $this->message->content,
            'sender_id'  => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'guest_id'   => $this->message->guest_id,
            'attachments' => $this->message->attachments,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
