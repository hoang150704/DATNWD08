<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ConversationRepository;
use App\Entities\Conversation;
use App\Validators\ConversationValidator;

/**
 * Class ConversationRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ConversationRepositoryEloquent extends BaseRepository implements ConversationRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Conversation::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    public function findOpenByGuest(string $guestId)
    {
        return Conversation::where('guest_id', $guestId)
            ->where('status', 'open')
            ->latest()
            ->first();
    }

    public function close(int $id)
    {
        return Conversation::where('id', $id)->update(['status' => 'closed']);
    }

    public function assignStaff(int $conversationId, int $staffId)
    {
        return Conversation::where('id', $conversationId)->update(['current_staff_id' => $staffId]);
    }
}
