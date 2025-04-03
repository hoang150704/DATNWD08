<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'message', 'status', 'user_id'];

    protected $dates = ['deleted_at']; // Đánh dấu ngày xóa mềm

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
