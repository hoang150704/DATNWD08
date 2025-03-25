<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'reason',
        'amount',
        'status',
        'approved_by',
        'approved_at',
        'refunded_at',
    ];

    protected $dates = ['approved_at', 'refunded_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
