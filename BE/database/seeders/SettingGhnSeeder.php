<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingGhnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('ghn_settings')->insert([
            ['weight_box' => 200,'service_type_id'=>2,'shop_id'=>195780]
        ]);
    }
}
