<?php

namespace App\Http\Controllers\Api\User;

use App\Events\NewCommentSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
                'variation'=> $comment->orderItem->variation,
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
}
