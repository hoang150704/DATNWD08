<?php

namespace App\Services\Chat;

use App\Services\Chat\Interfaces\MessageServiceInterface;
use App\Repositories\MessageRepository;
use App\Repositories\MessageRepositoryEloquent;

class MessageService implements MessageServiceInterface
{
    //
    protected $messageRepositoryEloquent;

    public function __construct(MessageRepositoryEloquent $messageRepositoryEloquent)
    {
        $this->messageRepositoryEloquent = $messageRepositoryEloquent;
    }

    public function sendMessage(array $data)
    {
        // Tạo data của tin nhắn 
        $dataMessage = [
            'conversation_id',
            'sender_type',
            'sender_id',
            'guest_id',
            'content',
            'type'
        ];
        return $this->messageRepositoryEloquent->create($data);
    }

    public function getMessages(int $conversationId, int $limit = 50)
    {
        return $this->messageRepositoryEloquent->getByConversation($conversationId, $limit);
    }
}