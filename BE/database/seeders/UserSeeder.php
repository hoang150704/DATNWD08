<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo người dùng mẫu
        for ($i = 0; $i < 20; $i++) {
            DB::table('users')->insert([
                'name' => fake()->name,
                'username' => fake()->userName,
                'email' => fake()->email,
                'phone' => '03' . fake()->numerify('########'),
                'avatar' => fake()->imageUrl,
                'role_id' => rand(0, 1),
                'password' => fake()->password,
            ]);
        }
    }
}
