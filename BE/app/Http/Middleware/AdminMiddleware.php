<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Kiểm tra người dùng đã đăng nhập chưa
        if (!$user) {
            return response()->json(['message' => 'Bạn chưa đăng nhập!'], 401);
        }

        // Nếu là staff đang cố gắng truy cập dashboard, từ chối luôn
        if ($user->role === 'staff' && $request->is('api/admin/dashboard')) {
            return response()->json(['message' => 'Staff không có quyền truy cập dashboard!'], 403);
        }
        return $next($request);
    }
}
