<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
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
    //
    public static function banUser($userId, $reason = null, $minutes = null, $action = null)
    {
        SpamBlacklist::updateOrCreate([
            'type' => 'user',
            'value' => $userId,
            'action' => $action,
        ], [
            'reason' => $reason,
            'until' => $minutes ? now()->addMinutes($minutes) : null,
        ]);
    }
    //
    public static function banIp($ip, $reason = null, $minutes = null, $action = null)
    {
        SpamBlacklist::updateOrCreate([
            'type' => 'ip',
            'value' => $ip,
            'action' => $action,
        ], [
            'reason' => $reason,
            'until' => $minutes ? now()->addMinutes($minutes) : null,
        ]);
    }
    //
    public static function banSmartly($reason = null, $minutes = null, $action = null)
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();

        self::banIp($ip, $reason, $minutes, $action);

        if ($userId) {
            self::banUser($userId, $reason, $minutes, $action);
        }
    }
    //
    public static function checkSpamAndAutoBan(): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();
        Log::info('Check spam order_spam');
        // 1. Đặt đơn không thanh toán
        self::checkByConfig('unpaid_order', $ip, $userId);

        // 2. Đặt đơn liên tục
        self::checkByConfig('order_spam', $ip, $userId);

        return true;
    }
    //
    public static function logAndCheckBan(string $action, int $limit, int $minutes, array $banLevels, string $baseReason): bool
    {
        $userId = auth('sanctum')->id();
        $ip = request()->ip();
        if (self::isBannedByAction($action)) {
            return false;
        }
        SpamLog::create([
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
            'created_at' => now(),
        ]);

        $count = SpamLog::where('action', $action)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->where(function ($q) use ($ip, $userId) {
                $q->where('ip', $ip);
                if ($userId) $q->orWhere('user_id', $userId);
            })->count();

        if ($count < $limit) {
            return false;
        }

        $violationCount = SpamBlacklist::where('action', $action)
            ->where(function ($q) use ($ip, $userId) {
                $q->where('value', $ip)->where('type', 'ip');
                if ($userId) $q->orWhere(function ($q2) use ($userId) {
                    $q2->where('value', $userId)->where('type', 'user');
                });
            })->count();

        $duration = $banLevels[$violationCount + 1] ?? null;

        $reason = "$baseReason (Lần " . ($violationCount + 1) . ($duration ? ')' : ' - BAN VĨNH VIỄN)');


        self::banSmartly($reason, $duration, $action);

        return true;
    }
    //
    public static function isBannedByAction(string $action): bool
    {
        $ip = request()->ip();
        $userId = auth('sanctum')->id();

        return SpamBlacklist::where('action', $action)
            ->where(function ($query) use ($ip, $userId) {
                $query->where(function ($q) use ($ip) {
                    $q->where('type', 'ip')->where('value', $ip);
                });

                if ($userId) {
                    $query->orWhere(function ($q) use ($userId) {
                        $q->where('type', 'user')->where('value', $userId);
                    });
                }
            })
            ->where(function ($query) {
                $query->whereNull('until')->orWhere('until', '>', now());
            })
            ->exists();
    }

    //
    protected static function checkByConfig(string $action, string $ip, ?int $userId): void
    {
        $config = config("spam.$action");
        Log::info('order_spam config:', $config);
        if (!$config) {
            return;
        }
        if (self::isLikelyTrustedUser($userId)) {
            return;
        }
        $query = Order::query()
            ->where('created_at', '>=', now()->subMinutes($config['minutes']));

        if ($action === 'unpaid_order') {
            $query->where('payment_method', 'online')
                ->where('payment_status_id', 1)
                ->where('order_status_id', 1);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        if ($query->count() >= $config['limit']) {
            self::logAndCheckBan(
                $action,
                $config['limit'],
                $config['minutes'],
                $config['levels'],
                ucfirst(str_replace('_', ' ', $action))
            );
        }
    }
    //
    public static function isLikelyTrustedUser(?int $userId): bool
    {
        if (!$userId) return false;

        $totalOrders = Order::where('user_id', $userId)->count();
        $cancelledOrders = Order::where('user_id', $userId)
            ->where('order_status_id', OrderStatus::idByCode('cancelled'))
            ->count();

        if ($totalOrders >= 30 && $cancelledOrders / $totalOrders <= 0.05) {
            return true;
        }

        return false;
    }
}
