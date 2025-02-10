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
            ['public_id'=>'ovmdtlu6ihcldyx9jckg'],
            ['public_id'=>'fwuyeublz9dda716tfpi'],
            ['public_id'=>'wjhxgmfpytbtvbfne5yu'],
            ['public_id'=>'yq6mviubta0ujkpngjyr'],
            ['public_id'=>'qjzs2nnqfcj2dqns4mx9'],
        ]);
    }
}
