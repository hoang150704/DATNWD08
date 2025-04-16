<?php

namespace App\Services\Chat;

use App\Entities\Conversation;
use App\Events\MessageSentEvent;
use App\Repositories\AttachmentRepositoryEloquent;
use App\Services\Chat\Interfaces\MessageServiceInterface;
use App\Repositories\MessageRepository;
use App\Repositories\MessageRepositoryEloquent;

class MessageService implements MessageServiceInterface
{
    //
    protected $messageRepositoryEloquent;
    protected $attachmentRepositoryEloquent;

    public function __construct(MessageRepositoryEloquent $messageRepositoryEloquent, AttachmentRepositoryEloquent $attachmentRepositoryEloquent)
    {
        $this->messageRepositoryEloquent = $messageRepositoryEloquent;
        $this->attachmentRepositoryEloquent = $attachmentRepositoryEloquent;
    }

    public function sendMessage(array $data)
    {
        $user = auth('sanctum')->user();

        // Gán người gửi
        if ($user) {
            $role = $user->role;

            $data['sender_type'] = in_array($role, ['admin', 'staff']) ? 'staff'
                : ($role === 'member' ? 'customer' : 'guest');

            $data['sender_id'] = $user->id;
        } else {
            $data['sender_type'] = 'guest';
            $data['sender_id']   = null;
        }

        //
        $conversation = Conversation::findOrFail($data['conversation_id']);
        $this->validateSenderInConversation($conversation, $data);


        // Tạo message
        $message = $this->messageRepositoryEloquent->create([
            'conversation_id' => $data['conversation_id'],
            'content'         => $data['content'] ?? null,
            'sender_type'     => $data['sender_type'],
            'sender_id'       => $data['sender_id'] ?? null,
            'guest_id'        => $data['guest_id'] ?? null,
        ]);

        // Lưu file đính kèm nếu có
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $item) {
                $this->attachmentRepositoryEloquent->create([
                    'message_id' => $message->id,
                    'file_url'   => $item['url'],
                    'file_type'  => $item['type'],
                    'file_name'  => $item['name'] ?? null,
                ]);
            }
        }
        event(new MessageSentEvent($message));

        return $message->fresh(['attachments']);
    }

    public function getMessages(int $conversationId, int $limit = 50)
    {
        return $this->messageRepositoryEloquent->getByConversation($conversationId, $limit);
    }

    protected function validateSenderInConversation($conversation, $data): void
    {
        switch ($data['sender_type']) {
            case 'guest':
                if ($conversation->guest_id !== ($data['guest_id'] ?? null)) {
                    throw new \Exception('Khách không khớp cuộc trò chuyện.');
                }
                break;
            case 'customer':
                if ($conversation->customer_id !== ($data['sender_id'] ?? null)) {
                    throw new \Exception('Người dùng không khớp cuộc trò chuyện.');
                }
                break;
            case 'staff':
                if ($conversation->current_staff_id !== ($data['sender_id'] ?? null)) {
                    throw new \Exception('Nhân viên không được phép trả lời cuộc trò chuyện này.');
                }
                break;
        }
    }
}
