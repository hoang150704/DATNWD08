<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface AttachmentRepository.
 *
 * @package namespace App\Repositories;
 */
interface AttachmentRepository extends RepositoryInterface
{
    //
    public function create(array $data);
}
