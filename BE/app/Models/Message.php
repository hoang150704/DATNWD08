<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'guest_id',
        'content',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
    public function getSenderName()
    {
        return match ($this->sender_type) {
            'staff', 'admin' => optional($this->sender)->name,
            'guest'          => $this->guest_name ?? 'Bạn',
            'system'         => 'Hệ thống',
            default          => null,
        };
    }

    public function getSenderAvatar()
    {
        return match ($this->sender_type) {
            'staff', 'admin' => optional($this->sender)->avatar_url,
            default          => null,
        };
    }
}
