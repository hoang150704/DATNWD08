<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\ApiService; 
use App\Traits\GhnTraits;
use Carbon\Carbon;

class GhnTrackingController extends Controller
{
    use GhnTraits;

    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function getFeeAndTimeTracking(Request $request)
    {
        try {
            $data = $request->validate([
                'to_district_id' => 'required',
                'to_ward_code' => 'required',
                'weight' => 'required',
            ]);

            $dataGetTime = [
                'to_ward_code' => $data['to_ward_code'],
                'to_district_id' => $data['to_district_id'],
                'service_type_id' => 2,
            ];

            $dataGetFee = array_merge($dataGetTime, ['weight' => $data['weight']]);

            $responses = $this->apiService->postAsyncMultiple([
                'time' => [
                    'endpoint' => '/shiip/public-api/v2/shipping-order/leadtime',
                    'data' => $dataGetTime,
                    'headers' => ['ShopId' => config('services.ghn.shop_id')]
                ],
                'fee' => [
                    'endpoint' => '/shiip/public-api/v2/shipping-order/fee',
                    'data' => $dataGetFee,
                    'headers' => ['ShopId' => config('services.ghn.shop_id')]
                ]
            ]);

            $responseTime = $responses['time'];
            $responseFee = $responses['fee'];

            $fromDate = $toDate = $totalFee = null;

            if ($responseTime['code'] == 200 && isset($responseTime['data']['leadtime_order'])) {
                $fromDate = Carbon::parse($responseTime['data']['leadtime_order']['from_estimate_date'])->format('d-m-y');
                $toDate = Carbon::parse($responseTime['data']['leadtime_order']['to_estimate_date'])->format('d-m-y');
            }
            if ($responseFee['code'] == 200 && isset($responseFee['data']['total'])) {
                $totalFee = $responseFee['data']['total'];
            }

            return response()->json([
                'time' => [
                    'from_estimate_date' => $fromDate,
                    'to_estimate_date' => $toDate
                ],
                'fee' => $totalFee
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi',
                'errors'  => $th->getMessage()
            ]);
        }
    }

    public function postOrderGHN(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string|max:5000',
                'content' => 'nullable|string|max:2000',
                'payment_type_id' => 'required|integer|in:1,2',
                'required_note' => 'required|string|in:CHOTHUHANG,CHOXEMHANGKHONGTHU,KHONGCHOXEMHANG',
            ]);

            $order = Order::with('items')->findOrFail($id);
            $totalWeight = OrderItem::where('order_id', $id)
                ->selectRaw('SUM(weight * quantity) as total_weight')
                ->value('total_weight');

            $order_items = $order->items->map(function ($item) {
                return [
                    "name" => $item->product_name,
                    "quantity" => $item->quantity
                ];
            })->toArray();

            $shopId = config('services.ghn.shop_id');

            $infoShop = $this->getShopInfo($shopId);
            $convertAddressShop = $this->convertAddress($infoShop['address']);
            $addressConvert = $this->convertAddress($order->o_address);

            $data = array_merge($validated, [
                'order_code' => $order->code,
                'order_items' => $order_items,
                'total_weight' => $totalWeight,
                'shop' => $infoShop,
                'shop_address' => $convertAddressShop,
                'to_address' => $addressConvert,
                'cod_amount' => $order->final_amount
            ]);

            $response = $this->apiService->postAsyncMultiple([
                'create_order' => [
                    'endpoint' => '/shiip/public-api/v2/shipping-order/create',
                    'data' => $data,
                    'headers' => ['ShopId' => $shopId]
                ]
            ]);

            return response()->json($response['create_order']);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi tạo đơn hàng GHN',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
