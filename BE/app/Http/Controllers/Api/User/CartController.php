<?php

namespace App\Http\Controllers\Api\User;

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
            $data = $request->json()->all(); // Lấy tất cả dữ liệu gửi lên

            if (!is_array($data) || empty($data)) {
                return response()->json([], 200);
            }

            // Tạo mảng các id variant
            $variationIds = array_column($data, 'variant');

            // Đảm bảo là mảng hoặc một danh sách id
            $variations = ProductVariation::whereIn('id', $variationIds)
                ->with('product:id,name,main_image', 'library', 'attributeValues')
                ->get();

            if ($variations->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy sản phẩm nào!',
                    'code' => 404
                ], 200);
            }

            // Duyệt qua từng variation và thêm hình ảnh + quantity vào mảng
            $variations->transform(function ($variation) use ($data) {
                // Thêm quantity
                $matchedVariant = collect($data)->firstWhere('variant', $variation->id);
                $quantity = $matchedVariant ? $matchedVariant['quantity'] : 1;

                // Xác định link ảnh
                $imageUrl = null;
                if (!empty($variation->variant_image) && !empty($variation->library)) {
                    $imageUrl = Product::getConvertImage($variation->library->url, 100, 100, 'thumb');
                } elseif (!empty($variation->product->main_image) && !empty($variation->product->library)) {
                    $imageUrl = Product::getConvertImage($variation->product->library->url, 100, 100, 'thumb');
                } else {
                    $imageUrl = 'https://thegioidensanvuon.com/wp-content/uploads/2021/06/den-SLCC22-1.jpg'; // Ảnh mặc định
                }

                // Xử lý biến thể
                $attributeValues = $variation->attributeValues->pluck('name')->toArray();
                $attributeString = implode(' - ', $attributeValues);

                // Gán quantity và link ảnh vào kết quả trả về
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
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Chưa đăng nhập'], 401);
            }

            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart) {
                return response()->json([], 200);
            }

            // Lấy danh sách sản phẩm cả xóa mềm
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->with([
                    'variation' => function ($query) {
                        $query->withTrashed();
                    },
                    'variation.product' => function ($query) {
                        $query->withTrashed();
                    },
                    'variation.library',
                    'variation.attributeValues'
                ])
                ->get();

            $validCartItems = [];

            foreach ($cartItems as $cartItem) {
                $variation = $cartItem->variation;

                // Kiểm tra nếu biến thể hoặc sản phẩm đã bị xóa mềm
                if (!$variation || !$variation->product || $variation->trashed() || $variation->product->trashed()) {
                    // Xóa item khỏi giỏ hàng 
                    $cartItem->delete();
                    continue;
                    //TIếp tục 
                }

                // check số lượng tồn kho 

                if ($variation->stock_quantity < $cartItem->quantity) {
                    // cập nhật số lựng cho = với số lượng trong kho
                    $cartItem->update(['quantity' => $variation->stock_quantity]);
                }

                // Xác định link ảnh
                $imageUrl = null;
                if (!empty($variation->variant_image) && !empty($variation->library)) {
                    $imageUrl = Product::getConvertImage($variation->library->url, 100, 100, 'thumb');
                } elseif (!empty($variation->product->main_image) && !empty($variation->product->library)) {
                    $imageUrl = Product::getConvertImage($variation->product->library->url, 100, 100, 'thumb');
                } else {
                    $imageUrl = 'https://thegioidensanvuon.com/wp-content/uploads/2021/06/den-SLCC22-1.jpg'; // Ảnh mặc định
                }

                $validCartItems[] = [
                    'cart_item_id' => $cartItem->id,
                    'id' => $variation->id,
                    'product_id' => $variation->product_id,
                    'sku' => $variation->sku,
                    'weight' => $variation->weight,
                    'variant_image' => $variation->variant_image,
                    'regular_price' => $variation->regular_price,
                    'sale_price' => $variation->sale_price,
                    'stock_quantity' => $variation->stock_quantity,
                    'quantity' => $cartItem->quantity,
                    'image_url' => $imageUrl,
                    'name' => $variation->product->name,
                    'value' => implode(' - ', $variation->attributeValues->pluck('name')->toArray()),
                ];
            }

            return response()->json([
                'message' => 'Success',
                'data' => $validCartItems,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    // Thêm vào giỏ hàng
    public function addCart()
    {
        try {
            // Kiểm tra người dùng đã đăng nhập chưa
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Bạn chưa đăng nhập!'], 401);
            }
    
            // Validate request (kiểm tra dữ liệu đầu vào)
            request()->validate([
                'variant_id' => 'required|integer|exists:product_variations,id',
                'quantity' => 'required|integer|min:1',
            ], [
                'variant_id.required' => 'Vui lòng chọn sản phẩm.',
                'variant_id.integer' => 'Mã sản phẩm phải là số nguyên.',
                'variant_id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',
                'quantity.required' => 'Số lượng là bắt buộc.',
                'quantity.integer' => 'Số lượng phải là số nguyên.',
                'quantity.min' => 'Số lượng không được nhỏ hơn 1.',
            ]);
    
            $variationId = request('variant_id');
            $newQuantity = request('quantity');
    
            // Tìm biến thể sản phẩm trong database
            $productVariation = ProductVariation::find($variationId);
            if (!$productVariation) {
                return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
            }
    
            // Kiểm tra hoặc tạo giỏ hàng cho user
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
    
            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('variation_id', $variationId)
                ->first();
    
            // Lấy số lượng hiện có trong giỏ hàng
            $existingQuantity = $cartItem ? $cartItem->quantity : 0;
    
            // Tính tổng số lượng sau khi thêm vào
            $totalQuantity = $existingQuantity + $newQuantity;
    
            // Kiểm tra tổng số lượng có vượt quá tồn kho không
            if ($totalQuantity > $productVariation->stock_quantity) {
                return response()->json([
                    'message' => 'Số lượng trong giỏ hàng vượt quá số lượng tồn kho!',
                    'stock_quantity' => $productVariation->stock_quantity,
                    'cart_quantity_now' => $existingQuantity,
                    'quantity_member_add' => $newQuantity,
                    'max_can_add' => $productVariation->stock_quantity - $existingQuantity
                ], 400);
            }
    
            // Cập nhật hoặc thêm mới sản phẩm vào giỏ hàng
            $cartItem = CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'variation_id' => $variationId],
                ['quantity' => $totalQuantity]
            );
    
            // Cập nhật số lượng tồn kho
            $productVariation->stock_quantity -= $newQuantity;
            $productVariation->save();
    
            return response()->json([
                'message' => 'Sản phẩm đã được thêm vào giỏ hàng!',
                'data' => $cartItem
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Có lỗi xảy ra!',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
    
    // xóa items
    public function removeItem($id)
    {
        try {
            $user = auth('sanctum')->user();
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

    // thay đổi sổ lượng
    public function changeQuantity($id)
    {
        try {
            $user = auth('sanctum')->user();
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
                    'message' => 'Số lượng phải lớn hơn hoặc bằng 1',
                ], 400);
            }

            // Tìm sản phẩm trong giỏ hàng
            $updateItem = CartItem::where('cart_id', $cart->id)
                ->where('variation_id', $variationId)
                ->where('id', $id)
                ->first();

            if (!$updateItem) {
                return response()->json([
                    'message' => 'Không tìm thấy sản phẩm cần update',
                ], 404);
            }

            // Kiểm tra số lượng tồn kho
            $productVariation = ProductVariation::find($variationId);

            if (!$productVariation) {
                return response()->json([
                    'message' => 'Sản phẩm không tồn tại',
                ], 404);
            }

            if ($newQuantity > $productVariation->stock_quantity) {
                return response()->json([
                    'message' => 'Số lượng yêu cầu vượt quá số lượng tồn kho',
                ], 400);
            }

            // Cập nhật số lượng nếu hợp lệ
            $updateItem->quantity = $newQuantity;
            $updateItem->save();

            return response()->json([
                'message' => 'Cập nhật số lượng thành công',
                'data' => $updateItem
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    // Đồng bộ cart
    public function syncCart(Request $request)
    {
        try {
            $user = auth('sanctum')->user(); // lấy ra user
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
    // Xóa toàn bộ cart item
    public function clearAll()
    {
        try {
            $user = auth('sanctum')->user();
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'message' => 'Không tìm thấy cart',
                ], 404);
            }

            // Xóa toàn bộ sản phẩm trong giỏ hàng
            CartItem::where('cart_id', $cart->id)->delete();

            return response()->json([
                'message' => 'Xóa thành công'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
