<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusTracking extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function order()
    {
        return $this->hasMany(Order::class,'stt_track');
    }
}
