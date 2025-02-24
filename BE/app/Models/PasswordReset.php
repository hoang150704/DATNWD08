<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    protected $table = 'password_reset_tokens'; // Chỉ định bảng

    protected $fillable = ['email', 'token', 'created_at']; // Các cột được phép điền vào

    public $timestamps = false; // Vì bảng này không có cột `updated_at`
}
