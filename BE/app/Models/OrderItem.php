<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'variation',
        'image',
        'product_name',
        'quantity',
        'price',
        'weight'
    ];

    // public function product()
    // {
    //     return $this->belongsTo(Product::class);
    // }
    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
    //
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
