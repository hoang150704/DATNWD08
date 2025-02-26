<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;

    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'is_active',
        'email_verified_at',
        'role',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    public function voucherUsages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMIN;
    }
    public function isMember()
    {
        return $this->role == self::ROLE_MEMBER;
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'avatar', 'id');
    }
}
