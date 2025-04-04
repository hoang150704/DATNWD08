<?php

namespace App\Exports\Product;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class VariantExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return Product::query()
            ->with(['categories', 'variants.attributeValues'])
            ->where('type', 0)
            ->get();
    }

    public function map($product): array
    {
        $rows = [];

        foreach ($product->variants as $variant) {
            $color = $variant->attributeValues->firstWhere('attribute.name', 'Màu sắc');
            $colorValue = $color ? $color->name : 'N/A';

            $size = $variant->attributeValues->firstWhere('attribute.name', 'Kích thước');
            $sizeValue = $size ? $size->name : 'N/A';

            $rows[] = [
                $product->id,
                $product->name,
                $product->categories->pluck('name')->join(', '),
                $variant->sku,
                $colorValue,
                $sizeValue,
                $variant->regular_price,
                $variant->sale_price ?? 'N/A',
                $variant->stock_quantity ?? 'Hết hàng',
                $product->created_at->format('d-m-Y H:i:s'),
                $product->updated_at->format('d-m-Y H:i:s'),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên sản phẩm',
            'Danh mục',
            'SKU',
            'Màu sắc',
            'Kích thước',
            'Giá thường',
            'Giá khuyến mãi',
            'Số lượng',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function title(): string
    {
        return "Sản phẩm biến thể";
    }
}
