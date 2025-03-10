<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function getVariation(Request $request)
    {
        try {
            $data = $request->json()->all(); /// Lấy tất cả dữu liệu gửi lên
            //
            if (!is_array($data) || empty($data)) {
                return response()->json([
                    'message' => 'Dữ liệu gửi lên không hợp lệ hoặc rỗng'
                ], 400);
            }
            //Tạo mảng các id variant
            $variationIds = array_column($data, 'variant');

            // Đảm bảo là mảng hoặc một danh sách id
            $variations = ProductVariation::whereIn('id', $variationIds)
                ->with('product:id,name,main_image')
                ->get();
            //
            if ($variations->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy sản phẩm nào!',
                ], 404);
            }
            // Duyệt qua từng variation và thêm hình ảnh + quantity  vào mảng
            $variations->transform(function ($variation) use ($data) {
                // Thêm quantity
                $matchedVariant = collect($data)->firstWhere('variant', $variation->id);
                $quantity = $matchedVariant ? $matchedVariant['quantity'] : 1;

                // Xác định link ảnh
                $imageUrl = null;
                if ($variation->variant_image) {
                    $imageUrl = Product::getConvertImage($variation->library->url, 100, 100, 'thumb');
                } elseif ($variation->product->main_image) {
                    $imageUrl = Product::getConvertImage($variation->product->library->url, 100, 100, 'thumb');
                }
                // Xử lí biến thể 
                $attributeValues = $variation->attributeValues->pluck('name')->toArray();
                $attributeString = implode(' - ', $attributeValues);
                // Gán quantity và link ảnh vào kết quả trả về
                $variation->quantity = $quantity;
                $variation->image_url = $imageUrl;

                return [
                    'id' => $variation->id,
                    'product_id' => $variation->product_id,
                    'sku' => $variation->sku,
                    'weight' => $variation->weight,
                    'variant_image' => $variation->variant_image,
                    'regular_price' => $variation->regular_price,
                    'sale_price' => $variation->sale_price,
                    'stock_quantity' => $variation->stock_quantity,
                    'quantity' => $quantity,
                    'image_url' => $imageUrl,
                    'name' => $variation->product->name,
                    'value'=>$attributeString
                ];
            });

            return response()->json([
                'message' => 'Success',
                'data' => $variations,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function index()
    {
        try {
            // Kiểm tra xem người dùng đã đăng nhập chưa
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            // Lấy giỏ hàng của người dùng
            $cartId = Cart::where('user_id', Auth::id())->first();

            // Lấy danh sách các sản phẩm trong giỏ hàng, kèm theo thông tin về variation và product
            $cartItems = CartItem::where('cart_id', $cartId->id)
                ->with(['variation.product:id,name,main_image']) // Eager load để lấy thông tin về sản phẩm và ảnh chính
                ->get();

            // Duyệt qua các cartItems và kiểm tra variation có variant_image hay không
            $cartItems->each(function ($cartItem) {
                // Kiểm tra nếu có ảnh variant_image trong ProductVariation
                $cartItem->image = $cartItem->variation->variant_image ?? $cartItem->variation->product->main_image;
            });

            return response()->json([
                'message' => 'Success',
                'data' => $cartItems,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()  // Trả về thông báo lỗi chi tiết nếu cần
            ], 500);
        }
    }


    public function addCart()
    {
        try {
            $cart = Cart::where('user_id', Auth::id())->first();

            $validated = request()->validate([
                'quantity' => 'required|integer|min:1',
            ], [
                'quantity.min' => 'Số lượng không được nhỏ hơn 1'
            ]);

            $variation = request('variation_id');
            $quantity = request('quantity');

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('variation_id', $variation)
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $quantity;
                $cartItem->save();
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'variation_id' => $variation,
                    'quantity' => $quantity
                ]);
            }

            return response()->json([
                'message' => 'Success',
                'data' => $cartItem
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function removeItem()
    {
        try {
            $cart = Cart::where('user_id', Auth::id())->first();

            $variation = request('variation_id');

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('variation_id', $variation)
                ->first();

            $cartItem->delete();

            return response()->json([
                'message' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }

    public function changeQuantity()
    {
        try {
            $cart = Cart::where('user_id', Auth::id())->first();

            $variation = request('variation_id');
            $change = request('change');

            $updateItem = CartItem::where('cart_id', $cart->id)->where('variation_id', $variation)->first();

            if ($change == "+") {
                $updateItem->quantity += 1;
            }
            if ($change == "-") {
                if ($updateItem->quantity > 1) {
                    $updateItem->quantity -= 1;
                }
            }

            $updateItem->save();

            return response()->json([
                'message' => 'Success',
                'data' => $updateItem
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
            ], 500);
        }
    }
}
