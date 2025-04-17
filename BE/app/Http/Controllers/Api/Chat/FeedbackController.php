<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationFeedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    //
    public function submitFeedback(Request $request, int $id)
    {
        $request->validate([
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
            'submitted_by'  => 'nullable|string|max:255',
        ]);

        $conversation = Conversation::find($id);

        if (!$conversation || $conversation->status !== 'closed') {
            return response()->json(['message' => 'Chỉ được đánh giá sau khi cuộc trò chuyện đã kết thúc'], 400);
        }

        $existing = ConversationFeedback::where('conversation_id', $id)->first();
        if ($existing) {
            return response()->json(['message' => 'Bạn đã đánh giá cuộc trò chuyện này rồi'], 400);
        }

        $feedback = ConversationFeedback::create([
            'conversation_id' => $id,
            'rating'          => $request->rating,
            'comment'         => $request->comment,
            'submitted_by'    => $request->submitted_by,
            'created_at'      => now(),
        ]);

        return response()->json([
            'message' => 'Cảm ơn bạn đã đánh giá!',
            'data'    => $feedback,
        ]);
    }
}
