<?php

namespace App\Http\Middleware;

use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Services\GhnStatusFlowService;
use App\Services\ShippingStatusMapper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckValidShippingStatusFlow
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $data = $request->all();
        $incomingGhnStatus = $data['Status'] ?? null;
        $incomingMappedStatus = ShippingStatusMapper::toShipping($incomingGhnStatus);
        $orderCode = $data['OrderCode'] ?? null;

        if (!$orderCode || !$incomingMappedStatus) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ'], 200);
        }

        // Lấy shipment + order
        $shipment = Shipment::with('order')->where('shipping_code', $orderCode)->first();
        if (!$shipment || !$shipment->order) {
            Log::warning("Webhook GHN: Không tìm thấy shipment với mã $orderCode");
            return response()->json(['message' => 'Không tìm thấy shipment'], 200);
        }

        // Lấy trạng thái ghn mới nhất trong bảng shipping_logs
        $latestLog = ShippingLog::where('shipment_id', $shipment->id)
            ->latest('timestamp')
            ->first();

        $currentGhnStatus = $latestLog?->ghn_status;
    

        // So sánh flow
        if (!GhnStatusFlowService::canChange($currentGhnStatus,  $incomingGhnStatus)) {
            Log::warning("Webhook GHN: Trạng thái không hợp lệ từ [$currentGhnStatus] -> [ $incomingGhnStatus] (GHN: $incomingGhnStatus) | Đơn: {$shipment->order->code}");
            return response()->json(['message' => 'Sai luồng trạng thái GHN'], 200);
        }

        return $next($request);
    }
}
