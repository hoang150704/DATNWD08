<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::select('id', 'name', 'parent_id', 'slug')->get(); // lấy ra tất cả danh mục không xóa mềm
        $groupCategories = $categories->groupBy('parent_id');//Nhóm tất cả danh mục theo paren_id(Các danh mục có parent_id sẽ chung 1 nhóm)

        $data = $this->convertData($groupCategories,null); // GỌi hàm convert lần đầu
        return response()->json($data,200);
    }
    // Convert dữ liệu thành dạng cha-con
    private function convertData($listCategories, $parentId, $visited = [])
    {
        $convertData = [];
    
        if (isset($listCategories[$parentId])) { // Kiểm tra nhóm danh mục con theo parebt_id
            foreach ($listCategories[$parentId] as $category) { 
                if (!in_array($category->id, $visited)) { 
                    $visited[] = $category->id; 
    
                    $convertData[] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'children' => $this->convertData($listCategories, $category->id, $visited),
                    ];
                }
            }
        }
    
        return $convertData;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $category = Category::withTrashed()->find($id);

        if (!$category) {
            return response()->json(['message' => 'Danh mục không tồn tại'], 404);
        }
    
        if ($category->trashed()) {
            return response()->json(['message' => 'Danh mục đã được xóa mềm'], 400);
        }
        $childCategories = Category::where('parent_id', $id)->get();

        //Cập nhật parent_id của các danh mục con thành null
        foreach ($childCategories as $childCategory) {
            $childCategory->parent_id = null;
            $childCategory->save();
        }
        $category->delete();
    
        $category->delete();

    
        return response()->json(['message' => 'Danh mục đã được chuyển vào thùng rác'], 200);
    }
}
