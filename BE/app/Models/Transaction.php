<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'method',
        'type',
        'amount',
        'transaction_code',
        'vnp_transaction_no',
        'vnp_bank_code',
        'vnp_bank_tran_no',
        'vnp_card_type',
        'vnp_pay_date',
        'vnp_response_code',
        'vnp_transaction_status',
        'vnp_create_date',
        'vnp_refund_request_id',
        'status',
        'note',
        'created_at',
    ];

    protected $dates = ['vnp_pay_date', 'created_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
