<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReviewController extends Controller
{
    public function getReviewsByProduct($productId)
    {
        // Lấy danh sách bình luận của sản phẩm với user và reply (nếu có)
        $reviews = Comment::where('product_id', $productId)
        ->where('is_active', true)
        ->with(['user:id,name', 'product.variants']) // Thêm product.variants để tránh N+1 query
        ->orderBy('created_at', 'desc')
        ->paginate(5)
        ->through(function ($comment) {
            return [
                'id' => $comment->id,
                'username' => $comment->user ? $this->maskName($comment->user->name) : 'Người dùng ẩn danh',
                'rating' => $comment->rating,
                'variants' => $comment->product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                    ];
                }),
                'content' => $comment->content,
                'created_at' => Carbon::parse($comment->created_at)->format('d/m/Y'),
                'images' => $comment->images ?? [],
                'reply' => $comment->reply ?? null,
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
}
