<?php

namespace App\Services\Chat;

use App\Entities\MessageRepository;
use App\Enums\SystemEnum;
use App\Repositories\ConversationRepository;
use App\Repositories\ConversationRepositoryEloquent;
use App\Repositories\MessageRepositoryEloquent;
use App\Services\Chat\Interfaces\ConversationServiceInterface;

class ConversationService implements ConversationServiceInterface
{

       protected ConversationRepositoryEloquent $conversationRepositoryEloquent;
       protected MessageRepositoryEloquent $messageRepositoryEloquent;

       public function __construct(
              ConversationRepositoryEloquent $conversationRepositoryEloquent,
              MessageRepositoryEloquent $messageRepositoryEloquent
       ) {
              $this->conversationRepositoryEloquent = $conversationRepositoryEloquent;
              $this->messageRepositoryEloquent = $messageRepositoryEloquent;
       }

       //Tạo cuộc trò chuyện và gán nhân viên phù hợp (nếu có)

       public function createAndAssign(array $data)
       {
              $user = auth('sanctum')->user();

              if (in_array($user->role, [SystemEnum::ADMIN, SystemEnum::STAFF])) {
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
                            'sender_type' => 'system',

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

       public function assignToStaff(int $conversationId, int $staffId) {}


       //  Đóng cuộc trò chuyện (manual hoặc sau 30 phút)

       public function close(int $conversationId) {}


       // Lấy các cuộc trò chuyện của nhân viên hiện tại

       public function myConversations(int $staffId) {}


       //Lấy thông tin khách (guest_name, phone, email)

       public function guestInfo(int $conversationId) {}


       //Tìm nhân viên đang online để gán cuộc trò chuyện

       public function findAvailableStaff(): ?int
       {
              return $this->conversationRepositoryEloquent->findAvailableStaffId();
       }
}
