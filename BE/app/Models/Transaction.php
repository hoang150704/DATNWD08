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
        'vnp_pay_date',
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
