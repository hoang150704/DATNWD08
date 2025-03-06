<?php

namespace App\Models;

use App\Traits\UploadTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UploadTraits;
    protected $fillable = [
        'name',
        'description',
        'short_description',
        'main_image',
        'slug',
        'type',
        'box_id'
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariation::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category_relations');
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'main_image', 'id');
    }
    public function productImages()
    {
        return $this->belongsToMany(Library::class, 'product_images');
    }
    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'product_id');
    }

    public function box()
    {
        return $this->belongsTo(Box::class, 'box_id', 'id');
    }
}
