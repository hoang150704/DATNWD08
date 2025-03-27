<?php

namespace App\Traits;

trait MaskableTraits
{
    public function maskPhone($phone)
    {
        return substr($phone, 0, 4) . 'xxxx' . substr($phone, -2);
    }

    public function maskEmail($email)
    {
        $parts = explode('@', $email);
        $name = substr($parts[0], 0, 4);
        return $name . '****@' . $parts[1];
    }
}
