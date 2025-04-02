<?php

namespace App\Enums;

class OrderStatusEnum
{
    //
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const SHIPPING = 'shipping';
    public const COMPLETED = 'completed';
    public const CLOSED = 'closed';
    public const RETURN_REQUESTED = 'return_requested';
    public const RETURN_APPROVED = 'return_approved';
    public const REFUNDED = 'refunded';
    public const CANCELLED = 'cancelled';
}