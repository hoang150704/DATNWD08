<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SpamBlacklist;
use App\Models\SpamLog;
use Illuminate\Support\Facades\Log;

class SpamProtectionService
{
    public static function isBanned(): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();

        return SpamBlacklist::where(function ($query) use ($ip, $userId) {
            $query->where(function ($q) use ($ip) {
                $q->where('type', 'ip')->where('value', $ip);
            });

            if ($userId) {
                $query->orWhere(function ($q) use ($userId) {
                    $q->where('type', 'user')->where('value', $userId);
                });
            }
        })->where(function ($query) {
            $query->whereNull('until')->orWhere('until', '>', now());
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

    public static function banSmartly($reason = null, $minutes = null)
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();

        self::banIp($ip, $reason, $minutes);

        if ($userId) {
            self::banUser($userId, $reason, $minutes);
        }
    }

    public static function checkSpamAndAutoBan(): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();

        // 1. Đặt đơn online không thanh toán
        $unpaidOrders = Order::query()
            ->where('payment_method', 'online')
            ->where('payment_status_id', 1) // unpaid
            ->where('order_status_id', 1)   // pending
            ->where('created_at', '>=', now()->subMinutes(60));

        if ($userId) {
            $unpaidOrders->where('user_id', $userId);
        } else {
            $unpaidOrders->where('ip_address', $ip);
        }

        if ($unpaidOrders->count() >= 3) {
            self::banSmartly('Spam đặt đơn online không thanh toán', 720);
            return false;
        }

        // 2. Huỷ đơn liên tục (xử lý ở nơi gọi dịch vụ huỷ đơn)

        // 3. Đặt đơn liên tục trong thời gian ngắn
        $recentOrders = Order::query()
            ->where('created_at', '>=', now()->subMinutes(10));

        if ($userId) {
            $recentOrders->where('user_id', $userId);
        } else {
            $recentOrders->where('ip_address', $ip);
        }

        if ($recentOrders->count() >= 5) {
            self::banSmartly('Đặt đơn liên tục trong thời gian ngắn', 180);
            return false;
        }

        return true;
    }

    public static function logAndCheckBan(string $action, int $limit, int $minutes, int $banMinutes, string $reason)
    {
        $userId = auth('sanctum')->id();
        $ip = request()->ip();

        SpamLog::create([
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
            'data' => null,
            'created_at' => now(),
        ]);

        $count = SpamLog::where('action', $action)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->where(function ($q) use ($ip, $userId) {
                $q->where('ip', $ip);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })->count();

        if ($count >= $limit) {
            self::banSmartly($reason, $banMinutes);
            return true;
        }

        return false;
    }

    public static function checkAndBanByLevels(string $action, int $limit, int $minutes, array $levels): bool
    {
        $userId = auth('sanctum')->id();
        $ip = request()->ip();

        SpamLog::create([
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
            'created_at' => now(),
        ]);

        $count = SpamLog::where('action', $action)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        if ($count >= $limit) {
            SpamLog::create([
                'action' => $action . '_violation',
                'user_id' => $userId,
                'ip' => $ip,
                'created_at' => now(),
            ]);

            $violationCount = SpamLog::where('action', $action . '_violation')
                ->where('user_id', $userId)
                ->count();

            $duration = $levels[$violationCount] ?? null;

            if ($duration !== null) {
                self::banSmartly("Vi phạm $action lần thứ $violationCount", $duration);
            } else {
                self::banSmartly("Vi phạm $action nhiều lần - BAN VĨNH VIỄN", null);
            }

            return true;
        }

        return false;
    }
}
