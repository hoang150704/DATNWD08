<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Library extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('libraries')->insert([
            ['public_id'=>'ovmdtlu6ihcldyx9jckg',"url"=>"https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066958/ovmdtlu6ihcldyx9jckg.jpg"],
            ['public_id'=>'fwuyeublz9dda716tfpi',"url"=>"https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066961/fwuyeublz9dda716tfpi.webp"],
            ['public_id'=>'wjhxgmfpytbtvbfne5yu',"url"=>"https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066964/wjhxgmfpytbtvbfne5yu.webp"],
            ['public_id'=>'yq6mviubta0ujkpngjyr',"url"=>"https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066967/yq6mviubta0ujkpngjyr.jpg"],
            ['public_id'=>'qjzs2nnqfcj2dqns4mx9',"url"=>"https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066970/qjzs2nnqfcj2dqns4mx9.jpg"],
        ]);
    }
}
