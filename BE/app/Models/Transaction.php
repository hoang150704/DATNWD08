<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'type',
        'amount',
        'status',
        'transaction_code',
        'note',

        // VNPAY
        'vnp_transaction_no',
        'vnp_bank_code',
        'vnp_bank_tran_no',
        'vnp_pay_date',
        'vnp_card_type',
        'vnp_response_code',
        'vnp_transaction_status',
        'vnp_create_date',
        'vnp_refund_request_id',

        // Ship_cod hoàn tiền thủ công
        'transfer_reference',
        'proof_images',
    ];

    protected $casts = [
        'vnp_pay_date' => 'datetime',
        'vnp_create_date' => 'datetime',
        'proof_images' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
