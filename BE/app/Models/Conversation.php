<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'guest_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'current_staff_id',
        'status'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function feedback()
    {
        return $this->hasOne(ConversationFeedback::class);
    }

    public function transfers()
    {
        return $this->hasMany(ConversationTransfer::class);
    }
}
