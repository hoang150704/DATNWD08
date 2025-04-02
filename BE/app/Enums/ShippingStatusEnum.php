<?php

namespace App\Enums;

class ShippingStatusEnum
{
    //
    public const NOT_CREATED = 'not_created';
    public const CREATED = 'created';
    public const PICKED = 'picked';
    public const DELIVERING = 'delivering';
    public const DELIVERED = 'delivered';
    public const RETURNED = 'returned';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
}