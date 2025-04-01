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
        $base = [
            'id' => $this->id,
            'shipping_code' => $this->shipping_code,
            'shipping_status_name' => $this->shippingStatus->name,
            'carrier' => $this->carrier,
            'from_estimate_date' => $this->from_estimate_date,
            'to_estimate_date' => $this->to_estimate_date,
            'actual_delivery_date' => $this->actual_delivery_date,
            'pickup_time' => $this->pickup_time,
            ''
        ];
        return parent::toArray($request);
    }
}
