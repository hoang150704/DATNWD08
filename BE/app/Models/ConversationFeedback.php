<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationFeedback extends Model
{
    use HasFactory;
    public $timestamps = false;
    public $table = 'conversation_feedbacks';
    protected $fillable = [
        'conversation_id',
        'rating',
        'comment',
        'submitted_by',
        'created_at'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
