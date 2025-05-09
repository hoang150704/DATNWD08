<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffOnlineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->staff_id,
            'staff_online_id' => $this->id,
            'name' => $this->staff->name,
            'email' => $this->staff->email,
            'avatar' => $this->staff->avatar
        ];
    }
}
