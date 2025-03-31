<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('product_attributes')->insert([
            [
                "product_id" => 1,
                "attribute_id" => 1,
                "attribute_value_id" => 17,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "attribute_id" => 1,
                "attribute_value_id" => 18,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "attribute_id" => 2,
                "attribute_value_id" => 19,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "attribute_id" => 2,
                "attribute_value_id" => 20,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "attribute_id" => 2,
                "attribute_value_id" => 21,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "attribute_id" => 2,
                "attribute_value_id" => 22,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "attribute_id" => 1,
                "attribute_value_id" => 17,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "attribute_id" => 1,
                "attribute_value_id" => 25,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "attribute_id" => 2,
                "attribute_value_id" => 19,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 3,
                "attribute_id" => 1,
                "attribute_value_id" => 17,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 3,
                "attribute_id" => 1,
                "attribute_value_id" => 25,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 3,
                "attribute_id" => 2,
                "attribute_value_id" => 19,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 3,
                "attribute_id" => 2,
                "attribute_value_id" => 20,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 3,
                "attribute_id" => 2,
                "attribute_value_id" => 21,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 4,
                "attribute_id" => 1,
                "attribute_value_id" => 17,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 4,
                "attribute_id" => 1,
                "attribute_value_id" => 25,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 4,
                "attribute_id" => 2,
                "attribute_value_id" => 20,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 4,
                "attribute_id" => 2,
                "attribute_value_id" => 21,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 5,
                "attribute_id" => 1,
                "attribute_value_id" => 25,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 5,
                "attribute_id" => 1,
                "attribute_value_id" => 26,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 5,
                "attribute_id" => 2,
                "attribute_value_id" => 20,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 5,
                "attribute_id" => 2,
                "attribute_value_id" => 21,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 7,
                "attribute_id" => 1,
                "attribute_value_id" => 30,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 7,
                "attribute_id" => 1,
                "attribute_value_id" => 31,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 7,
                "attribute_id" => 2,
                "attribute_value_id" => 1,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 7,
                "attribute_id" => 2,
                "attribute_value_id" => 2,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 8,
                "attribute_id" => 1,
                "attribute_value_id" => 32,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 8,
                "attribute_id" => 2,
                "attribute_value_id" => 21,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 10,
                "attribute_id" => 1,
                "attribute_value_id" => 18,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 10,
                "attribute_id" => 2,
                "attribute_value_id" => 1,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 10,
                "attribute_id" => 2,
                "attribute_value_id" => 2,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 11,
                "attribute_id" => 1,
                "attribute_value_id" => 17,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 11,
                "attribute_id" => 1,
                "attribute_value_id" => 36,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [

                "product_id" => 11,
                "attribute_id" => 1,
                "attribute_value_id" => 37,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }
}
