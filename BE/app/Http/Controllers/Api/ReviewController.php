<?php

namespace App\Http\Controllers\Api;

use App\Events\NewCommentSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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

    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'rating'        => 'required|numeric|min:1|max:5',
            'content'       => 'required|string|min:10|max:500',
            'images'        => 'nullable|array',
            'images.*'      => 'nullable|image|max:2048',
        ]);

        // Lấy user ID từ người dùng đã đăng nhập
        // $userId = auth()->id();
        $userId = 1;

        // Xử lý ảnh: Lưu ảnh vào storage nếu có
        $imagePaths = [];
        if ($request->hasFile('images')) {
            $directory = 'comments';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('comments', 'public');
            }
        }

        // Tạo bình luận
        $comment = Comment::create([
            'product_id'    => $request->product_id,
            'user_id'       => $userId,
            'rating'        => $request->rating,
            'content'       => $request->content,
            'images'        => json_encode($imagePaths),
            'is_active'     => false
        ]);

        // Gửi thông báo đến admin để duyệt bình luận
        event(new NewCommentSubmitted($comment));

        return response()->json([
            'message' => 'Bình luận của bạn đã được gửi thành công!',
            'data' => [
                'id'         => $comment->id,
                'product_id' => $comment->product_id,
                'user_id'    => $comment->user_id,
                'rating'     => $comment->rating,
                'content'    => $comment->content,
                'images'     => json_decode($comment->images), // Giải mã JSON để hiển thị mảng
                'is_active'  => $comment->is_active,
                'created_at' => Carbon::parse($comment->created_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s'),
            ]
        ], 201);
    }
}
