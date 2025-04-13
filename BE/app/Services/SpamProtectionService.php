<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SpamBlacklist;
use App\Models\SpamLog;

class SpamProtectionService
{
    //
    public static function isBanned(): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->user()->id;

        return SpamBlacklist::where(function ($q) use ($ip, $userId) {
            $q->where(function ($q) use ($ip) {
                $q->where('type', 'ip')->where('value', $ip);
            })->orWhere(function ($q) use ($userId) {
                $q->where('type', 'user')->where('value', $userId);
            });
        })->where(function ($q) {
            $q->whereNull('until')->orWhere('until', '>', now());
        })->exists();
    }

    public static function banUser($userId, $reason = null, $minutes = null)
    {
        SpamBlacklist::updateOrCreate([
            'type' => 'user',
            'value' => $userId,
        ], [
            'reason' => $reason,
            'until' => $minutes ? now()->addMinutes($minutes) : null,
        ]);
    }

    public static function banIp($ip, $reason = null, $minutes = null)
    {
        SpamBlacklist::updateOrCreate([
            'type' => 'ip',
            'value' => $ip,
        ], [
            'reason' => $reason,
            'until' => $minutes ? now()->addMinutes($minutes) : null,
        ]);
    }

    public static function checkSpamAndAutoBan(): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->user()->id;

        // 1. Đặt đơn không thanh toán
        $unpaidOrders = Order::query()
            ->where('payment_method', 'online')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(60));

        if ($userId) {
            $unpaidOrders->where('user_id', $userId);
        } else {
            $unpaidOrders->where('ip_address', $ip);
        }

        if ($unpaidOrders->count() >= 3) {
            self::banIp($ip, 'Spam đặt đơn online không thanh toán', 720);
            if ($userId) {
                self::banUser($userId, 'Spam đặt đơn online không thanh toán', 720);
            }
            return false;
        }

        // 2. Hủy đơn hàng liên tục
        if ($userId) {
            $cancelledOrders = SpamLog::query()
                ->where('action', 'cancel')
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subMinutes(60))
                ->count();

            if ($cancelledOrders >= 3) {
                self::banUser($userId, 'Hủy đơn hàng liên tục', 360);
                return false;
            }
        }

        // 3. Đặt hàng liên tục
        $recentOrders = Order::query()
            ->where('created_at', '>=', now()->subMinutes(10));

        if ($userId) {
            $recentOrders->where('user_id', $userId);
        } else {
            $recentOrders->where('ip_address', $ip);
        }

        if ($recentOrders->count() >= 5) {
            self::banIp($ip, 'Đặt hàng liên tục', 180);
            if ($userId) {
                self::banUser($userId, 'Đặt hàng liên tục', 180);
            }
            return false;
        }

        return true;
    }

    public static function banUserWithLevels($userId, $baseReason, $action)
    {
        $banCount = SpamBlacklist::where('type', 'user')
            ->where('value', $userId)
            ->where('reason', 'like', "%$action%")
            ->count();

        if ($banCount === 0) {
            self::banUser($userId, "$baseReason (Lần 1)", 180); // 3 giờ
        } elseif ($banCount === 1) {
            self::banUser($userId, "$baseReason (Lần 2)", 720); // 12 giờ
        } else {
            self::banUser($userId, "$baseReason (Lần 3 - Vĩnh viễn)", null); // ban vĩnh viễn
        }
    }
}
