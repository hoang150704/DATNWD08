<?php

namespace App\Http\Controllers\Api\User;

use App\Events\NewCommentSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function getReviewsByProduct($productId, Request $request)
    {
        $rating = $request->input('rating');
        $hasImages = $request->boolean('has_images');

        $query = Comment::where('product_id', $productId)
            ->where('is_active', true)
            ->with('user:id,name,email,avatar');

        if (in_array($rating, [1, 2, 3, 4, 5])) {
            $query->where('rating', $rating);
        }

        if ($hasImages) {
            $query->whereNotNull('images')->where('images', '!=', '[]');
        }

        $reviews = $query->orderByDesc('created_at')->paginate(5);

        $reviews->transform(function ($comment) {
            $name = $comment->user?->name ?? $comment->customer_name ?? 'Ẩn danh';
            $email = $comment->user?->email ?? $comment->customer_mail ?? null;

            return [
                'id'         => $comment->id,
                'name'       => $name,
                'email'      => $this->maskEmail($email),
                'avatar'     => $comment->user?->avatar
                    ?: 'https://png.pngtree.com/png-clipart/20210608/ourlarge/pngtree-dark-gray-simple-avatar-png-image_3418404.jpg',
                'rating'     => $comment->rating,
                'variation'=> $comment->orderItem->variation ?? null,
                'content'    => $comment->content,
                'is_updated'=> $comment->is_updated,
                'images'     => $comment->images ?? [],
                'reply'      => $comment->reply ?? null,
                'reply_at'      => $comment->reply_at ?? null,
                'updated_at' => $comment->updated_at,
            ];
        });

        return response()->json($reviews, 200);
    }


    // Xử lý việc che tên
    private function maskEmail($email)
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '[Ẩn]';
        }

        [$name, $domain] = explode('@', $email);
        $nameMasked = substr($name, 0, 3) . str_repeat('*', max(0, strlen($name) - 3));

        return $nameMasked . '@' . $domain;
    }
    //
    public function getReviewDashboard($productId)
    {
        $query = Comment::where('product_id', $productId)->where('is_active', true);

        $ratingStats = $query->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        $averageRating = round($query->avg('rating'), 1);
        $totalReviews = $query->count();
        $withImages = $query->whereNotNull('images')->where('images', '!=', '[]')->count();
        $hiddenCount = Comment::where('product_id', $productId)
            ->where('is_active', false)
            ->whereNotNull('hidden_reason')
            ->count();
        return response()->json([
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'ratings' => [
                5 => $ratingStats[5] ?? 0,
                4 => $ratingStats[4] ?? 0,
                3 => $ratingStats[3] ?? 0,
                2 => $ratingStats[2] ?? 0,
                1 => $ratingStats[1] ?? 0,
            ],
            'with_images' => $withImages,
            'hidden_comments' => $hiddenCount
        ]);
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'order_id' => 'required|integer|exists:orders,id',
    //             'order_item_id' => 'required|integer|exists:order_items,id',
    //             'rating' => 'required|integer|min:1|max:5',
    //             'content' => 'required|string|min:5|max:3000',
    //             'images' => 'nullable|array',
    //             'images.*' => 'string|url',
    //         ]);

    //         $user = auth('sanctum')->user();

    //         // Lấy đơn hàng và item
    //         $order = Order::findOrFail($request->order_id);
    //         $orderItem = OrderItem::where('order_id', $request->order_id)
    //             ->where('id', $request->order_item_id)
    //             ->first();

    //         if (!$orderItem) {
    //             return response()->json([
    //                 'message' => 'Không tìm thấy sản phẩm trong đơn hàng'
    //             ], 404);
    //         }

    //         // Kiểm tra quyền đánh giá
    //         if ($order->user_id !== $user->id) {
    //             return response()->json([
    //                 'message' => 'Bạn không có quyền đánh giá sản phẩm này'
    //             ], 403);
    //         }

    //         // Kiểm tra trạng thái đơn hàng
    //         if (!in_array($order->status->code, ['completed', 'closed'])) {
    //             return response()->json([
    //                 'message' => 'Bạn chỉ có thể đánh giá khi đơn hàng đã hoàn thành'
    //             ], 400);
    //         }

    //         // Kiểm tra đã đánh giá chưa
    //         $existingReview = Comment::where('order_id', $request->order_id)
    //             ->where('order_item_id', $request->order_item_id)
    //             ->first();

    //         if ($existingReview) {
    //             if ($existingReview->is_updated) {
    //                 return response()->json([
    //                     'message' => 'Bạn đã chỉnh sửa đánh giá, không thể cập nhật thêm'
    //                 ], 400);
    //             }

    //             // Cho phép chỉnh sửa 1 lần
    //             $existingReview->update([
    //                 'rating' => $request->rating,
    //                 'content' => $request->content,
    //                 'images' => $request->images,
    //                 'is_updated' => true,
    //             ]);

    //             $this->updateProductAverageRating($orderItem->product_id);
    //             return response()->json([
    //                 'message' => 'Đã cập nhật đánh giá thành công',
    //                 'data' => $existingReview
    //             ]);
    //         }

    //         // Tạo đánh giá mới
    //         $review = Comment::create([
    //             'order_id' => $order->id,
    //             'order_item_id' => $orderItem->id,
    //             'product_id' => $orderItem->product_id,
    //             'user_id' => $user->id,
    //             'rating' => $request->rating,
    //             'content' => $request->content,
    //             'images' => $request->images,
    //             'is_active' => true,
    //             'is_updated' => false
    //         ]);

    //         // Cập nhật điểm trung bình của sản phẩm
    //         $this->updateProductAverageRating($orderItem->product_id);

    //         // Gửi event thông báo có đánh giá mới
    //         event(new NewCommentSubmitted($review));

    //         return response()->json([
    //             'message' => 'Đánh giá thành công',
    //             'data' => $review
    //         ], 201);

    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => 'Có lỗi xảy ra',
    //             'error' => $th->getMessage()
    //         ], 500);
    //     }
    // }

    // private function updateProductAverageRating($productId)
    // {
    //     $avgRating = Comment::where('product_id', $productId)
    //         ->where('is_active', true)
    //         ->avg('rating');

    //     \App\Models\Product::where('id', $productId)
    //         ->update(['avg_rating' => round($avgRating, 1)]);
    // }
}
