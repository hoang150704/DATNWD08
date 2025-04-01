<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_code',
        'shipping_status_id',
        'carrier',
        'from_estimate_date',
        'to_estimate_date',
        'shipping_fee_details',
        'return_confirmed',
        'return_confirmed_at',
        'cancel_reason',
    ];

    protected $casts = [
        'from_estimate_date' => 'datetime',
        'to_estimate_date' => 'datetime',
        'return_confirmed_at' => 'datetime',
        'shipping_fee_details' => 'array',
        'return_confirmed' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingStatus()
    {
        return $this->belongsTo(ShippingStatus::class);
    }

    public function shippingLogs()
    {
        return $this->hasMany(ShippingLog::class);
    }
    public function shippingLogsTimeline()
    {
        return $this->hasMany(ShippingLog::class, 'shipment_id')
            ->orderBy('timestamp'); // timeline
    }
}
