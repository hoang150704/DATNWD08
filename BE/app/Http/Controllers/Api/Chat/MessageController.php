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
    public function getMessages($conversationId)
    {
        $messages = $this->messageService->getMessages($conversationId, 50);

        return response()->json([
            'messages' => MessageResource::collection($messages)
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
