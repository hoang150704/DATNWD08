<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray($request)
    {
        $base = [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'amount' => $this->amount,
            'images' => $this->images ?? [],
            'bank_name' => $this->bank_name,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'created_at' => $this->created_at->toDateTimeString(),
        ];

        // Nếu đã được duyệt
        if ($this->status === 'approved' || $this->status === 'refunded') {
            $base['approved_by'] = $this->approved_by;
            $base['approved_at'] = optional($this->approved_at)->toDateTimeString();
        }

        // Nếu bị từ chối
        if ($this->status === 'rejected') {
            $base['rejected_by'] = $this->rejected_by;
            $base['rejected_at'] = optional($this->rejected_at)->toDateTimeString();
            $base['reject_reason'] = $this->reject_reason;
        }

        // Nếu đã hoàn tiền
        if ($this->status === 'refunded') {
            $base['refunded_at'] = optional($this->refunded_at)->toDateTimeString();
        }

        return $base;
    }
}
