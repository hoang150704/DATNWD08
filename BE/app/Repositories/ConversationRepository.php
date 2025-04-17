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
    public function close(int $conversationId, ?string $note = null): bool;
    public function assignStaff(int $conversationId, int $staffId);
    public function findAvailableStaffId();
    public function getAllForAdmin(array $filters = []);
    public function findOpenByCustomer(int $customerId);
    public function getMyConversations(int $staffId, int $limit);
    public function getAdminConversations(int $limit, array $filters = []);
    public function isAssignableConversation(int $conversationId): bool;
    public function transfer(int $conversationId, int $fromStaffId, int $toStaffId);
    public function getUnassignedConversations(int $limit);
    public function findTransferableConversation(int $conversationId, int $fromStaffId);
}
