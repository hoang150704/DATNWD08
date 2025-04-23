<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvesationTransfer extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff' => [
                'id' => $this->from_staff?->id,
                'name'   => $this->from_staff?->name,
                'avatar' => $this->from_staff?->avatar,
            ],
            'to_staff_id' => $this->to_staff_id,
            'conversation_id' => $this->conversation_id,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'status' => $this->status
        ];
    }
}
