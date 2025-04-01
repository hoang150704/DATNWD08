<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Config;

class GhnApiService
{
    protected $client;
    protected $baseUrl;
    protected $token;
    protected $shopId;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = config('services.ghn.url');
        $this->token = config('services.ghn.token');
        $this->shopId = config('services.ghn.shop_id');
    }

    protected function headers(array $customHeaders = []): array
    {
        return array_merge([
            'Token' => $this->token,
            'ShopId' => $this->shopId,
            'Content-Type' => 'application/json',
        ], $customHeaders);
    }

    public function createOrder(array $data): array
    {
        return $this->post('/v2/shipping-order/create', $data);
    }

    public function cancelOrder(array $orderCodes): array
    {
        return $this->post('/shiip/public-api/v2/switch-status/cancel', [
            'order_codes' => $orderCodes
        ]);
    }
    public function reShipOrder(array $orderCodes): array
    {
        return $this->post('/shiip/public-api/v2/switch-status/cancel', [
            'order_codes' => $orderCodes
        ]);
    }

    public function getLeadTime(array $data): array
    {
        return $this->post('/v2/shipping-order/leadtime', $data);
    }

    public function getFee(array $data): array
    {
        return $this->post('/v2/shipping-order/fee', $data);
    }

    public function getShops(): array
    {
        return $this->post('/v2/shop/all', [
            'offset' => 1,
            'limit' => 200,
            'client_phone' => ''
        ]);
    }

    protected function post(string $endpoint, array $data, array $headers = []): array
    {
        try {
            $res = $this->client->post($this->baseUrl . $endpoint, [
                'headers' => $this->headers($headers),
                'json' => $data
            ]);

            return json_decode($res->getBody(), true);
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'GHN API error',
                'error' => $e->getMessage()
            ];
        }
    }
}
