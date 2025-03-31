<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils; // Import đúng class Utils để dùng settle()

class ApiService
{
    protected $baseUrl;
    protected $defaultHeaders;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config('services.ghn.url');
        $this->defaultHeaders = [
            'token' => config('services.ghn.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $this->client = new Client();
    }

    /**
     * Gửi nhiều yêu cầu POST bất đồng bộ cùng lúc (Async)
     */
    public function postAsyncMultiple($requests)
    {
        $promises = [];

        foreach ($requests as $key => $request) {
            $headers = array_merge($this->defaultHeaders, $request['headers'] ?? []);
            $promises[$key] = $this->client->postAsync($this->baseUrl . $request['endpoint'], [
                'headers' => $headers,
                'json' => $request['data']
            ]);
        }

        // Sử dụng `Utils::settle()` thay vì `Promise::settle()`
        $responses = Utils::settle($promises)->wait();

        $results = [];
        foreach ($responses as $key => $response) {
            if ($response['state'] === 'fulfilled') {
                $results[$key] = json_decode($response['value']->getBody(), true);
            } else {
                $results[$key] = ['error' => 'API call failed', 'message' => $response['reason']->getMessage()];
            }
        }

        return $results;
    }
}
