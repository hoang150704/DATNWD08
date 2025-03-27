<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $notifications = Notification::orderByDesc('created_at')->get();
            return response()->json([
                'message' => 'Success',
                'data' => $notifications
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ]);
        }
    }

    public function markAsRead(Notification $notification)
    {
        try {
            $isRead = request('is_read');
            if ($notification->is_read != $isRead) {
                $notification->update(['is_read' => $isRead]);
            } else {
                return response()->json([
                    'message' => 'Trạng thái không hợp lệ'
                ]);
            }
            return response()->json([
                'message' => 'Success',
                'data' => $notification
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ]);
        }
    }
}
