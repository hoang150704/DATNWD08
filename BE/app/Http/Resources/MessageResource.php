<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_type'     => $this->sender_type,
            'sender_id'       => $this->sender_id,
            'guest_id'        => $this->guest_id,
            'sender_name'     => $this->getSenderName(),
            'avatar'          => $this->getSenderAvatar(),
            'type'            => $this->type ?? 'text',
            'content'         => $this->content,
            'attachments'     => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($file) {
                    return [
                        'url'  => $file->file_url,
                        'type' => $file->file_type ?? 'file',
                        'name' => $file->file_name ?? null,
                    ];
                });
            }),
            'created_at'       => $this->created_at?->toDateTimeString(),
            'created_at_human' => $this->created_at?->format('H:i'),
        ];
    }
}
