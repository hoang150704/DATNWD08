<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected function search($parent = null, $children = null)
    {
        try {
            return Category::query()
                ->when($children, function ($query) use ($children) {
                    $query->where('name', 'like', "%{$children}%")
                        ->whereNotNull('parent_id');
                })
                ->when(!$children && $parent, function ($query) use ($parent) {
                    $query->where('name', 'like', "%{$parent}%")
                        ->whereNull('parent_id')
                        ->with('children');
                })
                ->when(!$parent && !$children, function ($query) {
                    $query->whereNull('parent_id')->with('children');
                })
                ->paginate(15);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage()
            ], 404);
        }
    }


    public function index()
    {
        try {
            $parent = request('parent');
            $children = request('children');

            // Nếu có $children, chỉ tìm theo $children
            if ($children) {
                $categories = $this->search(null, $children); // Truyền đúng vị trí tham số
                return response()->json($categories, 200);
            }

            // Nếu có $parent hoặc không có request nào, tìm theo $parent
            $categories = $this->search($parent, null);
            $this->convertChildren($categories); // Gọi hàm để convert dữ liệu

            return response()->json($categories, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function convertChildren($categories)
    {
        foreach ($categories as $category) {
            if ($category->children->count() > 0) {
                $this->convertChildren($category->children);
            }
        }
    }

    // Thùng rác
    public function trash(Request $request)
    {
        try {
            //code...
            $listSoftDeleteCategories = Category::onlyTrashed()->paginate(15);
            return response()->json($listSoftDeleteCategories, 200); // trả về respone
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }
    // 
    public function getCategories()
    {
        try {
            //code...
            $categories = Category::select('id', 'name')->get(); //phân trang theo danh mục gốc(Không phải con của danh mục khác)
            return response()->json($categories, 200); // trả về respone
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }
    // Lấy danh mục cha để thêm

    public function getParentCategories()
    {
        try {
            //code...
            $categories = Category::whereNull('parent_id')->select('id', 'name')->get();
            return response()->json($categories, 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',
                'parent_id' => [
                    'nullable',
                    Rule::exists('categories', 'id')->whereNull('parent_id'),
                ],
            ]);

            // Chuẩn hóa slug
            $slug = Str::slug($data['name']);
            $count = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = "{$slug}-$count";
                $count++;
            }
            $data['slug'] = $slug;

            // Tạo bản ghi mới
            $category = Category::create($data);

            return response()->json($category, 201);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Vui lòng nhập đầy đủ và đúng thông tin", "errors" => $e->getMessage()], 422);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::findOrFail($id);

            // Kiểm tra xem danh mục hiện tại có phải là danh mục cha của danh mục nào không
            $isParentCategory = Category::where('parent_id', $category->id)->exists();

            if ($isParentCategory) {
                // Nếu là danh mục cha, trả về mảng rỗng
                $parentCategories = [];
            } else {
                // Nếu không phải danh mục cha, lấy tất cả danh mục cấp 1 trừ danh mục hiện tại
                $parentCategories = Category::select('id', 'name')->whereNull('parent_id')->where('id', '!=', $category->id)->get();
            }
            // Convert data
            $categoryConvert = [
                "id" => $category->id,
                "name" => $category->name,
                "slug" => $category->slug,
                "parent_id" => $category->parent_id,
            ];

            return response()->json(compact('categoryConvert', 'parentCategories'), 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    // Convert fduwx liệu
    // private function getAllsIdsNeedExclude($category)
    // {
    //     $ids = [$category->id];
    //     foreach ($category->children as $child) {
    //         $ids = array_merge($ids, $this->getAllsIdsNeedExclude($child));
    //     }
    //     return $ids;
    // }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            //code...
            $data = $request->validate([
                'name' => 'required',
                'parent_id' => 'nullable',
                'slug' => 'required',
            ]);

            $slug = Str::slug($data['slug']);

            $count = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = "{$slug}-" . $count;
                $count++;
            }

            Category::where('id', $id)->update([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'],
                'slug' => $slug,
            ]);


            return response()->json(['message' => 'Cập nhật danh mục thành công'], 200);
        } catch (ValidationException $e) {
            return response()->json(["message" => "Vui lòng nhập đầy đủ và đúng thông tin"], 422);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        try {

            $ids = request('ids');

            $categories = Category::whereIn('id', $ids)->get();

            // Cập nhật parent_id của các danh mục con thành null
            Category::whereIn('parent_id', $ids)->update(['parent_id' => null]);

            Category::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Danh mục đã được chuyển vào thùng rác', 'data' => $categories], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    // xóa cứng
    public function hardDelete()
    {
        try {
            $ids = request('ids');

            Category::onlyTrashed()->whereIn('id', $ids)->forceDelete();

            return response()->json(['message' => 'Xóa danh mục thành công'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy danh mục cần xóa'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    // Khôi phục
    public function restore()
    {
        try {
            $ids = request('ids');
            Category::onlyTrashed()->whereIn('id', $ids)->restore();

            return response()->json(["message" => "Bạn đã phục hồi thành công"], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(["message" => "Không tìm thấy danh mục cần khôi phục"], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $th->getMessage()

            ], 500);
        }
    }

    public function changeCategory()
    {
        try {
            $ids = request('ids');
            $categoryId = request('categoryId');

            $update = Category::whereIn('id', $ids)->update(['parent_id' => $categoryId]);

            return response()->json([
                'message' => 'Cập nhật thành công',
                'data' => $update
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Cập nhật thất bại',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
