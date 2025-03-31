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
            // Thôn tin chính
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method, 
            'type' => $this->type,     
            'status' => $this->status, 
            'amount' => (float) $this->amount,
            'transaction_code' => $this->transaction_code,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            //Giao diện
            'label' => $this->getLabel(),
            'summary' => $this->getSummary(),

            //Thông tin phụ (chỉ hiển thị khi xem chi tiết)
            'extra_details' => $this->getDetails()
        ];
    }

    protected function getLabel()
    {
        return match (true) {
            $this->type === 'payment' && $this->status === 'success' => 'Đã thanh toán',
            $this->type === 'payment' && $this->status === 'pending' => 'Chờ thanh toán',
            $this->type === 'payment' && $this->status === 'failed' => 'Thanh toán thất bại',
            $this->type === 'refund' && $this->status === 'success' => 'Đã hoàn tiền',
            $this->type === 'refund' && $this->status === 'pending' => 'Đang hoàn tiền',
            $this->type === 'refund' && $this->status === 'failed' => 'Hoàn tiền thất bại',
            default => 'Giao dịch',
        };
    }

    protected function getSummary()
    {
        return "{$this->type} ({$this->status}) - " . number_format($this->amount, 0, ',', '.') . ' VND';
    }

    protected function getDetails()
    {
        // Nếu đang xử lý
        if ($this->status === 'pending') {
            return [
                'Trạng thái' => 'Đang xử lý...',
                'Ghi chú' => $this->note,
            ];
        }

        // Nếu là VNPAY
        if ($this->method === 'vnpay') {
            // Nếu có hoàn tiền thủ công 
            if ($this->transfer_reference || $this->proof_images) {
                return collect([
                    'Hình thức' => 'Chuyển khoản thủ công (VNPAY lỗi)',
                    'Mã chuyển khoản' => $this->transfer_reference,
                    'Ảnh minh chứng' => $this->proof_images,
                    'Ghi chú' => $this->note,
                ])->filter()->toArray();
            }

            // Giao dịch tự động qua vnpay
            return collect([
                'Hình thức' => 'Thanh toán qua VNPAY',
                'Ngân hàng' => $this->vnp_bank_code,
                'Mã giao dịch ngân hàng' => $this->vnp_bank_tran_no,
                'Mã VNPAY' => $this->vnp_transaction_no,
                'Loại thẻ' => $this->vnp_card_type,
                'Thời gian tạo giao dịch' => optional($this->vnp_create_date)?->format('Y-m-d H:i:s'),
                'Thời gian thanh toán' => optional($this->vnp_pay_date)?->format('Y-m-d H:i:s'),
                'Mã hoàn tiền (nếu có)' => $this->vnp_refund_request_id,
                'Mã phản hồi' => $this->vnp_response_code,
                'Trạng thái VNPAY' => $this->vnp_transaction_status,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        // Nếu là ship_cod hoặc refund thủ công
        if ($this->method === 'ship_cod') {
            return collect([
                'Hình thức' => $this->type === 'refund' ? 'Hoàn tiền thủ công (ship_cod)' : 'Thanh toán khi nhận hàng',
                'Mã chuyển khoản' => $this->transfer_reference,
                'Ảnh minh chứng' => $this->proof_images,
                'Ghi chú' => $this->note,
            ])->filter()->toArray();
        }

        // Fallback
        return collect([
            'Ghi chú' => $this->note
        ])->filter()->toArray();
    }
}
