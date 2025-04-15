<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface ConversationFeedbackRepository.
 *
 * @package namespace App\Repositories;
 */
interface ConversationFeedbackRepository extends RepositoryInterface
{
    //
    public function getByConversation(int $conversationId);
}
