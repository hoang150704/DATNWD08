<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'shipping_code' => $this->shipping_code,
            'shipping_status_id' => $this->shipping_status_id,
            'shipping_status_name' => optional($this->shippingStatus)->name,
            'carrier' => $this->carrier,
            'from_estimate_date' => $this->from_estimate_date?->format('Y-m-d H:i:s'),
            'to_estimate_date' => $this->to_estimate_date?->format('Y-m-d H:i:s'),
            'shipping_fee_details' => is_array($this->shipping_fee_details)
                ? $this->shipping_fee_details
                : json_decode($this->shipping_fee_details, true),
            'return_confirmed' => $this->return_confirmed,
            'return_confirmed_at' => $this->return_confirmed_at?->format('Y-m-d H:i:s'),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
