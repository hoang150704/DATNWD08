<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\SettingGhn;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Library::class,
            UserSeeder::class,
            CategorySeeder::class,
            AttributeSeeder::class,
            AttributeValueSeeder::class,
            ProductSeeder::class,
            ProductCategoryRelationSeeder::class,
            ProductImageSeeder::class,
            ProductVariationSeeder::class,
            ProductVariationValueSeeder::class,
            ProductAttributeSeeder::class,
            VoucherSeeder::class,
            VoucherUsageSeeder::class,
            CommentSeeder::class,
            OrderSeeder::class,
            OrderHistorySeeder::class,
            OrderItemSeeder::class,
            StatusTrackingSeeder::class,
            StatusPaymentSeeder::class,
            SettingGhnSeeder::class,
          
        ]);
    }
}
