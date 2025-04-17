<?php

namespace App\Services\Chat\Interfaces;

interface ConversationServiceInterface
{

    //Tạo cuộc trò chuyện và gán nhân viên phù hợp (nếu có)

    public function createAndAssign(array $data);

    // Tìm cuộc trò chuyện đang mở theo guest_id (khách vãng lai)

    public function findOpenByGuest(string $guestId);


    // Tìm cuộc trò chuyện đang mở theo customer_id (khách đăng nhập)

    public function findOpenByCustomer(int $userId);


    // Gán nhân viên xử lý cuộc trò chuyện

    public function assignToStaff(int $conversationId, int $staffId);


    //  Đóng cuộc trò chuyện (manual hoặc sau 30 phút)

    public function close(int $conversationId, $user, ?string $note = null): bool;


    // Lấy các cuộc trò chuyện của nhân viên hiện tại

    public function myConversations(int $staffId, int $limit);


    //Lấy thông tin khách (guest_name, phone, email)

    public function guestInfo(int $conversationId);


    //Tìm nhân viên đang online để gán cuộc trò chuyện

    public function findAvailableStaff(): ?int;

    //Lấy chat cho admin
    public function adminConversations(int $limit,  $filters);

    //Nhận chat
    public function claim(int $conversationId, int $staffId);

    // Danh sách các cuộc trò chuyện chưa có ai hỗ trợ
    public function unassignedConversations(int $limit);
    //
    public function requestTransfer(int $conversationId, int $fromStaffId, int $toStaffId, ?string $note = null);
    public function acceptTransfer(int $transferId);
    public function rejectTransfer(int $transferId);
}
