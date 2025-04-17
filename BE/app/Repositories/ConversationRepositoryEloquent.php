<?php

namespace App\Repositories;

use App\Enums\SystemEnum;
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

    public function close(int $conversationId, ?string $note = null): bool
    {
        return Conversation::where('id', $conversationId)
            ->where('status', 'open')
            ->update([
                'status'     => 'closed',
                'close_note' => $note,
                'closed_at'  => now(),
            ]);
    }

    public function assignStaff(int $conversationId, int $staffId): bool
    {
        return Conversation::where('id', $conversationId)
            ->where('status', 'open')
            ->update(['current_staff_id' => $staffId]);
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

    public function getMyConversations(int $staffId, int $limit = 50)
    {
        return Conversation::with(['latestMessage', 'customer', 'staff', 'feedback'])
            ->where('current_staff_id', $staffId)
            ->orderByRaw("FIELD(status, 'open') DESC")
            ->orderByDesc('updated_at')
            ->paginate($limit);
    }

    public function getAdminConversations(int $limit = 50, array $filters = [])
    {
        $query = Conversation::with(['latestMessage', 'customer', 'staff', 'feedback']);

        // Lọc theo trạng thái (open / closed / ...)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Lọc theo nhân viên (theo ID)
        if (!empty($filters['staff_id'])) {
            $query->where('current_staff_id', $filters['staff_id']);
        }

        return $query
            ->orderByRaw("FIELD(status, 'open') DESC")
            ->orderByDesc('updated_at')
            ->paginate($limit);
    }

    public function isAssignableConversation(int $conversationId): bool
    {
        return Conversation::where('id', $conversationId)
            ->where('status', 'open')
            ->whereNull('current_staff_id')
            ->exists();
    }

    //
    public function transfer(int $conversationId, int $fromStaffId, int $toStaffId): ?Conversation
    {
        $conversation = Conversation::where('id', $conversationId)
            ->where('current_staff_id', $fromStaffId)
            ->where('status', 'open')
            ->first();

        if (!$conversation) return null;

        $conversation->update(['current_staff_id' => $toStaffId]);

        return $conversation->fresh();
    }
    //
    public function getUnassignedConversations(int $limit = 50)
    {
        return Conversation::whereNull('current_staff_id')
            ->where('status', SystemEnum::OPEN)
            ->latest('updated_at')
            ->with(['customer', 'staff', 'latestMessage'])
            ->paginate($limit);
    }
    public function findTransferableConversation(int $conversationId, int $fromStaffId): ?Conversation
    {
        return Conversation::where('id', $conversationId)
            ->where('current_staff_id', $fromStaffId)
            ->where('status', 'open')
            ->first();
    }
    //
    
}
