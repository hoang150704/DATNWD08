<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusTracking extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
    protected $casts = ['next_status_allowed' => 'array'];

    public function order()
    {
        return $this->hasOne(Order::class, 'stt_track');
    }
}
