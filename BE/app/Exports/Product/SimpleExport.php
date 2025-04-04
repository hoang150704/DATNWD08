<?php

namespace App\Exports\Product;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SimpleExport implements FromQuery, WithMapping, WithHeadings, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return Product::query()->with(['categories', 'variants'])->where('type', '1');
    }

    public function map($product): array
    {
        $variant = $product->variants->first();
        return [
            $product->id,
            $product->name,
            $product->categories->pluck('name')->join(', '),
            $variant->sku,
            $variant->regular_price,
            $variant?->sale_price ?? 'N/A',
            $variant?->stock_quantity ?? 'Hết hàng',
            $product->created_at->format('d-m-Y H:i:s'),
            $product->updated_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên sản phẩm',
            'Danh mục',
            'SKU',
            'Giá thường',
            'Giá khuyến mãi',
            'Số lượng',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function title(): string
    {
        return "Sản phẩm đơn giản";
    }
}
