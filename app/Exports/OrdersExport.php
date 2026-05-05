<?php

namespace App\Exports;

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

class OrdersExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles, WithTitle
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
            ->with('user')
            ->whereBetween('created_at', [$from, $to])
            ->oldest()
            ->get();

        return [
            ['Orders Report'],
            ["Date range: {$this->dateFrom} to {$this->dateTo}"],
            ['Order Number', 'Customer', 'Email', 'Status', 'Total Amount', 'Created At'],
            ...$orders->map(fn (Order $order): array => [
                $order->order_number,
                $order->user?->name,
                $order->user?->email,
                $order->status->label(),
                (float) $order->total_amount,
                $order->created_at?->toDateTimeString(),
            ])->all(),
            [],
            ['Total', $orders->count(), null, null, (float) $orders->sum('total_amount'), null],
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
        return 'Orders';
    }
}
