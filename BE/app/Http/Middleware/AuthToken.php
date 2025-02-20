<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     // Lấy token từ header
    //     $token = $request->header('Authorization');

    //     // Kiểm tra token có tồn tại không
    //     if (!$token || !Token::where('token', $token)->exists()) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     return $next($request);
    // }
}
