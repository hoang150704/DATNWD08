<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SpamProtectionService;

class CheckBlacklist
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (SpamProtectionService::isBanned()) {
            return response()->json([
                'message' => 'Tài khoản hoặc IP của bạn đang bị hạn chế do hành vi bất thường.'
            ], 403);
        }

        return $next($request);
    }
}
