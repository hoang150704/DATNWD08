<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;

class TransactionFlowService
{

    // Kiểm tra xem có đc tạo bản ghi ko

    public static function canCreate(Order $order, string $type, string $status): bool
    {
        $method = $order->payment_method; // vnpay hoặc ship_cod
        $orderStatus = $order->status->code ?? null; // trạng thái hệ thống

        // Kiểm tra giao dịch tồn tại
        $hasPending = Transaction::where('order_id', $order->id)
            ->where('type', $type)
            ->where('status', 'pending')
            ->exists();

        $hasSuccess = Transaction::where('order_id', $order->id)
            ->where('type', $type)
            ->where('status', 'success')
            ->exists();

        // payment
        if ($type === 'payment') {
            if ($status === 'pending') {
                return !$hasPending && !$hasSuccess;
            }

            if ($status === 'success' || $status === 'failed') {
                return $hasPending && !$hasSuccess;
            }
        }

        // refund 
        if ($type === 'refund') {
            // Nếu là thanh toán online, thì phải thanh toán thành công
            if ($method === 'vnpay' && !$hasSuccess) {
                return false;
            }

            // Nếu là ship_cod thì phải giao hàng thành công (hoặc đơn đã completed)
            if ($method === 'ship_cod' && !in_array($orderStatus, ['completed', 'closed'])) {
                return false;
            }

            // refund: chỉ cho phép 1 pending tại 1 đơn hàng, 1 type payment hay refund , và không có success nếu đang pending
            if ($status === 'pending') {
                return !$hasPending && !$hasSuccess;
            }

            if ($status === 'success' || $status === 'failed') {
                return $hasPending && !$hasSuccess;
            }
        }

        return false;
    }


    // Tạo giao dịch thanh toán hoặc hoàn tiền

    public static function create(array $data): ?Transaction
    {
        $order = $data['order'];
        $type = $data['type'];
        $status = $data['status'];

        if (!self::canCreate($order, $type, $status)) {
            return null;
        }

        return Transaction::create([
            'order_id'           => $order->id,
            'method'             => $data['method'], // 'vnpay' hoặc 'ship_cod'
            'type'               => $type, // payment or rèund
            'amount'             => $data['amount'],
            'status'             => $status, // pending success failled
            'transaction_code'   => $data['transaction_code'] ?? null,
            'vnp_transaction_no' => $data['vnp_transaction_no'] ?? null,
            'vnp_bank_code'      => $data['vnp_bank_code'] ?? null,
            'vnp_pay_date'       => $data['vnp_pay_date'] ?? null,
            'note'               => $data['note'] ?? null,
        ]);
    }
}
