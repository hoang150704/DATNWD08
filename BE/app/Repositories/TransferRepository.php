<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface TransferRepository.
 *
 * @package namespace App\Repositories;
 */
interface TransferRepository extends RepositoryInterface
{
    //
   
    public function logTransfer(int $conversationId, int $fromStaffId, int $toStaffId, ?string $note = null);
    public function findPendingTransferByStaff(int $transferId, int $staffId);
}
