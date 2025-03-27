<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'reason',
        'images',
        'status',
        'approved_by',
        'approved_at',
        'refunded_at',
    ];

    protected $casts = [
        'images' => 'array',
        'approved_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
