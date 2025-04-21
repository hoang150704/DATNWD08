<?php

namespace App\Repositories;

use App\Entities\Conversation;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\TransferRepository;
use App\Entities\Transfer;
use App\Models\ConversationTransfer;
use App\Validators\TransferValidator;

/**
 * Class TransferRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class TransferRepositoryEloquent extends BaseRepository implements TransferRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ConversationTransfer::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function logTransfer(int $conversationId, int $fromStaffId, int $toStaffId, ?string $note = null)
    {
        return ConversationTransfer::create([
            'conversation_id' => $conversationId,
            'from_staff_id'   => $fromStaffId,
            'to_staff_id'     => $toStaffId,
            'note'            => $note,
            'status'          => 'pending',
        ]);
    }
    public function findPendingTransferByStaff(int $transferId, int $staffId): ?ConversationTransfer
    {
        return ConversationTransfer::where('id', $transferId)
            ->where('to_staff_id', $staffId)
            ->where('status', 'pending')
            ->first();
    }
    public function getPendingTransferByStaffID( int $staffId)
    {
        return ConversationTransfer::where('to_staff_id', $staffId)
            ->where('status', 'pending')
            ->get();
    }
}
