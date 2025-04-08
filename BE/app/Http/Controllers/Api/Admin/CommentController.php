<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CommentController extends Controller
{

    protected function search($keyword = null, $rating = null, $isActive = null)
    {
        $query = Comment::query();

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('content', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_email', 'like', "%{$keyword}%");
            });
        }

        if ($rating) {
            $query->where('rating', $rating);
        }

        if (!is_null($isActive)) {
            $query->where('is_active', $isActive);
        }

        return $query
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->paginate(10);
    }



    public function index(Request $request)
    {
        try {
            $keyword = $request->input('keyword');
            $rating = $request->input('rating');
            $isActive = $request->has('is_active') ? (int)$request->input('is_active') : null;

            $comments = $this->search($keyword, $rating, $isActive);
            $comments->transform(function ($comment) {
                return [
                    'id' => $comment->id,
                    'reviewer_name' => $comment->user_id ? $comment->user->name : $comment->customer_name ?? '[Ẩn danh]',
                    'reviewer_email' => $comment->user_id ? $comment->user->email : $comment->customer_mail ?? '[Không có]',
                    'rating' => $comment->rating,
                    'content_preview' => Str::limit(strip_tags($comment->content), 80),
                    'is_active' => $comment->is_active,
                    'is_updated' => $comment->is_updated,
                    'has_reply' => !empty($comment->reply),
                    'created_at' => Carbon::parse($comment->created_at)->format('d/m/Y H:i'),
                ];
            });
            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function show(Comment $comment)
    {
        try {
            $comment = Comment::with(['user', 'order', 'orderItem'])->findOrFail($comment->id);

            $reviewerName = $comment->user?->name ?? $comment->customer_name ?? '[Ẩn danh]';
            $reviewerEmail = $comment->user?->email ?? $comment->customer_mail ?? '[Không có email]';
            $avatar = $comment->user?->avatar
                ? $comment->user->avatar
                : 'https://png.pngtree.com/png-clipart/20210608/ourlarge/pngtree-dark-gray-simple-avatar-png-image_3418404.jpg';
            $data = [
                'id' => $comment->id,
                'reviewer_name' => $reviewerName,
                'reviewer_email' => $reviewerEmail,
                'avatar' => $avatar,
                'user_type' => $comment->user_id ? 'Tài khoản' : 'Khách mua hàng',

                'order' => [
                    'code' => $comment->order->code ?? null,
                    // 'created_at' =>$comment->order->created_at->format('d/m/Y H:i'),
                    'status' => $comment->order->status->name ?? null,
                ],

                'product' => [
                    'name' => $comment->orderItem->product_name ?? '[Không có]',
                    'image' => $comment->orderItem->product_image ?? null,
                    'sku' => $comment->orderItem->product_sku ?? null,
                    'price' => $comment->orderItem->unit_price ?? null,
                ],

                'review' => [
                    'rating' => $comment->rating,
                    'content' => $comment->content,
                    'images' => $comment->images ?? [],
                    'is_updated' => $comment->is_updated,
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                ],

                'moderation' => [
                    'is_active' => $comment->is_active,
                    'hidden_reason' => $comment->hidden_reason,
                    'reply' => $comment->reply,
                    'reply_at' => $comment->reply_at ? $comment->reply_at->format('d/m/Y H:i') : null,
                ]
            ];
            return response()->json([
                'message' => 'Success',
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage(),
            ], 404);
        }
    }


    // public function destroy()
    // {
    //     try {
    //         $id = request()->id;
    //         Comment::whereIn('id', $id)->delete();

    //         return response()->json([
    //             'message' => 'Success',
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'Failed',
    //         ], 404);
    //     }
    // }

    public function reply(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reply' => 'required|string|min:5|max:3000'
            ], [
                'reply.required' => 'Nội dung phản hồi không được để trống.',
                'reply.string' => 'Nội dung phản hồi không hợp lệ.',
                'reply.min' => 'Phản hồi quá ngắn (tối thiểu 5 ký tự).',
                'reply.max' => 'Phản hồi không được vượt quá 3000 ký tự.'
            ]);

            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json([
                    'message' => 'Không tìm thấy đánh giá.'
                ], 404);
            }

            if (!empty($comment->reply)) {
                return response()->json([
                    'message' => 'đánh giá này đã được phản hồi.'
                ], 400);
            }

            $comment->reply = $validated['reply'];
            $comment->reply_at = now();
            $comment->save();

            return response()->json([
                'message' => 'Success',
                'data' => $comment
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }


    public function statusToggle(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|boolean',
                'reason' => 'nullable|string|max:1000',
            ], [
                'status.required' => 'Trạng thái không được bỏ trống.',
                'status.boolean' => 'Trạng thái không hợp lệ.',
                'reason.max' => 'Lý do không được vượt quá 1000 ký tự.',
            ]);

            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json([
                    'message' => 'Không tìm thấy đánh giá.'
                ], 404);
            }

            $status = (bool) $request->status;

            // Nếu muốn ẩn, phải có lý do
            if (!$status && empty($request->reason)) {
                return response()->json([
                    'message' => 'Vui lòng nhập lý do khi ẩn đánh giá.'
                ], 422);
            }

            $comment->is_active = $status;
            $comment->hidden_reason = $status ? null : $request->reason;
            $comment->save();

            return response()->json([
                'message' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failler',
            ], 500);
        }
    }
}
