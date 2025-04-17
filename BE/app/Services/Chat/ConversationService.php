<?php

namespace App\Services\Chat;

use App\Entities\Conversation;
use App\Entities\MessageRepository;
use App\Enums\SystemEnum;
use App\Events\ConversationAssignedEvent;
use App\Events\TransferRejectedEvent;
use App\Models\StaffSession;
use App\Repositories\ConversationRepository;
use App\Repositories\ConversationRepositoryEloquent;
use App\Repositories\MessageRepositoryEloquent;
use App\Repositories\StaffSessionRepositoryEloquent;
use App\Repositories\TransferRepositoryEloquent;
use App\Services\Chat\Interfaces\ConversationServiceInterface;

class ConversationService implements ConversationServiceInterface
{

       protected ConversationRepositoryEloquent $conversationRepositoryEloquent;
       protected MessageRepositoryEloquent $messageRepositoryEloquent;
       protected StaffSessionRepositoryEloquent $staffSessionEloquent;
       protected TransferRepositoryEloquent $transferRepositoryEloquent;

       public function __construct(
              ConversationRepositoryEloquent $conversationRepositoryEloquent,
              MessageRepositoryEloquent $messageRepositoryEloquent,
              StaffSessionRepositoryEloquent $staffSessionEloquent,
              TransferRepositoryEloquent $transferRepositoryEloquent
       ) {
              $this->conversationRepositoryEloquent = $conversationRepositoryEloquent;
              $this->messageRepositoryEloquent = $messageRepositoryEloquent;
              $this->staffSessionEloquent = $staffSessionEloquent;
              $this->transferRepositoryEloquent = $transferRepositoryEloquent;
       }

       //Tạo cuộc trò chuyện và gán nhân viên phù hợp (nếu có)

       public function createAndAssign(array $data)
       {
              $user = auth('sanctum')->user();

              if (in_array($user?->role, [SystemEnum::ADMIN, SystemEnum::STAFF])) {
                     return response()->json([
                            'message' => 'Nhân viên và quản trị viên không được phép khởi tạo cuộc trò chuyện.'
                     ], 403);
              }

              $existing = $user ? $this->conversationRepositoryEloquent->findOpenByCustomer($user->id)
                     : ($data['guest_id'] ?? null
                            ? $this->conversationRepositoryEloquent->findOpenByGuest($data['guest_id'])
                            : null);

              if ($existing) {
                     throw new \Exception('Bạn đã có một cuộc trò chuyện đang diễn ra.');
              }

              $dataConvertsation = [
                     'customer_id' =>  $user?->id ?? null,
                     'guest_id' => $data['guest_id'] ?? null,
                     'guest_name' => $data['guest_name'] ?? null,
                     'guest_email' => $data['guest_email'] ?? null,
                     'guest_phone' =>  $data['guest_phone'] ?? null,
                     'status' => SystemEnum::OPEN,
              ];
              // Tạo 1 convertsation mới
              $conversation = $this->conversationRepositoryEloquent->create($dataConvertsation);
              // Lấy ra id 1 nhân viên để gán trò chuyện
              $staffId = $this->findAvailableStaff();
              if (!$staffId) {
                     $this->messageRepositoryEloquent->create([
                            'conversation_id' => $conversation->id,
                            'content' => 'Hiện tại chưa có nhân viên nào online. Chúng tôi sẽ phản hồi sớm nhất có thể!',
                            'sender_type' => SystemEnum::SYSTEM,

                     ]);
              } else {
                     // gán chat cho nhân viên
                     $this->conversationRepositoryEloquent->assignStaff($conversation->id, $staffId);
              }
              return $conversation->fresh();
       }


       // Tìm cuộc trò chuyện đang mở theo guest_id (khách vãng lai)

       public function findOpenByGuest(string $guestId)
       {
              return $this->conversationRepositoryEloquent->findOpenByGuest($guestId);
       }


       // Tìm cuộc trò chuyện đang mở theo customer_id (khách đăng nhập)

       public function findOpenByCustomer(int $userId)
       {
              return $this->conversationRepositoryEloquent->findOpenByCustomer($userId);
       }


       // Gán nhân viên xử lý cuộc trò chuyện

       public function assignToStaff(int $conversationId, int $staffId): bool
       {
              if (
                     !$this->staffSessionEloquent->isStaffOnline($staffId) ||
                     !$this->conversationRepositoryEloquent->isAssignableConversation($conversationId)
              ) {
                     return false;
              }

              // Gán nhân viên
              return $this->conversationRepositoryEloquent->assignStaff($conversationId, $staffId);
       }


       //  Đóng cuộc trò chuyện (manual hoặc sau 30 phút)

       public function close(int $conversationId, $user, ?string $note = null): bool
       {
              $conversation = Conversation::find($conversationId);

              if (!$conversation || $conversation->status !== 'open') {
                     return false;
              }

              if ($user->role === 'staff' && $conversation->current_staff_id !== $user->id) {
                     return false;
              }

              return $this->conversationRepositoryEloquent->close($conversationId, $note);
       }


       // Lấy các cuộc trò chuyện của nhân viên hiện tại

       public function myConversations(int $staffId, int $limit)
       {
              return $this->conversationRepositoryEloquent->getMyConversations($staffId, $limit);
       }


       //Lấy thông tin khách (guest_name, phone, email)

       public function guestInfo(int $conversationId) {}


       //Tìm nhân viên đang online để gán cuộc trò chuyện

       public function findAvailableStaff(): ?int
       {
              return $this->conversationRepositoryEloquent->findAvailableStaffId();
       }

       //
       public function adminConversations(int $limit,  $filters)
       {
              return $this->conversationRepositoryEloquent->getAdminConversations($limit, $filters);
       }
       //
       public function claim(int $conversationId, int $staffId)
       {
              $conversation = Conversation::find($conversationId);

              if (!$conversation || $conversation->status !== SystemEnum::OPEN || $conversation->current_staff_id) {
                     return false;
              }

              return $this->conversationRepositoryEloquent->assignStaff($conversationId, $staffId);
       }

       // CHuyển tin nhắn
       public function requestTransfer(int $conversationId, int $fromStaffId, int $toStaffId, ?string $note = null)
       {
              $online = $this->staffSessionEloquent->isStaffOnline($toStaffId);

              if (!$online) return null;

              $conversation = $this->conversationRepositoryEloquent->findTransferableConversation($conversationId, $fromStaffId);

              if (!$conversation) return null;

              // Tạo bản ghi chuyển (chờ xác nhận)
              $this->transferRepositoryEloquent->logTransfer($conversationId, $fromStaffId, $toStaffId, $note);

              return $conversation;
       }

       // Đồng ý
       public function acceptTransfer(int $transferId): ?Conversation
       {
              $user = auth('sanctum')->user();

              if (!$user || !in_array($user->role, ['admin', 'staff'])) {
                     return null;
              }

              // Tìm bản ghi chuyển đang pending mà đúng là mình được chuyển tới
              $transfer = $this->transferRepositoryEloquent
                     ->findPendingTransferByStaff($transferId, $user->id);

              if (!$transfer) return null;

              // Cập nhật status = accepted
              $transfer->update(['status' => 'accepted']);

              // Cập nhật current_staff_id trong bảng conversation
              $conversation = $this->conversationRepositoryEloquent
                     ->transfer($transfer->conversation_id, $transfer->from_staff_id, $user->id);

              if (!$conversation) return null;

              // Tạo tin nhắn hệ thống xác nhận chuyển
              $this->messageRepositoryEloquent->create([
                     'conversation_id' => $conversation->id,
                     'content' => 'Nhân viên ' . $user->name . ' đã nhận cuộc trò chuyện này.',
                     'sender_type' => SystemEnum::SYSTEM,
              ]);

              // Gửi event nếu có
              event(new ConversationAssignedEvent($conversation));

              return $conversation->fresh();
       }

       public function rejectTransfer(int $transferId): bool
       {
              $user = auth('sanctum')->user();

              if (!$user || !in_array($user->role, ['admin', 'staff'])) {
                     return false;
              }

              $transfer = $this->transferRepositoryEloquent
                     ->findPendingTransferByStaff($transferId, $user->id);

              if (!$transfer) return false;

              $transfer->update(['status' => 'rejected']);
              event(new TransferRejectedEvent($transfer));


              return true;
       }

       public function unassignedConversations(int $limit = 50)
       {
              return $this->conversationRepositoryEloquent->getUnassignedConversations($limit);
       }
}
