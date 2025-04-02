<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingLog extends Model
{
    public $timestamps = false;

  
    protected $fillable = [
        'shipment_id',
        'ghn_status',
        'mapped_status_id',
        'location',
        'note',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function mappedStatus()
    {
        return $this->belongsTo(ShippingStatus::class, 'mapped_status_id');
    }
}
