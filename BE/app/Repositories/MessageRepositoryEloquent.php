<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\MessageRepository;
use App\Models\Message;
use App\Validators\MessageValidator;

/**
 * Class MessageRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class MessageRepositoryEloquent extends BaseRepository implements MessageRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Message::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    //

    public function getByConversation(int $conversationId, ?int $beforeId = null, int $limit = 50)
    {
        $query = Message::where('conversation_id', $conversationId)
            ->with('attachments')
            ->orderByDesc('id');
    
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }
    
        return $query->limit($limit)
            ->get()
            ->reverse()
            ->values(); // để đảm bảo thứ tự từ cũ → mới cho FE dễ hiển thị
    }
    
}
