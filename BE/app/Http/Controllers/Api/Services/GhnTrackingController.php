<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Jobs\CompleteOrderJob;
use App\Models\GhnSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderStatusLog;
use App\Models\PaymentStatus;
use App\Models\SettingGhn;
use App\Models\Shipment;
use App\Models\ShippingLog;
use App\Models\ShippingStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\ApiService;
use App\Services\GhnApiService;
use App\Services\ShippingStatusMapper;
use App\Traits\GhnTraits;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GhnTrackingController extends Controller
{
    use GhnTraits;
    protected $ApiService;
    protected $ghnApiService;
    protected $shopId;
    
    protected $weight_service = 20000;
    public function __construct(ApiService $ApiService, GhnApiService $ghnApiService)
    {
        $this->ApiService = $ApiService;
        $this->ghnApiService = $ghnApiService;
        $this->shopId = config('services.ghn.shop_id');
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

            $dataGetTime = [
                'to_ward_code' => $data['to_ward_code'],
                'to_district_id' => $data['to_district_id'],
                'service_type_id' => 2
            ];

            $weight = $data['weight'];
            $dataGetFee = array_merge($dataGetTime, ['weight' => $weight]);
            $responses = $this->ApiService->postAsyncMultiple([
                'time' => [
                    'endpoint' => '/shiip/public-api/v2/shipping-order/leadtime',
                    'data' => $dataGetTime,
                    'headers' => ['ShopId' => $this->shopId]
                ],
                'fee' => [
                    'endpoint' => '/shiip/public-api/v2/shipping-order/fee',
                    'data' => $dataGetFee,
                    'headers' => ['ShopId' => $this->shopId]
                ]
            ]);

            $responseTime = $responses['time'];
            $responseFee = $responses['fee'];
            //
            if ($responseTime['code'] == 200 && isset($responseTime['data']['leadtime_order'])) {
                $fromDate = Carbon::parse($responseTime['data']['leadtime_order']['from_estimate_date'])->format('d-m-y');
                $toDate = Carbon::parse($responseTime['data']['leadtime_order']['to_estimate_date'])->format('d-m-y');
            } else {
                $fromDate = null;
                $toDate = null;
            }
            if ($responseFee['code'] == 200 && isset($responseFee['data']['total'])) {
                $totalFee = $responseFee['data']['total'];
            } else {
                $totalFee = null;
            }
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
        // Lấy thông tin order
        $order = Order::with('items', 'shipment')->findOrFail($id);
        //kiểm tra xem có thêm được không
        if($order->order_status_id != 2){
            return response()->json(['message' => 'Không thể tạo đơn hàng ở trạng thái này'], 400);
        }
        // Setup data lấy tt shop
        $dataShop  = [
            'offset' => 1,
            'limit' => 200,
            'client_phone' => ''
        ];
        // Lấy thông tin shop
        $responses = $this->ApiService->postAsyncMultiple([
            'shop_info' => [
                'endpoint' => '/shiip/public-api/v2/shop/all',
                'data' => $dataShop,
                'headers' => []
            ]
        ]);

        $responseShop = $responses['shop_info'];
        // COnvert thông tin
        $infoShop = $this->covertInfoShop($responseShop, $this->shopId);
        // convert địa chỉ
        $convertAddressShop = $this->convertAddress($infoShop['address']);
        // Lấy ra thông tin shop

        // Convert địa chỉ
        $addressConvert = $this->convertAddress($order->o_address);
        // Tính cân nặng
        $totalWeight = OrderItem::where('order_id', $id)
            ->selectRaw('SUM(weight * quantity) as total_weight')
            ->value('total_weight');
        $finalWeight = (int) $totalWeight;
        $service_type_id = $finalWeight < $this->weight_service ? 2 : 5;
        //1 là người bán gửi
        // 2 là người nhận gửi
        $payment_type_id = $order->payment_method == 'vnpay' ? 1 : 2;
        $codAmount = $order->payment_method == 'vnpay' ? 0 : $order->final_amount;
        $dataValidated = $request->validate(
            [
                'note' => 'nullable|string|max:5000',
                'content' => 'nullable|string|max:2000',
                'required_note' => 'required|string|in:CHOTHUHANG,CHOXEMHANGKHONGTHU,KHONGCHOXEMHANG',
            ]
        );

        $data = [
            "return_phone" => $infoShop['phone'],
            "return_address" => $infoShop['address'],
            "return_district_id" => $infoShop['district_id'],
            "return_ward_code" => $infoShop['ward_code'],
            "from_name" => $infoShop['name'],
            "from_phone" => $infoShop['phone'],
            "from_address" => $infoShop['address'],
            "from_ward_name" => $convertAddressShop['ward'],
            "from_district_name" => $convertAddressShop['district'],
            "from_province_name" => $convertAddressShop['province'],
            'note' => $dataValidated['note'] ?? "",
            'content' => $dataValidated['content'] ?? "",
            'payment_type_id' => $payment_type_id,
            'required_note' => $dataValidated['required_note'],
            'client_order_code' => $order->code,
            "to_name" => $order->o_name,
            "to_phone" => $order->o_phone,
            "to_address" => $order->o_address,
            "to_ward_name" => $addressConvert['ward'],
            "to_district_name" => $addressConvert['district'],
            "to_province_name" => $addressConvert['province'],
            "cod_amount" => $codAmount,
            "weight" =>  (int) $finalWeight,
            "service_type_id" => $service_type_id

        ];
        $customHeaders = [
            "ShopId" => $this->shopId,
        ];
        $order_items = OrderItem::where('order_id', $id)->get()->toArray();
        $convertedItems = array_map(function ($item) {
            return [
                "name" => $item["product_name"],
                "quantity" => $item["quantity"]
            ];
        }, $order_items);
        $data['items'] = $convertedItems;
        $responses = $this->ApiService->postAsyncMultiple([
            'create_order' => [
                'endpoint' => '/shiip/public-api/v2/shipping-order/create',
                'data' => $data,
                'headers' => $customHeaders
            ]
        ]);
        
        $postOrder = $responses['create_order'];
        Log::info($postOrder);
        if ($postOrder['code'] == 200) {
            $mappedShippingStatus = ShippingStatusMapper::toShipping('ready_to_pick');
            $mappedShippingStatusId = ShippingStatus::idByCode($mappedShippingStatus);
            $order->shipment->update([
                'shipping_code'         => $postOrder['data']['order_code'],
                'shipping_status_id'    => $mappedShippingStatusId,
                'shipping_fee_details'  => json_encode($postOrder['data']['fee'])
            ]);
            //
            $order->update([
                'shipping_status_id' => $mappedShippingStatusId,
            ]);
            // Tạo bản ghi log
            ShippingLog::create([
                'shipment_id'       => $order->shipment->id,
                'ghn_status'        => 'ready_to_pick',
                'mapped_status_id'  => $mappedShippingStatusId,
                'location'          => null,
                'note'              => 'Tạo đơn GHN thành công',
                'timestamp'         => now(),
            ]);
        }
        return response()->json($postOrder);
    }

    /**
     * Display the specified resource.
     */
    public function cancelOrderGhn(Request $request)
    {
        $result = $this->ghnApiService->cancelOrder(['LB7TUG']);
        return response()->json($result, 200);
    }
    //callback webhook
    public function callBackWebHook(Request $request)
    {
        $data = $request->all();
        $type = strtolower($data['Type'] ?? '');
        $orderCodeGhn = $data['OrderCode'] ?? null;
        $ghnStatus = $data['Status'] ?? null;
        
        if (!$orderCodeGhn || !in_array($type, ['create', 'switch_status'])) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ'], 200);
        }

        $shipment = Shipment::where('shipping_code', $orderCodeGhn)->first();
        if (!$shipment || !$shipment->order) {
            Log::warning("GHN Webhook: Không tìm thấy shipment với mã đơn $orderCodeGhn");
            return response()->json(['message' => 'Không tìm thấy shipment tương ứng'], 200);
        }

        $order = $shipment->order;

        // Mapping trạng thái
        $shippingCode = ShippingStatusMapper::toShipping($ghnStatus);
        $orderCodeMapped = ShippingStatusMapper::toOrder($shippingCode);
        //Nếu trạng thái là đã giao thì gọi job
        if($ghnStatus == 'delivered'){
            CompleteOrderJob::dispatch($order->id)->delay(now()->addMinutes(2));
        }
        // Cập nhật trạng thái vận chuyển
        if ($shippingCode) {
            $shippingStatus = ShippingStatus::where('code', $shippingCode)->first();
            if ($shippingStatus && $shipment->shipping_status_id !== $shippingStatus->id) {
                $shipment->shipping_status_id = $shippingStatus->id;
                $shipment->save();
            }
            if ($order->shipping_status_id !== $shippingStatus->id) {
                $order->shipping_status_id = $shippingStatus->id;
                $order->save();
            }
        }

        // Cập nhật trạng thái đơn hàng
        if ($orderCodeMapped) {
            $orderStatus = OrderStatus::where('code', $orderCodeMapped)->first();
            if ($orderStatus && $order->order_status_id !== $orderStatus->id) {
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status_id' => $order->order_status_id,
                    'to_status_id' => $orderStatus->id,
                    'changed_by' => 'system',
                ]);
                $order->order_status_id = $orderStatus->id;
                $order->save();
            }
        }

        // Ghi log trạng thái vận chuyển
        ShippingLog::create([
            'shipment_id' => $shipment->id,
            'ghn_status' => $ghnStatus,
            'mapped_status_id' => $shippingStatus->id ?? null,
            'location' => $data['Warehouse'] ?? null,
            'note' => $data['Description'] ?? null,
            'timestamp' => Carbon::parse($data['Time'] ?? now()),
        ]);
        // Nếu giao hàng thanh công thì cập nhật transaction
        if (
            $ghnStatus === 'delivered' &&
            $order->payment_method === 'ship_cod' &&
            $order->paymentStatus->code === 'unpaid'
        ) {
            // Tạo transaction thanh toán ship_cod
            Transaction::create([
                'order_id' => $order->id,
                'method' => 'ship_cod',
                'type' => 'payment',
                'amount' => $order->final_amount,
                'status' => 'success',
                'note' => 'Giao hàng thành công - GHN thu hộ',
            ]);

            // Cập nhật trạng thái thanh toán
            $order->update([
                'payment_status_id' => PaymentStatus::idByCode('paid'),
            ]);
        }

        return response()->json(['message' => 'Xử lý webhook thành công'], 200);
    }
}
