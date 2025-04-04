<?php

namespace App\Exports\Product;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Sản phẩm biến thể' => new VariantExport(),
            'Sản phẩm đơn giản' => new SimpleExport(),
        ];
    }
}

