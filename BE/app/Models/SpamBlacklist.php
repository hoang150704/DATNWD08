<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpamBlacklist extends Model
{
    use HasFactory;
    protected $fillable = [
        'type', 'value', 'reason', 'until','action',
    ];

    protected $dates = ['until'];
}
