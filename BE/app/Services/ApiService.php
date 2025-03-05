<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiService
{
    protected $baseUrl;
    protected $defaultHeaders;

    public function __construct()
    {
        $this->baseUrl = config('services.ghn.url');
        $this->defaultHeaders = [
            'token' => config('services.ghn.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Gửi yêu cầu GET với Header tùy chỉnh
     */
    public function get($endpoint, $params = [], $customHeaders = [])
    {
        $headers = array_merge($this->defaultHeaders, $customHeaders);

        return Http::withHeaders($headers)
                   ->get($this->baseUrl . $endpoint, $params)
                   ->json();
    }

    /**
     * Gửi yêu cầu POST với Header tùy chỉnh
     */
    public function post($endpoint, $data = [], $customHeaders = [])
    {
        $headers = array_merge($this->defaultHeaders, $customHeaders);

        return Http::withHeaders($headers)
                   ->post($this->baseUrl . $endpoint, $data)
                   ->json();
    }
}
