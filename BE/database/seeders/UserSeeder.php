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
                'avatar' => fake()->imageUrl,
                'role' => User::ROLE_ADMIN || User::ROLE_MEMBER,
                'password' => Hash::make(12345678),
            ]);
        }
    }
}
