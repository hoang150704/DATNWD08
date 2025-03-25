<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'type',
    ];
    public static function idByCode($code)
    {
        return OrderStatus::where('code', $code)->value('id');
    }
}

