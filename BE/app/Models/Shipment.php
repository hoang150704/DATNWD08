<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_code',
        'shipping_status_id',
        'shipping_fee',
        'carrier',
        'from_estimate_date',
        'to_estimate_date',
        'actual_delivery_date',
        'pickup_time',
        'cancel_reason',
    ];

    protected $dates = [
        'from_estimate_date',
        'to_estimate_date',
        'actual_delivery_date',
        'pickup_time',
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
