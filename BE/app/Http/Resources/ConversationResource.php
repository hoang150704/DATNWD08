<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {

        $isCustomer = $this->customer ? true : false;

        return [
            'id'     => $this->id,
            'status' => $this->status,

            'user' => [
                'name'   => $isCustomer ? $this->customer->name : $this->guest_name,
                'email'  => $isCustomer ? $this->customer->email : $this->guest_email,
                'phone'  => $isCustomer ? $this->customer->phone : $this->guest_phone,
                'avatar' => $isCustomer ? $this->customer->avatar : null,
                'type'   => $isCustomer ? 'customer' : 'guest',
            ],

            'staff' => [
                'name'   => $this->staff?->name,
                'avatar' => $this->staff?->avatar_url,
            ],

            'last_message'      => $this->latestMessage?->content,
            'last_message_time' => $this->latestMessage?->created_at?->diffForHumans(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
            'closed_at'  => $this->closed_at?->toDateTimeString(),
            'close_note' => $this->close_note,
            'feedback' => $this->whenLoaded('feedback', function () {
                return [
                    'rating'        => $this->feedback->rating,
                    'comment'       => $this->feedback->comment,
                    'submitted_by'  => $this->feedback->submitted_by,
                    'created_at'    => $this->feedback->created_at?->toDateTimeString(),
                ];
            }),
        ];
    }
}
