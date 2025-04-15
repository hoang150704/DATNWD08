<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSession extends Model
{
    use HasFactory;
    protected $fillable = [
        'staff_id',
        'last_seen_at'
    ];
}
