<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{

    protected function search($keyword = null, $rating = null, $isActive = null)
    {
        try {
            $query = Comment::query();

            if ($keyword) {
                $query->where('content', 'like', "%{$keyword}%");
            }

            if ($rating) {
                $query->where('rating', $rating);
            }

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            return $query
                ->with(['user:id,name', 'product:id,name',])
                ->paginate(10);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ], 404);
        }
    }


    public function index()
    {
        try {
            $keyword = request('keyword');
            $rating = request('rating');

            $comments = $this->search($keyword, $rating, 1);

            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 404);
        }
    }

    public function show(Comment $comment)
    {
        try {
            $comment = Comment::with(['user:id,name', 'product:id,name'])->findOrFail($comment->id);

            return response()->json([
                'message' => 'Success',
                'data' => $comment
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 404);
        }
    }

    public function destroy()
    {
        try {
            $id = request()->id;
            Comment::whereIn('id', $id)->delete();

            return response()->json([
                'message' => 'Success',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 404);
        }
    }

    public function reply()
    {
        try {
            $id = request()->id;
            $replyContent = request()->reply;

            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json([
                    'message' => 'Comment không tồn tại.'
                ], 404);
            }

            if (empty($replyContent)) {
                return response()->json([
                    'message' => 'Reply không được để trống.'
                ], 400);
            }

            if (!empty($comment->reply)) {
                return response()->json([
                    'message' => 'Comment này đã được phản hồi.'
                ], 400);
            }

            $comment->reply = $replyContent;
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


    public function statusToggle()
    {
        try {
            $id = request()->id;
            $status = request()->status;

            Comment::whereIn('id', $id)->update(['is_active' => $status]);
            return response()->json([
                'message' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json();
        }
    }

    public function hiddenComment()
    {
        try {
            $keyword = request('keyword');
            $rating = request('rating');

            $comments = $this->search($keyword, $rating, 0);

            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 404);
        }
    }

}
