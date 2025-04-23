<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\Chat\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    //
    public function getMessages(Request $request, $conversationId)
    {
        $beforeId = $request->query('before_id'); // lấy tin nhắn trước ID này (nếu có)
        $limit = 50;    // mặc định lấy 50 tin nhắn
    
        $messages = $this->messageService->getMessages($conversationId, $beforeId, $limit);
    
        return response()->json([
            'messages' => MessageResource::collection($messages),
            'has_more' => count($messages) === (int) $limit,
            'last_loaded_id' => $messages->first()?->id // để FE gửi before_id cho lần sau
        ]);
    }
    
    //
    public function sendMessage(SendMessageRequest $request)
    {
        try {
            $message = $this->messageService->sendMessage($request->validated());

            return new MessageResource($message);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi gửi tin nhắn',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }
}
