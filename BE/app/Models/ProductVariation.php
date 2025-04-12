<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $table = 'product_variations';
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
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variation_values', 'variation_id', 'attribute_value_id');
    }
    public function getFormattedVariation()
    {
        $attributes = $this->attributeValues()->with('attribute')->get();

        // Nếu không có giá trị thuộc tính, trả về null
        if ($attributes->isEmpty()) {
            return null;
        }

        return $attributes->mapWithKeys(function ($value) {
            return [$value->attribute->name => $value->name];
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class,'variation_id','id');
    }
}
