<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface ConversationRepository.
 *
 * @package namespace App\Repositories;
 */
interface ConversationRepository extends RepositoryInterface
{
    //
    public function findOpenByGuest(string $guestId);
    public function create(array $data);
    public function close(int $id);
    public function assignStaff(int $conversationId, int $staffId);
}
