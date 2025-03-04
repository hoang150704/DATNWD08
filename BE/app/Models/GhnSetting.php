<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GhnSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'weight_box',
        'service_type_id',
        'shop_id'
    ];
}
