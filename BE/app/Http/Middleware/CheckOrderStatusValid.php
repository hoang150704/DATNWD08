<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Services\OrderStatusFlowService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrderStatusValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $order = Order::findOrFail($request->route('order'));
        $toStatusCode = $request->input('to_status');

        if (!OrderStatusFlowService::canChange($order, $toStatusCode)) {
            return response()->json([
                'error' => 'Không thể chuyển trạng thái từ ' . $order->status->code . ' sang ' . $toStatusCode
            ], 400);
        }

        return $next($request);
    }
}
