<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                    'value' => $attributeString
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
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Chưa đăng nhập'], 401);
            }

            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart) {
                return response()->json(['message' => 'Giỏ hàng trống!'], 404);
            }


            // Lấy danh sách các sản phẩm trong giỏ hàng, kèm theo thông tin về variation và product
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->with(['variation.product:id,name,main_image'])
                ->get();

            $variations = $cartItems->map(function ($cartItem) {
                $imageUrl = $cartItem->variation->variant_image
                    ? Product::getConvertImage($cartItem->variation->library->url, 100, 100, 'thumb')
                    : Product::getConvertImage($cartItem->variation->product->library->url, 100, 100, 'thumb');

                return [
                    'cart_item_id' => $cartItem->id,
                    'id' => $cartItem->variation->id,
                    'product_id' => $cartItem->variation->product_id,
                    'sku' => $cartItem->variation->sku,
                    'weight' => $cartItem->variation->weight,
                    'variant_image' => $cartItem->variation->variant_image,
                    'regular_price' => $cartItem->variation->regular_price,
                    'sale_price' => $cartItem->variation->sale_price,
                    'stock_quantity' => $cartItem->variation->stock_quantity,
                    'quantity' => $cartItem->quantity,
                    'image_url' => $imageUrl,
                    'name' => $cartItem->variation->product->name,
                    'value' => implode(' - ', $cartItem->variation->attributeValues->pluck('name')->toArray()),
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

    public function removeItem($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Chưa đăng nhập'], 401);
            }
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'message' => 'Không tìm thấy cart',
                ], 404);
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'message' => 'Không tìm thấy item cần xóa',
                ], 404);
            }

            $cartItem->delete();

            return response()->json([
                'message' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function changeQuantity()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Chưa đăng nhập'], 401);
            }
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'message' => 'Không tìm thấy giỏ hàng của bạn',
                ], 404);
            }

            $variationId = request('variation_id');
            $newQuantity = request('quantity');
            if ($newQuantity < 1) {
                return response()->json([
                    'message' => 'Số lượng phải lớn hơn bằng 1',
                ], 400);
            }
            $updateItem = CartItem::where('cart_id', $cart->id)
                ->where('variation_id', $variationId)
                ->first();

            if (!$updateItem) {
                return response()->json([
                    'message' => 'Không timg thấy sản phẩm cần update',
                ], 404);
            }

            // Đảm bảo số lượng hợp lệ (ít nhất là 1)
            if ($newQuantity < 1) {
                return response()->json([
                    'message' => 'Invalid quantity',
                ], 400);
            }

            $updateItem->quantity = $newQuantity;
            $updateItem->save();

            return response()->json([
                'message' => 'Thành công',
                'data' => $updateItem
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function syncCart(Request $request)
    {
        try {
            $user = Auth::user(); // lấy ra user
            if (!$user) {
                return response()->json(['error' => 'Chưa đăng nhập'], 401);
            }

            $cartItems = $request->input('cart', []);

            if (empty($cartItems)) {
                return response()->json(['message' => 'Đồng bộ giỏ hàng thành công!'], 200); // nếu đẩy lên mảng rỗng thì ngừng đồng bộ
            }

            DB::beginTransaction();

            //kiểm tra giỏ hàng, nếu chưa có thì tạo mới
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            //Lấy danh sách các varition id hợp lệ hợp lệ
            $validVariations = ProductVariation::whereIn('id', array_column($cartItems, 'variation_id'))
                ->pluck('id')
                ->toArray();

            $invalidVariations = []; // Lưu danh sách sản phẩm không hợp lệ

            foreach ($cartItems as $item) {
                $variationId = $item['variation_id'];
                $quantity = $item['quantity'];

                // lấy ra các v_id ko hợp lệ
                if (!in_array($variationId, $validVariations)) {
                    $invalidVariations[] = $variationId;
                    continue; // Nếu ko hợp lệ thì đến với vòng lặp foreach mới
                }

                //Kiểm tra sản phẩm đã có trong giỏ hàng chưa
                $cartItem = CartItem::where('cart_id', $cart->id)
                    ->where('variation_id', $variationId)
                    ->first();

                if ($cartItem) { // nếu có rồi thì tăng số lkuongw
                    $cartItem->increment('quantity', $quantity);
                } else { // Nếu chưa thì tạo mới
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'variation_id' => $variationId,
                        'quantity' => $quantity
                    ]);
                }
            }

            DB::commit();

            //Nếu có sản phẩm không hợp lệ thông báo lại nhưng vanax tgheem các sp hợp lệ vào dtb
            if (!empty($invalidVariations)) {
                return response()->json([
                    'message' => 'Đồng bộ giỏ hàng thành công, nhưng một số sản phẩm không hợp lệ đã bị bỏ qua.',
                    'invalid_variations' => $invalidVariations
                ], 200);
            }
            // Nếu ko có sp nào ko hợp lệ thì thông báo thành công
            return response()->json(['message' => 'Đồng bộ giỏ hàng thành công!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi đồng bộ giỏ hàng', 'errors' => $e->getMessage()], 500);
        }
    }
}
