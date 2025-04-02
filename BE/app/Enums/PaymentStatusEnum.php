<?php

namespace App\Enums;

class PaymentStatusEnum
{
    //
    public const UNPAID = 'unpaid';
    public const PAID = 'paid';
    public const REFUNDED = 'refunded';
    public const CANCELLED = 'cancelled';
}