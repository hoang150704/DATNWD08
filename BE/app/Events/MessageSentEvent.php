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
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_type'     => $this->message->sender_type,
            'sender_id'       => $this->message->sender_id,
            'guest_id'        => $this->message->guest_id,
            'sender_name'     => $this->message->getSenderName(),
            'avatar'          => $this->message->getSenderAvatar(),
            'type'            => $this->message->type ?? 'text',
            'content'         => $this->message->content,
            'attachments'     => $this->message->whenLoaded('attachments', function () {
                return $this->message->attachments->map(function ($file) {
                    return [
                        'url'  => $file->file_url,
                        'type' => $file->file_type ?? 'file',
                        'name' => $file->file_name ?? null,
                    ];
                });
            }),
            'created_at'       => $this->message->created_at?->toDateTimeString(),
            'created_at_human' => $this->message->created_at?->format('H:i'),
        ];
    }
}
