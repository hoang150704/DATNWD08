<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'from_status_id',
        'to_status_id',
        'changed_by',
        'changed_at',
    ];

    protected $dates = ['changed_at'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function fromStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'from_status_id');
    }

    public function toStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'to_status_id');
    }

}
