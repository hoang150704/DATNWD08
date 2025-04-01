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
            'amount' => $this->amount,
            'status' => $this->status,
            'reason' => $this->reason,
            'images' => $this->images ?? [],
            'bank_name' => $this->bank_name,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'created_at' => $this->created_at->toDateTimeString(),
        ];

        // Gom thông tin phụ theo trạng thái
        $details = collect();

        if (in_array($this->status, ['approved', 'refunded'])) {
            $details->put('Người duyệt', $this->approved_by);
            $details->put('Thời gian duyệt', optional($this->approved_at)?->toDateTimeString());
        }

        if ($this->status === 'rejected') {
            $details->put('Người từ chối', $this->rejected_by);
            $details->put('Thời gian từ chối', optional($this->rejected_at)?->toDateTimeString());
            $details->put('Lý do từ chối', $this->reject_reason);
        }

        if ($this->status === 'refunded') {
            $details->put('Thời gian hoàn tiền', optional($this->refunded_at)?->toDateTimeString());
        }

        $base['details'] = $details->filter()->toArray();

        return $base;
    }
}
