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
        'order_status_id',
        'payment_status_id',
        'shipping_status_id',
        'cancel_reason',
        'cancel_by',
        'cancelled_at',
    ];

    protected $dates = ['cancelled_at'];

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
    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class);
    }
    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id')
            ->orderBy('changed_at'); 
    }
    public function payment_method()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }
}
