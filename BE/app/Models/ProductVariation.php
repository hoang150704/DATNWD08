<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'sku',
        'variant_image',
        'regular_price',
        'sale_price',
        'stock_quantity',
    ];

}
