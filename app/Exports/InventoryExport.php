<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles, WithTitle
{
    private const HEADER_ROW = 3;

    private const FIRST_DATA_ROW = 4;

    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
    ) {}

    public function array(): array
    {
        $products = Product::query()
            ->with(['category', 'brand'])
            ->orderBy('sku')
            ->get();

        return [
            ['Inventory Status Overview'],
            ["Generated for: {$this->dateFrom} to {$this->dateTo}"],
            ['SKU', 'Name', 'Category', 'Brand', 'Stock', 'Low Stock Threshold', 'Price', 'Status'],
            ...$products->map(fn (Product $product): array => [
                $product->sku,
                $product->name,
                $product->category?->name,
                $product->brand?->name,
                $product->stock_quantity,
                $product->low_stock_threshold,
                (float) $product->price,
                $product->stock_quantity === 0 ? 'No Stock' : ($product->isLowStock() ? 'Low Stock' : 'In Stock'),
            ])->all(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            self::HEADER_ROW => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_CURRENCY_USD,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $event->sheet->freezePane('A'.self::FIRST_DATA_ROW),
        ];
    }

    public function title(): string
    {
        return 'Inventory';
    }
}
