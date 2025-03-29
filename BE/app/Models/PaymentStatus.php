<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentStatus extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];
    public static function idByCode($code)
    {
        return PaymentStatus::where('code', $code)->value('id');
    }
}
