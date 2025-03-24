<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            [
                "name" => "Áo Polo Nam Họa Tiết Bo Dệt Geometric Retro Form Regular",
                "main_image" => 6, 
                "slug" => "ao-polo-nam-hoa-tiet-bo-det-geometric-retro-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Áo Polo Nam Họa Tiết Sọc Ngang Elegance Form Regular",
                "main_image" => 13, 
                "slug" => "ao-polo-nam-hoa-tiet-soc-ngang-elegance-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Áo Polo Nam Họa Tiết Vivid Layers Form Regular",
                "main_image" => 18, 
                "slug" => "ao-polo-nam-hoa-tiet-vivid-layers-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Áo Polo Nam Phối Màu Shade Flow Form Regular",
                "main_image" => 28, 
                "slug" => "ao-polo-nam-phoi-mau-shade-flow-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Áo Polo Nam Phối Viền Sọc Trắng Catalyst Innovations Form Regular",
                "main_image" => 33,
                "slug" => "ao-polo-nam-phoi-vien-soc-trang-catalyst-innovations-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Áo Polo Nam Trơn Logo Basic Modern Lifestyle Form Regular",
                "main_image" => 37, 
                "slug" => "ao-polo-nam-tron-logo-basic-modern-lifestyle-form-regular",
                "type" => "1",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Quần Short Denim Nam Carpenter Utility Work Form Regular",
                "main_image" => 44, 
                "slug" => "quan-short-denim-nam-carpenter-utility-work-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Quần Short Denim Nam Túi Hộp Street Flex Form Baggy",
                "main_image" => 50, 
                "slug" => "quan-short-denim-nam-tui-hop-street-flex-form-baggy",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Quần Short Jean Nam Torn Wash Form Slim",
                "main_image" => 59, 
                "slug" => "quan-short-jean-nam-torn-wash-form-slim",
                "type" => "1",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Quần Short Kaki Nam Fundamental Form Regular",
                "main_image" => 61, 
                "slug" => "quan-short-kaki-nam-fundamental-form-regular",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
            [
                "name" => "Quần Short Tây Nam Comfort Waistband Form Slim-fit",
                "main_image" => 67, 
                "slug" => "quan-short-tay-nam-comfort-waistband-form-slim-fit",
                "type" => "0",
                "rating" => "0",
                "created_at" => now(),
                "updated_at" => now(),
                "deleted_at" => null,
            ],
        ]);
    }
}
