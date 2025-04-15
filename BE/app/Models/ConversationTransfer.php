<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationTransfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'conversation_id',
        'from_staff_id',
        'to_staff_id',
        'note',
        'created_at'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
