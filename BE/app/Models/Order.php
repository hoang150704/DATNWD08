<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'total_amount',
        'discount_amount',
        'final_amount',
        'payment_method',
        'shipping',
        'o_name',
        'o_address',
        'o_phone',
        'o_mail',
        'note',
        'payment_url',
        'expiried_at',
        'order_status_id',
        'payment_status_id',
        'shipping_status_id',
        'cancel_reason',
        'cancel_by',
        'cancelled_at',
        'ip_address'
    ];

    protected $casts = [
        'expiried_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function shippingStatus()
    {
        return $this->belongsTo(ShippingStatus::class);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancel_by');
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class)->latest();
    }
    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id')
            ->orderBy('changed_at');
    }
}
