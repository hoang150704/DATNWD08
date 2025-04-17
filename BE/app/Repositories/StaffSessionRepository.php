<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface StaffSessionRepository.
 *
 * @package namespace App\Repositories;
 */
interface StaffSessionRepository extends RepositoryInterface
{
    //
    public function isStaffOnline(int $staffId): bool;
}
