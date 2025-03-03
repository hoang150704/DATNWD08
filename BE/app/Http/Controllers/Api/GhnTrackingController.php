<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GhnSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SettingGhn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\ApiService;
use App\Traits\GhnTraits;
use Carbon\Carbon;

class GhnTrackingController extends Controller
{
    use GhnTraits;
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
    public function postOrderGHN(Request $request, $id)
    {
        //Lấy 
        $setting_ghn = GhnSetting::first(); // Lấy setting ghn
        
        $dataShop  = [
            'offset' => 1,
            'limit' => 200,
            'client_phone'=>''
        ];
        $responseShop = json_decode($this->ApiService->post('/shiip/public-api/v2/shop/all',$dataShop,[]));
        $infoShop = $this->covertInfoShop($responseShop,$setting_ghn->shop_id);
        // Lấy ra thông tin shop

        $order = Order::with('items')->findOrFail($id);
        $addressConvert = $this->convertAddress($order->o_address);
        $totalWeight = OrderItem::where('order_id', $id)
            ->selectRaw('SUM(weight * quantity) as total_weight')
            ->value('total_weight');
        $finalWeight = (int) $totalWeight + $setting_ghn->weight_box;
        $dataValidated = $request->validate(
            [
                'insurance_value' => 'nullable|integer|min:0|max:5000000',
                'note' => 'nullable|string|max:5000',
                'note' => 'nullable|string|max:2000',
                'payment_type_id' => 'required|integer|in:1,2',
                'required_note' => 'required|string|in:CHOTHUHANG,CHOXEMHANGKHONGTHU,KHONGCHOXEMHANG',
                'length' => 'required_if:service_type_id,2|integer|min:1|max:200',
                'width' => 'required_if:service_type_id,2|integer|min:1|max:200',
                'height' => 'required_if:service_type_id,2|integer|min:1|max:200',
            ]
        );

        $data = [
            "from_name"=> $infoShop['name'],
            "from_phone"=> $infoShop['name'],
            "from_address"=> $infoShop['address'],
            "from_ward_name"=> "Phường 14",
            "from_district_name"=> "Quận 10",
            "from_province_name"=> "HCM",
            'insurance_value' => $dataValidated['insurance_value'] ?? 0,
            'note' => $dataValidated['note'] ?? "",
            'content' => $dataValidated['content'] ?? "",
            'payment_type_id' => $dataValidated['payment_type_id'],
            'required_note' => $dataValidated['required_note'],
            'length' => $dataValidated['length'],
            'width' => $dataValidated['width'],
            'height' => $dataValidated['height'],
            'client_order_code' => $order->code,
            "to_name" => $order->o_name,
            "to_phone" => $order->o_phone,
            "to_address" => $order->o_address,
            "to_ward_name" => $addressConvert['ward'],
            "to_district_name" => $addressConvert['district'],
            "to_province_name" => $addressConvert['province'],
            "cod_amount" => $order->final_amount,
            "weight" =>  (int) $finalWeight,
            "service_type_id" => $setting_ghn->service_type_id

        ];
        $customHeaders = [
            "ShopId" => $setting_ghn->shop_id,
        ];
        $order_items = OrderItem::where('order_id', $id)->get()->toArray();
        $convertedItems = array_map(function ($item) {
            return [
                "name" => $item["product_name"],
                "quantity" => $item["quantity"]
            ];
        }, $order_items);
        $data['items'] = $convertedItems;
        $postOrder = $this->ApiService->post('/shiip/public-api/v2/shipping-order/create', $data, $customHeaders);
        return response()->json($data);
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
