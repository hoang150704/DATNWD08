<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            //code...
            $categories = Category::whereNull('parent_id')->with('children')->paginate(15); //phân trang theo danh mục gốc(Không phải con của danh mục khác)
            $this->convertChildren($categories); // gọi hàm để convert lại dữ liệu
            return response()->json($categories, 200); // trả về respone
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(["message" => "Lỗi", 500]);
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
            return response()->json(["message" => "Lỗi", 500]);
        }
    }

    // Lấy danh mục cha để thêm

    public function getParentCategories()
    {
        $categories = Category::select('id', 'name')->get();
        return response()->json($categories,200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',
                'parent_id' => 'nullable',
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
            return response()->json(["message" => "Vui lòng nhập đầy đủ và đúng thông tin"], 422);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra khi thêm danh mục'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $category = Category::select('id', 'name', 'slug', 'parent_id', 'created_at', 'updated_at')->findOrFail($id);
            $listIdsExclude = $this->getAllsIdsNeedExclude($category);
            $parentCategories = Category::select('id', 'name')
                ->whereNotIn('id', $listIdsExclude)
                ->get();
            $categoryConvert = [
                "id" => $category->id,
                "name" => $category->name,
                "slug" => $category->slug,
                "parent_id" => $category->parent_id
            ];
            return response()->json(compact('categoryConvert', 'parentCategories'), 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Có lỗi xảy ra.'], 500);
        }
    }

    // Convert fduwx liệu
    private function getAllsIdsNeedExclude($category)
    {
        $ids = [$category->id];
        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getAllsIdsNeedExclude($child));
        }
        return $ids;
    }


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
                $slug = "{$slug}-$count";
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
            return response()->json(['error' => 'Lỗi cập nhật'], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::findOrFail($id);
    
            if ($category->trashed()) {
                return response()->json(['message' => 'Danh mục đã được xóa mềm'], 400);
            }
    
            // Cập nhật parent_id của các danh mục con thành null
            Category::where('parent_id', $id)->update(['parent_id' => null]); 
    
            $category->delete();
    
            return response()->json(['message' => 'Danh mục đã được chuyển vào thùng rác'], 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'Có lỗi xảy ra'], 500);
        }
    }

    // xóa cứng
    public function hardDelete(string $id)
    {
        try {
            Category::onlyTrashed()->findOrFail($id)->forceDelete();
    
            return response()->json(['message' => 'Xóa danh mục thành công'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy danh mục cần xóa'], 404);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['message' => 'Có lỗi xảy ra'], 500);
        }
    }

// Khôi phục
    public function restore($id)
    {
        try {
            $category = Category::onlyTrashed()->findOrFail($id); 
    
            $category->restore(); 
    
            return response()->json(["message"=>"Bạn đã phục hồi thành công"], 200); 
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(["message"=>"Không tìm thấy danh mục cần khôi phục"], 404); 
        } catch (\Throwable $th) {
            Log::error($th); 
            return response()->json(["message"=>"Có lỗi xảy ra"], 500); 
        }
    }

}
