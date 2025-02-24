<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            OrderSeeder::class,
            VoucherSeeder::class,
            VoucherUsageSeeder::class,
            CategorySeeder::class,
            Library::class,
            AttributeSeeder::class,
            AttributeValueSeeder::class,
            ProductSeeder::class,
            ProductCategoryRelationSeeder::class,
            ProductVariationSeeder::class,
            ProductVariationValueSeeder::class,
            ProductAttributeSeeder::class,
            ProductImageSeeder::class,
            ProductAttributeSeeder::class,
            OrderHistorySeeder::class,
            OrderItemSeeder::class,
            OrderSeeder::class,
            StatusTrackingSeeder::class,
            StatusPaymentSeeder::class,
        ]);
    }
}
