<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Tạo người dùng mẫ
        DB::table('users')->insert([
            [
                'name' => 'Hoàng',
                'username' => 'hoang2k4',
                'email' => 'phuongminhhoang77@gmail.com',
                'avatar' => 'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066958/ovmdtlu6ihcldyx9jckg.jpg',
                'role' => User::ROLE_MEMBER,
                'password' => Hash::make(12345678),
                'email_verified_at' => now()
            ],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'phuongminhhoang777@gmail.com',
                'avatar' => 'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066958/ovmdtlu6ihcldyx9jckg.jpg',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make(12345678),
                'email_verified_at' => now()
            ],
            [
                'name' => 'Lâm Bầu Trời',
                'username' => 'lambautroi',
                'email' => 'lamnh.thfitness@gmail.com',
                'avatar' => 'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066958/ovmdtlu6ihcldyx9jckg.jpg',
                'role' => User::ROLE_STAFF,
                'password' => Hash::make(12345678),
                'email_verified_at' => now()
            ]
        ]);
        //

        $ho = [
            'Nguyễn',
            'Trần',
            'Lê',
            'Phạm',
            'Hoàng',
            'Huỳnh',
            'Phan',
            'Vũ',
            'Võ',
            'Đặng',
            'Bùi',
            'Đỗ',
            'Hồ',
            'Ngô',
            'Dương',
            'Lý'
        ];
        $tenDem = [
            'Văn',
            'Thị',
            'Hữu',
            'Đức',
            'Ngọc',
            'Thanh',
            'Minh',
            'Trung',
            'Gia',
            'Xuân',
            'Quốc',
            'Nhật',
            'Hải',
            'Tuấn',
            'Thành',
            'Anh'
        ];
        $ten = [
            'An',
            'Bình',
            'Chi',
            'Dương',
            'Đạt',
            'Hà',
            'Hạnh',
            'Hiếu',
            'Huy',
            'Khánh',
            'Lan',
            'Linh',
            'Mai',
            'Nam',
            'Nga',
            'Ngân',
            'Phúc',
            'Quang',
            'Sơn',
            'Thảo',
            'Thắng',
            'Trang',
            'Tú',
            'Tùng',
            'Việt',
            'Yến'
        ];

        $startDate = Carbon::create(2023, 1, 1);
        $now = Carbon::now();
        for ($i = 0; $i < 100; $i++) {
            $ten_that = $ho[array_rand($ho)] . ' ' . $tenDem[array_rand($tenDem)] . ' ' . $ten[array_rand($ten)];
            $email = Str::slug($ten_that) . $i . '@example.com';
            $createdAt = $startDate->copy()->addDays(intval(($now->diffInDays($startDate) / 100) * $i));
            $updatedAt = $createdAt->copy()->addDays(rand(0, 10));

            DB::table('users')->insert(
                [
                    'name' => $ten_that,
                    'username' => Str::slug($ten_that).$i,
                    'email' => $email,
                    'avatar' => 'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1739066958/ovmdtlu6ihcldyx9jckg.jpg',
                    'role' => User::ROLE_MEMBER,
                    'password' => Hash::make(12345678),
                    'email_verified_at' => now(),
                    'created_at'=>$createdAt,
                    'updated_at'=>$updatedAt
                ]
            );
        }
    }
}
