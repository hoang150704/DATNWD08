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
        'weight',
        'stock_quantity',
    ];
    
    public function values()
    {
        return $this->hasMany(ProductVariationValue::class, 'variation_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'variant_image', 'id');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at')->limit(1);
    }
}
