<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Product([
            'id'=>$row['STT'],
            'name'=>$row['Tên sản phẩm'],
            'description'=>$row['Mô tả'],
            'short_description'=>$row['Mô tả ngắn'],
            'STT'=>$row[0],
            'STT'=>$row[0],
            'STT'=>$row[0],
            'STT'=>$row[0],
            'STT'=>$row[0],
        ]);
    }
}
