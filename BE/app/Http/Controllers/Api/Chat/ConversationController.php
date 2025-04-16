<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Services\Chat\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }
    //
    public function createAndAssign(CreateConversationRequest $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $conversation = $this->conversationService->createAndAssign($data);
            DB::commit();
            return response()->json([
                'message' => 'Tạo cuộc trò chuyện thành công',
                'data' => $conversation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi tạo hội thoại',
                'errors' => $e->getMessage()
            ], 403);
        }
    }

    public function getActiveConversation(Request $request)
    {
        $user_id = auth('sanctum')->user()?->id ?? null;
        $guest_id = $request->input('guest_id');
        if ($user_id) {
            $conversation = $this->conversationService->findOpenByCustomer($user_id);
        } elseif ($guest_id) {
            $conversation = $this->conversationService->findOpenByGuest($guest_id);
        } else {
            return response()->json([
                'conversation' => null,
                'message' => 'Không xác định được người dùng',
            ], 200);
        }
        return response()->json([
            'conversation' => $conversation,
        ]);
    }
}
