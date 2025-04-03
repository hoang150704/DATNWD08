<?php

namespace App\Http\Controllers\Api\User;

use App\Events\NewCommentSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function getReviewsByProduct(Request $request, $productId)
    {
        $rating = $request->input('rating'); // Nhận rating từ request

        // Truy vấn bình luận với điều kiện lọc số sao nếu có
        $query = Comment::where('product_id', $productId)
            ->where('is_active', true)
            ->with(['user:id,name,avatar', 'product.variants'])
            ->orderBy('created_at', 'desc');

        if (!empty($rating) && in_array($rating, [1, 2, 3, 4, 5])) {
            $query->where('rating', $rating);
        }

        $reviews = $query->paginate(5)->through(function ($comment) {
            return [
                'id'        => $comment->id,
                'avatar'    => $comment->avatar,
                'username'  => $comment->user ? $this->maskName($comment->user->name) : 'Người dùng ẩn danh',
                'rating'    => $comment->rating,
                'variants'  => $comment->product->variants->map(function ($variant) {
                    return [
                        'id'   => $variant->id,
                        'name' => $variant->name,
                    ];
                }),
                'content'       => $comment->content,
                'created_at'    => Carbon::parse($comment->created_at)->format('d/m/Y'),
                'reply'         => $comment->reply ?? null,
            ];
        });

        return response()->json($reviews, 200);
    }

    // Xử lý việc che tên
    private function maskName($name)
    {
        $words = explode(' ', $name); // Tách từng từ trong tên
        $maskedName = array_map(function ($word) {
            return mb_substr($word, 0, 1) . '**'; // Lấy chữ cái đầu và thêm **
        }, $words);

        return implode(' ', $maskedName); // Gộp lại thành chuỗi hoàn chỉnh
    }

    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'rating'        => 'required|integer|min:1|max:5',
            'content'       => 'required|string|min:10|max:500',
        ]);

        // Lấy user ID từ người dùng đã đăng nhập
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Bạn cần đăng nhập để bình luận!'], 401);
        }
    
        // Lấy danh sách đơn hàng đã mua của user
        $order = DB::table('orders')
            ->where('user_id', $userId)
            ->pluck('id'); // Lấy danh sách order_id
    
        if ($order->isEmpty()) {
            return response()->json(['message' => 'Bạn chưa mua sản phẩm này!'], 403);
        }
    
        // Kiểm tra trạng thái đơn hàng
        $completedStatusId = 5;
        $orderStatus = DB::table('order_histories')
            ->whereIn('order_id', $order)
            ->orderBy('id', 'desc')
            ->first();

        if (!$orderStatus || $orderStatus->status_id != $completedStatusId) {
            return response()->json(['message' => 'Bạn chỉ có thể bình luận sau khi đơn hàng đã hoàn thành.'], 403);
        }

        // Tạo bình luận
        $comment = Comment::create([
            'product_id'    => $validated['product_id'],
            'user_id'       => $userId,
            'rating'        => $validated['rating'],
            'content'       => $validated['content'],
            'is_active'     => false
        ]);

        // Gửi thông báo đến admin để duyệt bình luận
        // event(new NewCommentSubmitted($comment));

        return response()->json([
            'message' => 'Bình luận của bạn đã được gửi thành công!',
            'data' => [
                'id'         => $comment->id,
                'product_id' => $comment->product_id,
                'user_id'    => $comment->user_id,
                'rating'     => $comment->rating,
                'content'    => $comment->content,
                'is_active'  => $comment->is_active,
                'created_at' => Carbon::parse($comment->created_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s'),
            ]
        ], 201);
    }

    public function update(Request $request, $commentId)
    {
        // Lấy bình luận cần chỉnh sửa
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json(['message' => 'Bình luận không tồn tại!'], 404);
        }

        // Kiểm tra nếu bình luận đã được chỉnh sửa trước đó
        if (!is_null($comment->edited_at)) {
            return response()->json(['message' => 'Bạn chỉ được chỉnh sửa bình luận 1 lần!'], 403);
        }

        // Kiểm tra nếu bình luận đã được tạo quá 7 ngày
        $yourComment = Carbon::parse($comment->created_at);
        $now = Carbon::now();
        if ($yourComment->diffInDays($now) > 7) {
            return response()->json(['message' => 'Bình luận đã quá 7 ngày và không thể chỉnh sửa!'], 403);
        }

        // Validate dữ liệu mới
        $validated = $request->validate([
            'rating'    => 'nullable|integer|min:1|max:5',
            'content'   => 'required|string|min:10|max:500'
        ]);

        // Kiểm tra nếu có thay đổi dữ liệu trước khi cập nhật
        $hasChanges = false;

        if ($comment->content !== $validated['content']) {
            $comment->content = $validated['content'];
            $hasChanges = true;
        }

        if (array_key_exists('rating', $validated) && $comment->rating !== $validated['rating']) {
            $comment->rating = $validated['rating'];
            $hasChanges = true;
        }

        if (!$hasChanges) {
            return response()->json(['message' => 'Không có thay đổi nào được thực hiện!'], 400);
        }

        // Cập nhật bình luận
        $comment->updated_at = Carbon::now();
        $comment->save();

        return response()->json([
            'message' => 'Bình luận đã được chỉnh sửa thành công!',
            'data' => [
                'id'            => $comment->id,
                'rating'        => $comment->rating,
                'content'       => $comment->content,
                'updated_at'    => $comment->updated_at->format('d/m/Y H:i:s'),
            ]
        ], 200);
    }
}
