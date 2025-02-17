<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {
        try {
            $comments = Comment::where('is_active', 1)
                // ->with('user')
                ->paginate(10);

            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ]);
        }
    }

    public function show(Comment $comment)
    {
        try {
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
            return response()->json([
                'message' => 'Failed'
            ]);
        }
    }

    public function hiddenComment()
    {
        try {
            $comments = Comment::where('is_active', '0')->paginate(10);
            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ], 404);
        }
    }

    public function search()
    {
        try {
            $keyword = request()->keyword;
            $rating = request()->rating;
            if ($keyword && $rating) {
                $comments = Comment::where('content', 'like', "%{$keyword}%")->where('rating', $rating)->paginate(10);
            } else {
                if ($keyword) {
                    $comments = Comment::where('content', 'like', "%{$keyword}%")->paginate(10);
                } else {
                    $comments = Comment::where('rating', $rating)->paginate(10);
                }
            }
            return response()->json([
                'message' => 'Success',
                'data' => $comments
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ], 404);
        }
    }
}
