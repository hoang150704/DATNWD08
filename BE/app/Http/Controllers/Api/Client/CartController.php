<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function getVariation()
    {
        try {
            $variation_id = request('variation_id'); // Đảm bảo là mảng hoặc một danh sách id
            $variation = ProductVariation::whereIn('id', $variation_id)
                ->with('product:id,name,main_image')
                ->get();

            // Duyệt qua từng variation và thêm hình ảnh vào mảng
            $variation->each(function ($variation) use (&$images) {
                // Nếu có ảnh biến thể, sử dụng ảnh đó, nếu không lấy ảnh chính của sản phẩm
                $variation->image = $variation->variant_image ?? $variation->product->main_image;
            });

            return response()->json([
                'message' => 'Success',
                'data' => $variation,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
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
