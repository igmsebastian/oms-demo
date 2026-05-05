<?php

namespace App\Exports;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
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

class RevenueExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles, WithTitle
{
    private const HEADER_ROW = 3;

    private const FIRST_DATA_ROW = 4;

    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
    ) {}

    public function array(): array
    {
        $from = CarbonImmutable::parse($this->dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($this->dateTo)->endOfDay();
        $orders = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'status', 'total_amount', 'created_at']);

        $rows = $orders
            ->groupBy(fn (Order $order): string => $order->created_at?->format('Y-m') ?? now()->format('Y-m'))
            ->sortKeys()
            ->map(fn ($orders, string $period): array => [
                $period,
                $orders->count(),
                (float) $orders->sum('total_amount'),
                (float) $orders->where('status', OrderStatus::Completed)->sum('total_amount'),
                (float) $orders->where('status', OrderStatus::Refunded)->sum('total_amount'),
            ])
            ->values()
            ->all();

        return [
            ['Revenue Report'],
            ["Date range: {$this->dateFrom} to {$this->dateTo}"],
            ['Period', 'Orders', 'Gross Revenue', 'Completed Revenue', 'Refunded Revenue'],
            ...$rows,
            [],
            ['Total', $orders->count(), (float) $orders->sum('total_amount'), (float) $orders->where('status', OrderStatus::Completed)->sum('total_amount'), (float) $orders->where('status', OrderStatus::Refunded)->sum('total_amount')],
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
            'C' => NumberFormat::FORMAT_CURRENCY_USD,
            'D' => NumberFormat::FORMAT_CURRENCY_USD,
            'E' => NumberFormat::FORMAT_CURRENCY_USD,
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
        return 'Revenue';
    }
}
