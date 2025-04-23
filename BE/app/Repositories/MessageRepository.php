<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface MessageRepository.
 *
 * @package namespace App\Repositories;
 */
interface MessageRepository extends RepositoryInterface
{
    //
    public function create(array $data);
    public function getByConversation(int $conversationId, ?int $beforeId = null, int $limit = 50);
}
