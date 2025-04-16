<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ConversationRepository;
use App\Models\Conversation;
use App\Models\StaffSession;
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

    public function findOpenByCustomer(int $customerId)
    {
        return Conversation::where('customer_id', $customerId)
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

    public function findAvailableStaffId(): ?int
    {
        $onlineStaff = StaffSession::where('last_seen_at', '>=', now()->subMinutes(5))
            ->pluck('staff_id');

        if ($onlineStaff->isEmpty()) {
            return null;
        }

        $staffLoad = Conversation::where('status', 'open')
            ->whereIn('current_staff_id', $onlineStaff)
            ->selectRaw('current_staff_id, COUNT(*) as total')
            ->groupBy('current_staff_id')
            ->pluck('total', 'current_staff_id');

        return $staffLoad->sort()->keys()->first() ?? $onlineStaff->first();
    }

    public function getAllForAdmin(array $filters = [])
    {
        $query = Conversation::query()
            ->with(['latestMessage', 'staff:id,name,avatar', 'customer:id,name,email']) // eager load các quan hệ
            ->latest();

        // Lọc theo status (Mở / Đóng)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Lọc theo nhân viên (Theo ID nhân viên)
        if (!empty($filters['staff_id'])) {
            $query->where('current_staff_id', $filters['staff_id']);
        }

        // Optional filter: thời gian tạo
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate(20);
    }
}
