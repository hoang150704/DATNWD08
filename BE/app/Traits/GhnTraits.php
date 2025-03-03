<?php

namespace App\Traits;

trait GhnTraits
{
    public function convertAddress($address)
    {
        $addressParts = explode(',', $address);

        $street = trim($addressParts[0] ?? '');
        $ward = trim($addressParts[1] ?? '');
        $district = trim($addressParts[2] ?? '');
        $province = trim($addressParts[3] ?? '');

        return [
            'street' => $street,
            'ward' => $ward,
            'district' => $district,
            'province' => $province,
        ];
    }
}
