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
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            // ✨ Dữ liệu dùng cho frontend hiển thị
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
            $this->type === 'payment' && $this->status === 'failed' => 'Thanh toán thất bại',
            $this->type === 'refund' && $this->status === 'success' => 'Đã hoàn tiền',
            $this->type === 'refund' && $this->status === 'pending' => 'Đang xử lý',
            $this->type === 'refund' && $this->status === 'failed' => 'Hoàn tiền thất bại',
            default => 'Không xác định'
        };
    }



    protected function getSummary()
    {
        return match ($this->method) {
            'vnpay' => "{$this->getLabel()} qua VNPAY",
            'ship_cod' => "{$this->getLabel()} khi nhận hàng",
            default => $this->getLabel()
        };
    }

    protected function getDetails()
    {
        if ($this->status === 'pending') {
            return [
                'Trạng thái' => 'Đang xử lý...',
                'Ghi chú' => $this->note,
            ];
        }

        if ($this->type === 'payment' && $this->method === 'vnpay') {
            return collect([
                'Ngân hàng' => $this->vnp_bank_code,
                'Mã giao dịch ngân hàng' => $this->vnp_bank_tran_no,
                'Mã VNPAY' => $this->vnp_transaction_no,
                'Loại thẻ' => $this->vnp_card_type,
                'Thời gian thanh toán' => optional($this->vnp_pay_date)?->format('Y-m-d H:i:s'),
                'Mã phản hồi' => $this->vnp_response_code,
                'Trạng thái VNPAY' => $this->vnp_transaction_status,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        if ($this->type === 'refund' && $this->method === 'vnpay') {
            return collect([
                'Ngân hàng' => $this->vnp_bank_code,
                'Mã hoàn tiền VNPAY' => $this->vnp_transaction_no,
                'Yêu cầu hoàn tiền' => $this->vnp_refund_request_id,
                'Thời gian hoàn tiền' => optional($this->vnp_pay_date)?->format('Y-m-d H:i:s'),
                'Mã phản hồi' => $this->vnp_response_code,
                'Trạng thái hoàn' => $this->vnp_transaction_status,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        // ship_cod
        return collect([
            'Hình thức' => 'Thanh toán khi nhận hàng',
            'Ghi chú' => $this->note,
        ])->filter()->toArray();
    }
}
