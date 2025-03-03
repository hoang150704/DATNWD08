<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GhnSetting;
use App\Models\Order;
use App\Models\SettingGhn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\ApiService;
use Carbon\Carbon;

class GhnTrackingController extends Controller
{
    protected $ApiService;
    public function __construct(ApiService $ApiService)
    {
        $this->ApiService = $ApiService;
    }

    public function getFeeAndTimeTracking(Request $request)
    {
        try {
            //code...
            $data = $request->validate(
                [
                    'to_district_id' => 'required',
                    'to_ward_code' => 'required',
                    'weight' => 'required',
                ]
            );
            $setting_ghn = GhnSetting::first();
            $dataGetTime = [
                'to_ward_code' => $data['to_ward_code'],
                'to_district_id' => $data['to_district_id'],
                'service_type_id' => $setting_ghn->service_type_id,
                1
            ];
            //Lấy setiing của shoop

            $weight = $data['weight'] + $setting_ghn->weight_box;
            $dataGetFee = array_merge($dataGetTime, ['weight' => $weight]);
            $responseTime = $this->ApiService->post('/shiip/public-api/v2/shipping-order/leadtime', $dataGetTime, ['ShopId' => 195780]);
            $responseFee = $this->ApiService->post('/shiip/public-api/v2/shipping-order/fee', $dataGetFee, ['ShopId' => 195780]);
            //
            if ($responseTime['code'] == 200 && isset($responseTime['data']['leadtime_order'])) {
                $fromDate = Carbon::parse($responseTime['data']['leadtime_order']['from_estimate_date'])->format('Y-m-d');
                $toDate = Carbon::parse($responseTime['data']['leadtime_order']['to_estimate_date'])->format('Y-m-d');
            } else {
                $fromDate = null;
                $toDate = null;
            }

            // 

            if ($responseFee['code'] == 200 && isset($responseFee['data']['total'])) {
                $totalFee = $responseFee['data']['total'];
            } else {
                $totalFee = null;
            }
            //

            return response()->json([
                'time' => [
                    'from_estimate_date' => $fromDate,
                    'to_estimate_date' => $toDate
                ],
                'fee' => $totalFee
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi',
                'errors'  => $th->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function postOrderGHN($id)
    {
        $customHeaders = [
            'offset'=>0,
            'limit'=>200,
            'client_phone'=>""
        ];
        $allShop = json_decode($this->ApiService->get('/shiip/public-api/v2/shop/all',[],$customHeaders));

        // Lấy ra thông tin shop
        
        $order = Order::with('items')->findOrFail(1);


        return response()->json($order);
    }
 
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
