<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ConversationFeedbackRepository;
use App\Entities\ConversationFeedback;
use App\Validators\ConversationFeedbackValidator;

/**
 * Class ConversationFeedbackRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ConversationFeedbackRepositoryEloquent extends BaseRepository implements ConversationFeedbackRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ConversationFeedback::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function getByConversation(int $conversationId)
    {
        return ConversationFeedback::where('conversation_id', $conversationId)->first();
    }
    
}
