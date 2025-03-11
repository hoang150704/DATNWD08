<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dữ liệu mẫu cho product_variations
        $variations = [
            ['product_id' => 1, 'regular_price' => 300000, 'weight' => 450, 'sale_price' => 199000, 'stock_quantity' => 100],
            ['product_id' => 2, 'regular_price' => 300000, 'weight' => 500, 'sale_price' => 189000, 'stock_quantity' => 90],
            ['product_id' => 3, 'regular_price' => 350000, 'weight' => 550, 'sale_price' => 249000, 'stock_quantity' => 80],
            ['product_id' => 4, 'regular_price' => 350000, 'weight' => 600, 'sale_price' => 239000, 'stock_quantity' => 75],
            ['product_id' => 5, 'regular_price' => 400000, 'weight' => 650, 'sale_price' => 299000, 'stock_quantity' => 60],
            ['product_id' => 6, 'regular_price' => 400000, 'weight' => 700, 'sale_price' => 289000, 'stock_quantity' => 55],
            ['product_id' => 7, 'regular_price' => 450000, 'weight' => 750, 'sale_price' => 349000, 'stock_quantity' => 50],
            ['product_id' => 8, 'regular_price' => 450000, 'weight' => 800, 'sale_price' => 339000, 'stock_quantity' => 45],
            ['product_id' => 9, 'regular_price' => 500000, 'weight' => 850, 'sale_price' => 399000, 'stock_quantity' => 40],
            ['product_id' => 10, 'regular_price' => 500000, 'weight' => 900, 'sale_price' => 389000, 'stock_quantity' => 35],
        ];

        // Thêm product variations vào bảng
        foreach ($variations as $variation) {
            DB::table('product_variations')->insert([
                'product_id' => $variation['product_id'],
                'sku' => $this->generateSku($variation['product_id']),  // Tạo mã SKU cho từng sản phẩm
                'variant_image' => null,
                'weight'=>$variation['weight'],
                'regular_price' => $variation['regular_price'],
                'weight' => $variation['weight'],
                'sale_price' => $variation['sale_price'],
                'stock_quantity' => $variation['stock_quantity'],
            ]);
        }
    }
    /**
     * Hàm tạo mã SKU cho sản phẩm dựa trên product_id.
     *
     * @param int $productId
     * @return string
     */
    private function generateSku(int $productId): string
    {
        // Tạo SKU phức tạp hơn với một tiền tố ngẫu nhiên
        return 'SKU-' . strtoupper(Str::random(5)) . $productId;
    }
}
