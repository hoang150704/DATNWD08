<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'amount',
        'reason',
        'images',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'status',
        'approved_by',
        'approved_at',
        'refunded_at',
        'reject_reason',
        'rejected_at',
        'rejected_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'images' => 'array',
        'approved_at' => 'datetime',
        'refunded_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
