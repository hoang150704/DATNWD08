<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProductVariation
{
    //
    public function checkVariantUsedInActiveOrders(array $variantIds): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_variation_id', $variantIds)
            ->whereIn('orders.order_status_id', [1, 2, 3, 4, 6, 7]) // danh sách trạng thái còn hoạt động
            ->distinct()
            ->pluck('order_items.product_variation_id')
            ->toArray();
    }
}