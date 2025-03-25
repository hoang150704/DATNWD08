<?php
namespace App\Services;

class ShippingStatusMapper
{
    protected const GHN_TO_SHIPPING = [
        'ready_to_pick' => 'created',
        'picked' => 'picked',
        'delivering' => 'delivering',
        'delivered' => 'delivered',
        'delivery_fail' => 'failed',
        'returned' => 'returned',
        'cancel' => 'cancelled',
    ];

    protected const SHIPPING_TO_ORDER = [
        'created' => 'confirmed',
        'picked' => 'shipping',
        'delivering' => 'shipping',
        'delivered' => 'completed',
        'returned' => 'refunded',
        'failed' => 'shipping',
        'cancelled' => 'cancelled',
    ];

    public static function toShipping(string $ghnStatus): ?string
    {
        return self::GHN_TO_SHIPPING[$ghnStatus] ?? null;
    }

    public static function toOrder(string $shippingStatus): ?string
    {
        return self::SHIPPING_TO_ORDER[$shippingStatus] ?? null;
    }
}
