<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'transaction_code' => $this->transaction_code,
            'vnp_transaction_no' => $this->vnp_transaction_no,
            'vnp_bank_code' => $this->vnp_bank_code,
            'vnp_bank_tran_no' => $this->vnp_bank_tran_no,
            'vnp_card_type' => $this->vnp_card_type,
            'vnp_response_code' => $this->vnp_response_code,
            'vnp_transaction_status' => $this->vnp_transaction_status,
            'vnp_create_date' => optional($this->vnp_create_date)?->format('Y-m-d H:i:s'),
            'vnp_pay_date' => optional($this->vnp_pay_date)?->format('Y-m-d H:i:s'),
            'vnp_refund_request_id' => $this->vnp_refund_request_id,
            'transfer_reference' => $this->transfer_reference,
            'proof_images' => $this->proof_images,
            'note' => $this->note,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            // Giao diện frontend
            'label' => $this->getLabel(),
            'summary' => $this->getSummary(),
            'details' => $this->getDetails()
        ];
    }

    protected function getLabel()
    {
        return match (true) {
            $this->type === 'payment' && $this->status === 'success' => 'Đã thanh toán',
            $this->type === 'payment' && $this->status === 'pending' => 'Chờ thanh toán',
            $this->type === 'refund' && $this->status === 'success' => 'Đã hoàn tiền',
            $this->type === 'refund' && $this->status === 'pending' => 'Đang hoàn tiền',
            default => 'Giao dịch',
        };
    }

    protected function getSummary()
    {
        return "{$this->type} ({$this->status}) - {$this->amount} VND";
    }

    protected function getDetails()
    {
        if ($this->status === 'pending') {
            return [
                'Trạng thái' => 'Đang xử lý...',
                'Ghi chú' => $this->note,
            ];
        }

        if ($this->method === 'vnpay') {
            return collect([
                'Ngân hàng' => $this->vnp_bank_code,
                'Mã giao dịch ngân hàng' => $this->vnp_bank_tran_no,
                'Mã VNPAY' => $this->vnp_transaction_no,
                'Loại thẻ' => $this->vnp_card_type,
                'Thời gian thanh toán' => optional($this->vnp_pay_date)?->format('Y-m-d H:i:s'),
                'Mã hoàn tiền (nếu có)' => $this->vnp_refund_request_id,
                'Mã phản hồi' => $this->vnp_response_code,
                'Trạng thái VNPAY' => $this->vnp_transaction_status,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        if ($this->method === 'ship_cod') {
            return collect([
                'Hình thức' => 'Thanh toán khi nhận hàng',
                'Mã chuyển khoản hoàn tiền' => $this->transfer_reference,
                'Ảnh minh chứng' => $this->proof_images,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        return [];
    }
}
