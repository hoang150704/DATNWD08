<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingStatus extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];
    public static function idByCode($code)
    {
        return static::where('code', $code)->value('id');
    }

}
