<?php

use App\Exports\InventoryExport;
use App\Exports\OrdersExport;
use App\Exports\RevenueExport;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('report export endpoint downloads each supported report type for admins', function (string $type) {
    $admin = User::factory()->admin()->create();
    $dateFrom = now()->subDay()->toDateString();
    $dateTo = now()->toDateString();

    $response = $this->actingAs($admin)
        ->get(route('reports.export', [
            'type' => $type,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]))
        ->assertOk()
        ->assertHeader('content-disposition');

    expect($response->headers->get('content-disposition'))->toContain("oms-{$type}-{$dateFrom}-{$dateTo}.xlsx");
})->with(['orders', 'inventory', 'revenue']);

test('report export endpoint rejects guests users and invalid filters', function () {
    $user = User::factory()->create();

    $this->get(route('reports.export', ['type' => 'orders']))
        ->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get(route('reports.export', ['type' => 'orders']))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.export', [
            'type' => 'bad-type',
            'date_from' => now()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['type', 'date_to']);
});

test('export styling targets the header row and freezes the first data row', function () {
    $dateFrom = now()->subDay()->toDateString();
    $dateTo = now()->toDateString();
    $user = User::factory()->create();
    $product = createOmsProduct(['sku' => 'EXPORT-SKU-001']);
    $order = createOmsOrder($user, [['product' => $product]]);

    $exports = [
        'orders' => [new OrdersExport($dateFrom, $dateTo), 'Order Number', $order->order_number],
        'inventory' => [new InventoryExport($dateFrom, $dateTo), 'SKU', 'EXPORT-SKU-001'],
        'revenue' => [new RevenueExport($dateFrom, $dateTo), 'Period', $order->created_at->format('Y-m')],
    ];

    foreach ($exports as [$export, $header, $firstDataValue]) {
        $spreadsheet = loadExportSpreadsheet($export);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getCell('A3')->getValue())->toBe($header)
            ->and($sheet->getCell('A4')->getValue())->toBe($firstDataValue)
            ->and($sheet->getFreezePane())->toBe('A4')
            ->and($sheet->getStyle('A3')->getFill()->getStartColor()->getRGB())->toBe('111827')
            ->and($sheet->getStyle('A4')->getFill()->getStartColor()->getRGB())->not->toBe('111827');
    }
});

function loadExportSpreadsheet(object $export): Spreadsheet
{
    $path = tempnam(sys_get_temp_dir(), 'oms-export-');

    file_put_contents($path, Excel::raw($export, ExcelWriter::XLSX));

    try {
        return IOFactory::load($path);
    } finally {
        @unlink($path);
    }
}
