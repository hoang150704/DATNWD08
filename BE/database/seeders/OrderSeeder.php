<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\OrderStatus;
use App\Models\PaymentStatus;
use App\Models\ShippingStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();
        $variations = ProductVariation::all();

        // Load status IDs by code
        $orderStatusMap = OrderStatus::pluck('id', 'code');
        $paymentStatusMap = PaymentStatus::pluck('id', 'code');
        $shippingStatusMap = ShippingStatus::pluck('id', 'code');

        $closedStatusId = $orderStatusMap['closed'] ?? null;
        $cancelledStatusId = $orderStatusMap['cancelled'] ?? null;
        $paidStatusId = $paymentStatusMap['paid'] ?? null;
        $deliveredStatusId = $shippingStatusMap['delivered'] ?? null;

        if (($products->isEmpty() && $variations->isEmpty()) || !$closedStatusId) {
            return;
        }

        // Guest names for non-registered orders
        $guestNames = [
            'Nguyễn Văn An','Trần Thị Bình','Lê Hữu Dũng','Phạm Ngọc Hạnh',
            'Hoàng Minh Huy','Huỳnh Thị Lan','Phan Văn Long','Vũ Thị Mai',
            'Võ Hữu Nam','Đặng Thị Ngọc','Bùi Văn Phúc','Đỗ Thị Quỳnh',
            'Hồ Minh Sơn','Ngô Thị Thanh','Dương Văn Toàn','Lý Thị Trang',
            'Nguyễn Hữu Tùng','Trần Ngọc Uyên','Lê Văn Vinh','Phạm Thị Yến',
            'Hoàng Đức Anh','Huỳnh Gia Bảo','Phan Nhật Cường','Vũ Xuân Duy',
            'Võ Thị Hoa','Đặng Văn Kiên','Bùi Thị Lệ','Đỗ Nhật Minh',
            'Hồ Hữu Nghĩa','Ngô Minh Phương','Dương Thị Quân','Lý Văn Quý',
            'Nguyễn Ngọc Rạng','Trần Thị Sang','Lê Văn Thành','Phạm Thị Uyên',
            'Hoàng Văn Việt','Huỳnh Hữu Xuyên','Phan Thị Yến','Vũ Văn Bình',
            'Võ Minh Châu','Đặng Nhật Hào','Bùi Thị Kim','Đỗ Hữu Lâm',
            'Hồ Ngọc My','Ngô Văn Nam','Dương Nhật Quang','Lý Hữu Sinh',
            'Nguyễn Thị Trang','Trần Văn Đạt',
        ];

        // Tạo 3000 đơn hàng từ 2023 đến nay
        for ($i = 0; $i < 3000; $i++) {
            // Xác định thời gian tạo đơn hàng
            $createdAt = null;

            // 2 tháng gần nhất sẽ có khoảng 300 đơn hàng
            if ($i < 300) {
                // Phân bố đều trong 2 tháng gần nhất
                $daysAgo = rand(0, 60);
                $createdAt = Carbon::now()->subDays($daysAgo);
            } else {
                // Các đơn hàng còn lại phân bố từ 2023 đến 2 tháng trước
                $startDate = Carbon::create(2023, 1, 1);
                $endDate = Carbon::now()->subDays(61);
                $createdAt = Carbon::instance(fake()->dateTimeBetween($startDate, $endDate));
            }

            // Determine registered user or guest (10% guest)
            $eligibleUserIds = User::whereNotIn('id', [1,2,3])->pluck('id')->toArray();
            if ($i % 10 === 0) {
                $userId = null;
                $oName = $guestNames[array_rand($guestNames)];
                $oEmail = Str::slug($oName, '_') . '@example.com';
            } else {
                $userId = fake()->randomElement($eligibleUserIds);
                $user = User::find($userId);
                $oName = $user->name;
                $oEmail = $user->email;
            }

            // Shipping address and fee
            $provinces = ['Hà Nội','Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ'];
            $districts = ['Quận 1','Quận 3','Ba Đình','Thanh Xuân','Ngũ Hành Sơn','Ninh Kiều'];
            $wards = ['Phường 1','Phường 2','Phường 10','Phường Hòa Cường','Phường Trà Nóc'];
            $streets = ['Nguyễn Trãi','Lê Lợi','Trần Hưng Đạo','Hoàng Diệu','Phan Đình Phùng'];

            $oAddress = sprintf(
                "%d %s, %s, %s, %s",
                rand(1,300),
                fake()->randomElement($streets),
                fake()->randomElement($wards),
                fake()->randomElement($districts),
                fake()->randomElement($provinces)
            );
            $oPhone = fake()->phoneNumber;
            $shipFee = 20500;

            // Điều chỉnh tỷ lệ trạng thái đơn hàng
            $orderStatusCode = null;
            $cancelReason = null;
            $cancelBy = null;

            // Lấy danh sách các trạng thái có sẵn
            $availableStatuses = array_keys($orderStatusMap->toArray());

            if (fake()->boolean(80)) { // 80% đơn hàng closed
                $orderStatusCode = 'closed';
            } else {
                // Chỉ chọn từ các trạng thái có sẵn, loại bỏ 'closed' vì đã xử lý riêng
                $otherStatuses = array_filter($availableStatuses, function($status) {
                    return $status !== 'closed';
                });
                $orderStatusCode = fake()->randomElement($otherStatuses);
            }
            $orderStatusId = $orderStatusMap[$orderStatusCode];

            // Điều chỉnh trạng thái thanh toán và vận chuyển dựa trên trạng thái đơn hàng
            if ($orderStatusCode === 'closed') {
                $paymentStatusId = $paidStatusId;
                $shippingStatusId = $deliveredStatusId;
                $completedAt = $createdAt->copy()->addDays(rand(1,7));
                $updateTimestamp = $completedAt;
            } elseif ($orderStatusCode === 'cancelled') {
                $paymentStatusId = fake()->randomElement($paymentStatusMap->values()->toArray());
                $shippingStatusId = fake()->randomElement($shippingStatusMap->values()->toArray());
                $cancelBy = fake()->name;
                $cancelReason = fake()->sentence;
                $cancelledAt = $createdAt->copy()->addDays(rand(0,3));
                $updateTimestamp = $cancelledAt;
                $completedAt = null;
            } else {
                $paymentStatusId = fake()->randomElement($paymentStatusMap->values()->toArray());
                $shippingStatusId = fake()->randomElement($shippingStatusMap->values()->toArray());
                $updateTimestamp = $createdAt;
                $completedAt = null;
            }

            // Create order
            $order = Order::create([
                'user_id' => $userId,
                'code' => 'ORD-' . strtoupper(Str::random(10)),
                'total_amount' => 0,
                'discount_amount' => 0,
                'final_amount' => 0,
                'payment_method' => fake()->randomElement(['ship_cod','vnpay']),
                'shipping' => $shipFee,
                'o_name' => $oName,
                'o_address' => $oAddress,
                'o_phone' => $oPhone,
                'o_mail' => $oEmail,
                'note' => fake()->boolean(30) ? fake()->sentence : null,
                'order_status_id' => $orderStatusId,
                'payment_status_id' => $paymentStatusId,
                'shipping_status_id' => $shippingStatusId,
                'cancel_reason' => $cancelReason,
                'cancel_by' => $cancelBy,
                'created_at' => $createdAt,
                'updated_at' => $updateTimestamp,
                'completed_at' => $completedAt,
            ]);

            // Build order items
            $itemCount = rand(1,4);
            $items = [];
            $itemsTotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                if ($variations->isNotEmpty() && fake()->boolean(70)) {
                    $variation = $variations->random();
                    $product = $variation->product;
                    $price = $variation->regular_price;
                    $weight = $variation->weight;
                    $variationData = [
                        'sku' => $variation->sku,
                        'weight' => $variation->weight,
                        'variant_image' => $variation->variant_image,
                        'regular_price' => $variation->regular_price,
                        'sale_price' => $variation->sale_price,
                        'stock_quantity' => $variation->stock_quantity,
                    ];
                    $image = $variation->variant_image ?: $product->main_image;
                    $variationId = $variation->id;
                } else {
                    $product = $products->random();
                    $firstVar = $variations->firstWhere('product_id', $product->id);
                    if ($firstVar) {
                        $price = $firstVar->regular_price;
                        $weight = $firstVar->weight;
                        $variationData = [
                            'sku' => $firstVar->sku,
                            'weight' => $firstVar->weight,
                            'variant_image' => $firstVar->variant_image,
                            'regular_price' => $firstVar->regular_price,
                            'sale_price' => $firstVar->sale_price,
                            'stock_quantity' => $firstVar->stock_quantity,
                        ];
                        $image = $firstVar->variant_image ?: $product->main_image;
                        $variationId = $firstVar->id;
                    } else {
                        $price = 0;
                        $weight = 0;
                        $variationData = null;
                        $image = $product->main_image;
                        $variationId = null;
                    }
                }

                $quantity = rand(1,3);
                $total = $price * $quantity;
                $itemsTotal += $total;

                $items[] = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variation_id' => $variationId,
                    'weight' => $weight,
                    'variation' => $variationData ? json_encode($variationData, JSON_UNESCAPED_UNICODE) : null,
                    'image' => $image,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'created_at' => $createdAt,
                    'updated_at' => $updateTimestamp,
                ];
            }

            OrderItem::insert($items);

            // Apply random discount 5-10% on 20% of orders
            $discount = fake()->boolean(20) ? intval($itemsTotal * fake()->randomFloat(2, 0.05, 0.1)) : 0;

            // Update final totals and updated_at if not closed/cancelled
            $order->update([
                'total_amount' => $itemsTotal,
                'discount_amount' => $discount,
                'final_amount' => $itemsTotal - $discount + $shipFee,
                'updated_at' => $updateTimestamp,
            ]);

            // Add reviews for orders with user_id and order_status_id 4 or 5
            if ($userId !== null && in_array($orderStatusId, [4, 5])) {
                // Get all items for this order
                $orderItems = OrderItem::where('order_id', $order->id)->get();

                foreach ($orderItems as $item) {
                    // 80% chance to create a review for each item
                    if (fake()->boolean(80)) {
                        // Generate rating with higher probability for 5 stars
                        $rating = fake()->randomElement([
                            5, 5, 5, 5, 5,  // 50% chance for 5 stars
                            4, 4, 4,        // 30% chance for 4 stars
                            3, 3,           // 15% chance for 3 stars
                            2,              // 3% chance for 2 stars
                            1               // 2% chance for 1 star
                        ]);

                        // Generate review content based on rating
                        $content = match($rating) {
                            5 => fake()->randomElement([
                                'Sản phẩm chất lượng tuyệt vời, đóng gói cẩn thận!',
                                'Rất hài lòng với sản phẩm, giá cả hợp lý.',
                                'Sản phẩm đúng như mô tả, giao hàng nhanh chóng.',
                                'Chất lượng sản phẩm tốt, sẽ ủng hộ shop dài dài.',
                                'Sản phẩm đẹp, chất lượng cao cấp.'
                            ]),
                            4 => fake()->randomElement([
                                'Sản phẩm tốt, giá cả phải chăng.',
                                'Chất lượng ổn, giao hàng đúng hẹn.',
                                'Sản phẩm đẹp, đúng như mô tả.',
                                'Hài lòng với trải nghiệm mua sắm.',
                                'Sản phẩm tốt, nhưng có thể cải thiện thêm.'
                            ]),
                            3 => fake()->randomElement([
                                'Sản phẩm tạm được, giá hơi cao.',
                                'Chất lượng ổn nhưng chưa thực sự xuất sắc.',
                                'Sản phẩm đúng như mô tả nhưng có thể tốt hơn.',
                                'Tạm chấp nhận được, không quá xuất sắc.',
                                'Sản phẩm bình thường, không có gì đặc biệt.'
                            ]),
                            2 => fake()->randomElement([
                                'Chất lượng không như mong đợi.',
                                'Sản phẩm có vấn đề nhỏ cần cải thiện.',
                                'Giá hơi cao so với chất lượng.',
                                'Không thực sự hài lòng với sản phẩm.',
                                'Có thể cải thiện thêm về chất lượng.'
                            ]),
                            1 => fake()->randomElement([
                                'Chất lượng kém, không đáng tiền.',
                                'Sản phẩm không như mô tả.',
                                'Rất thất vọng với chất lượng.',
                                'Không nên mua sản phẩm này.',
                                'Chất lượng quá tệ, không đáng giá.'
                            ])
                        };

                        // 30% chance to have images
                        $images = fake()->boolean(30) ? json_encode([
                            'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1742457318/prc8cyqzrpb5xvvjvclg.jpg',
                            'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1742457321/gjrgnvhk3ozaef4lggma.jpg'
                        ]) : null;

                        // 20% chance to have admin reply
                        $hasReply = fake()->boolean(20);
                        $reply = $hasReply ? fake()->randomElement([
                            'Cảm ơn bạn đã đánh giá sản phẩm của chúng tôi!',
                            'Chúng tôi rất vui khi nhận được phản hồi tích cực từ bạn.',
                            'Cảm ơn sự ủng hộ của bạn. Chúng tôi sẽ cố gắng phục vụ tốt hơn.',
                            'Chúng tôi rất tiếc về trải nghiệm không tốt của bạn. Chúng tôi sẽ cải thiện.',
                            'Cảm ơn phản hồi của bạn. Chúng tôi sẽ xem xét và cải thiện sản phẩm.'
                        ]) : null;
                        $replyAt = $hasReply ? $createdAt->copy()->addDays(rand(1, 3)) : null;

                        // Create the review
                        \App\Models\Comment::create([
                            'order_id' => $order->id,
                            'order_item_id' => $item->id,
                            'product_id' => $item->product_id,
                            'user_id' => $userId,
                            'rating' => $rating,
                            'content' => $content,
                            'images' => $images,
                            'reply' => $reply,
                            'reply_at' => $replyAt,
                            'is_active' => true,
                            'is_updated' => false,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);

                        // Update product average rating
                        $this->updateProductAverageRating($item->product_id);
                    }
                }
            }
        }
    }

    private function updateProductAverageRating($productId)
    {
        // Calculate average rating from all active reviews
        $avgRating = \App\Models\Comment::where('product_id', $productId)
            ->where('is_active', true)
            ->avg('rating');

        // Round to 1 decimal place
        $avgRating = round($avgRating, 1);

        // Update products table
        \Illuminate\Support\Facades\DB::table('products')
            ->where('id', $productId)
            ->update(['avg_rating' => $avgRating]);
    }
}