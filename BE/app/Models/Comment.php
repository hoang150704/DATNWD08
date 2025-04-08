<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $table = 'product_reviews';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'user_id',
        'customer_name',
        'customer_mail',
        'rating',
        'content',
        'images',
        'reply',
        'hidden_reason',
        'is_active',
        'is_updated',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'is_updated' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(function ($user) {
            $user->name = '[Người dùng đã xoá]';
            $user->email = '[Email đã bị ẩn]';
        });
    }
}
