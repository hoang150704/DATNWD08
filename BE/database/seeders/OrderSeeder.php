<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 100; $i++) {
            DB::table('orders')->insert([
                'user_id' => rand(1, 20),
                'code' => 'DH!' . Carbon::now()->subDays(rand(0, 30))->timestamp,
                'total_amount' => rand(1000, 1000000),
                'discount_amount' => rand(500, 500000),
                'final_amount' => rand(800, 800000),
                'payment_method' => collect(['ship_cod', 'bank_transfer', 'e-wallets'])->random(),
                'shipping' => rand(1000, 200000),
                'o_name' => fake()->userName,
                'o_address' => fake()->address,
                'o_phone' => fake()->phoneNumber,
                'o_mail' => fake()->email,
                'stt_track' => rand(1, 7),
                'stt_payment' => rand(1, 2),
                'created_at' => fake()->dateTime,
            ]);
        }
    }
}
