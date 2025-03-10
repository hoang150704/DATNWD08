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
            $variation_id = request('variation_id');
            $variation = ProductVariation::where('id', $variation_id)->with('product:id,name,main_image')->first();

            return response()->json([
                'message' => 'Success',
                'data' => $variation
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
            ], 500);
        }
    }

    public function index()
    {
        try {
            if (Auth::check()) {
                return response()->json([
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }
            $cartId = Cart::where('user_id', Auth::id())->first();
            $cartItem = CartItem::where('cart_id', $cartId->id)->with('variation')->get();

            return response()->json([
                'message' => 'Success',
                'data' => $cartItem
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed'
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
