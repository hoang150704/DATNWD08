<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GhnWebhookFlowService
{
    protected array $allowedTransitions = [
        'ready_to_pick' => ['picking', 'cancel'],
        'picking' => ['picked', 'cancel'],
        'picked' => ['storing', 'transporting', 'delivering', 'delivery_fail', 'cancel'],
        'storing' => ['transporting', 'delivering', 'delivery_fail'],
        'transporting' => ['storing', 'delivering', 'delivery_fail'],
        'delivering' => ['delivered', 'delivery_fail'],
        'delivery_fail' => ['waiting_to_return'],
        'waiting_to_return' => ['return'],
        'return' => ['return_transporting'],
        'return_transporting' => ['returning'],
        'returning' => ['returned', 'return_fail'],
        'return_fail' => ['returning'],
        // Những trạng thái kết thúc / đặc biệt
        'cancel' => [],
        'delivered' => [],
        'returned' => [],
        'damage' => [],
        'lost' => [],
    ];

    public function handleWebhook(array $data): array
    {
        $type = strtolower($data['Type'] ?? '');
        $orderCode = $data['OrderCode'] ?? null;
        $status = $data['Status'] ?? null;

        if (!$orderCode || !$status || !in_array($type, ['create', 'switch_status'])) {
            return ['error' => 'Invalid data'];
        }

        $shipment = Shipment::where('shipping_code', $orderCode)->first();
        if (!$shipment || !$shipment->order) {
            return ['error' => 'Shipment not found'];
        }

        $order = $shipment->order;
        $currentStatus = $shipment->shippingStatus->code ?? null;

        // Check nếu trạng thái hợp lệ theo luồng
        if ($currentStatus && isset($this->allowedTransitions[$currentStatus])) {
            $allowed = $this->allowedTransitions[$currentStatus];
            if (!in_array($status, $allowed)) {
                return ['error' => "Trạng thái '$status' không hợp lệ từ '$currentStatus'"];
            }
        }

        // Lưu trạng thái shipping mới
        $shippingStatus = ShippingStatus::where('code', $status)->first();
        if ($shippingStatus && $shipment->shipping_status_id !== $shippingStatus->id) {
            $shipment->shipping_status_id = $shippingStatus->id;
            $shipment->save();
        }

        // Ghi log trạng thái shipping
        ShippingLog::create([
            'shipment_id' => $shipment->id,
            'ghn_status' => $status,
            'mapped_status_id' => $shippingStatus->id ?? null,
            'location' => $data['Warehouse'] ?? null,
            'note' => $data['Description'] ?? null,
            'timestamp' => Carbon::parse($data['Time'] ?? now()),
        ]);

        // Cập nhật trạng thái đơn 
        $mappedOrderCode = ShippingStatusMapper::toOrder($status);
        if ($mappedOrderCode) {
            $orderStatus = OrderStatus::where('code', $mappedOrderCode)->first();
            if ($orderStatus && $order->order_status_id !== $orderStatus->id) {
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status_id' => $order->order_status_id,
                    'to_status_id' => $orderStatus->id,
                    'changed_by' => 'system',
                ]);
                $order->order_status_id = $orderStatus->id;
                $order->save();
            }
        }

        return ['message' => 'Webhook processed successfully'];
    }
}
