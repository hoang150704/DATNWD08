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

    public function covertInfoShop($response, $id)
    {
        if ($response['code'] == 200 && isset($response['data']['shops'])) {
            $foundShops = array_filter($response['data']['shops'], function ($shop) use ($id) {
                return $shop['_id'] == $id;
            });

            $foundShop = reset($foundShops) ?? null; // lấy phần từ đầu tieen
            if(!$foundShop){
                return null;
            }else{
                return
                [
                    'name' => $foundShop['name'],
                    'phone' => $foundShop['phone'],
                    'address' => $foundShop['address'],
                    'ward_code' => $foundShop['ward_code'],
                    'district_id' => $foundShop['district_id'],
                    'client_id' => $foundShop['client_id']
                ];
            }
        }
    }
}
