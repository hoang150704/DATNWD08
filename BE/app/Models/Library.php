<?php

namespace App\Models;

use App\Traits\UploadTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    use HasFactory;
    use UploadTraits;
    protected $fillable = [
        'public_id','url'
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_images');
    }
}
