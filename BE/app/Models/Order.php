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
        'stt_track',
        'stt_payment',
        'note'
    ];

    public function stt_track()
    {
        return $this->belongsTo(StatusTracking::class, 'stt_track');
    }

    public function stt_payment()
    {
        return $this->belongsTo(StatusPayment::class, 'stt_payment');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
