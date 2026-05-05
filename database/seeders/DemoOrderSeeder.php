<?php

namespace Database\Seeders;

use App\Enums\CancellationStatus;
use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundStockDisposition;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderCancellation;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ReportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoOrderSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    protected array $stockCursor = [];

    protected const HISTORICAL_BASE_MONTHLY_ORDERS = 38;

    protected const TODAY_ORDER_COUNT = 600;

    protected const YESTERDAY_ORDER_COUNT = 600;

    /**
     * Seed realistic demo orders across every status and operational event.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $orders = $this->orders();

            $this->deleteExistingDemoOrders();

            $admins = User::query()
                ->whereIn('email', [
                    'basty@mydemo.com',
                    'ethan.wells@mydemo.com',
                    'sofia.nguyen@mydemo.com',
                ])
                ->get()
                ->keyBy('email');

            $customers = User::query()
                ->whereIn('email', collect($orders)->pluck('customer_email')->unique())
                ->get()
                ->keyBy('email');

            $products = Product::query()
                ->whereIn('sku', $this->productSkus($orders))
                ->get()
                ->keyBy('sku');

            if (
                $admins->isEmpty()
                || $customers->count() < collect($orders)->pluck('customer_email')->unique()->count()
                || $products->count() < count($this->productSkus($orders))
            ) {
                return;
            }

            $this->stockCursor = $this->startingStock($products, $orders);

            foreach ($orders as $definition) {
                $this->createDemoOrder($definition, $admins, $customers, $products);
            }
        });

        app(ReportService::class)->invalidate();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function orders(): array
    {
        return [
            ...$this->statusSampleOrders(),
            ...$this->historicalOrders(),
            ...$this->recentOrders(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function statusSampleOrders(): array
    {
        return [
            [
                'order_number' => 'OMS-DEMO-1001',
                'customer_email' => 'emma.carter@mydemo.com',
                'status' => OrderStatus::Pending,
                'created_at' => now()->subDays(18)->setTime(9, 15),
                'items' => [
                    ['sku' => 'SNEAK-LFS-001', 'quantity' => 1],
                ],
                'customer_note' => 'Please include the original shoebox in good condition.',
            ],
            [
                'order_number' => 'OMS-DEMO-1002',
                'customer_email' => 'noah.bennett@mydemo.com',
                'status' => OrderStatus::Confirmed,
                'created_at' => now()->subDays(17)->setTime(11, 20),
                'items' => [
                    ['sku' => 'SNEAK-RUN-001', 'quantity' => 2],
                ],
            ],
            [
                'order_number' => 'OMS-DEMO-1003',
                'customer_email' => 'ava.patel@mydemo.com',
                'status' => OrderStatus::Processing,
                'created_at' => now()->subDays(15)->setTime(14, 5),
                'items' => [
                    ['sku' => 'SNEAK-BSK-001', 'quantity' => 1],
                    ['sku' => 'SNEAK-LFS-002', 'quantity' => 1],
                ],
                'customer_note' => 'Customer asked for standard shipping and no invoice in the box.',
            ],
            [
                'order_number' => 'OMS-DEMO-1004',
                'customer_email' => 'lucas.kim@mydemo.com',
                'status' => OrderStatus::Packed,
                'created_at' => now()->subDays(13)->setTime(10, 45),
                'items' => [
                    ['sku' => 'SNEAK-TRN-001', 'quantity' => 1],
                ],
            ],
            [
                'order_number' => 'OMS-DEMO-1005',
                'customer_email' => 'mia.thompson@mydemo.com',
                'status' => OrderStatus::Shipped,
                'created_at' => now()->subDays(11)->setTime(16, 10),
                'items' => [
                    ['sku' => 'SNEAK-TRL-001', 'quantity' => 1],
                ],
                'tracking_number' => 'UPS-1Z8823DEMO1005',
                'carrier' => 'UPS',
            ],
            [
                'order_number' => 'OMS-DEMO-1006',
                'customer_email' => 'jordan.rivera@mydemo.com',
                'status' => OrderStatus::Delivered,
                'created_at' => now()->subDays(9)->setTime(12, 25),
                'items' => [
                    ['sku' => 'SNEAK-RUN-004', 'quantity' => 2],
                ],
                'tracking_number' => 'FDX-78391006',
                'carrier' => 'FedEx',
            ],
            [
                'order_number' => 'OMS-DEMO-1007',
                'customer_email' => 'noah.bennett@mydemo.com',
                'status' => OrderStatus::Completed,
                'created_at' => now()->subDays(8)->setTime(9, 50),
                'items' => [
                    ['sku' => 'SNEAK-RET-001', 'quantity' => 1],
                ],
                'tracking_number' => 'USPS-94001007',
                'carrier' => 'USPS',
            ],
            [
                'order_number' => 'OMS-DEMO-1008',
                'customer_email' => 'ava.patel@mydemo.com',
                'status' => OrderStatus::CancellationRequested,
                'created_at' => now()->subDays(7)->setTime(13, 35),
                'items' => [
                    ['sku' => 'SNEAK-LTD-002', 'quantity' => 1],
                ],
                'cancellation_reason' => 'Customer ordered US 12 but needs US 10 before the limited release ships.',
            ],
            [
                'order_number' => 'OMS-DEMO-1009',
                'customer_email' => 'lucas.kim@mydemo.com',
                'status' => OrderStatus::PartiallyCancelled,
                'created_at' => now()->subDays(6)->setTime(15, 5),
                'items' => [
                    ['sku' => 'SNEAK-BSK-001', 'quantity' => 2, 'cancelled_quantity' => 1],
                    ['sku' => 'SNEAK-LFS-001', 'quantity' => 1],
                ],
                'cancellation_reason' => 'Warehouse quality check found one pair with a lace defect.',
            ],
            [
                'order_number' => 'OMS-DEMO-1010',
                'customer_email' => 'mia.thompson@mydemo.com',
                'status' => OrderStatus::Cancelled,
                'created_at' => now()->subDays(5)->setTime(10, 30),
                'items' => [
                    ['sku' => 'SNEAK-TRN-002', 'quantity' => 1, 'cancelled_quantity' => 1],
                ],
                'cancellation_reason' => 'Customer cancelled after selecting the wrong training shoe size.',
            ],
            [
                'order_number' => 'OMS-DEMO-1011',
                'customer_email' => 'jordan.rivera@mydemo.com',
                'status' => OrderStatus::RefundPending,
                'created_at' => now()->subDays(11)->setTime(17, 40),
                'items' => [
                    ['sku' => 'SNEAK-LTD-001', 'quantity' => 1],
                ],
                'tracking_number' => 'DHL-DEMOLTD1011',
                'carrier' => 'DHL',
                'refund_reason' => 'Customer reported a visible scuff on the limited edition toe box.',
                'refund_amount' => 245.00,
            ],
            [
                'order_number' => 'OMS-DEMO-1012',
                'customer_email' => 'emma.carter@mydemo.com',
                'status' => OrderStatus::Refunded,
                'created_at' => now()->subDays(12)->setTime(8, 55),
                'items' => [
                    ['sku' => 'SNEAK-RET-002', 'quantity' => 1, 'refunded_quantity' => 1],
                ],
                'tracking_number' => 'UPS-1Z8823DEMO1012',
                'carrier' => 'UPS',
                'refund_reason' => 'Customer returned the pair unworn after a size exchange request.',
                'refund_amount' => 108.00,
                'stock_disposition' => RefundStockDisposition::GoodStock->value,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function historicalOrders(): array
    {
        $orders = [];
        $startDate = now()->subYears(2)->startOfDay();
        $endDate = now()->subDays(10)->endOfDay();
        $month = $startDate->copy()->startOfMonth();
        $monthIndex = 0;
        $sequence = 1;

        while ($month->lessThanOrEqualTo($endDate)) {
            $periodStart = $month->greaterThan($startDate) ? $month->copy() : $startDate->copy();
            $periodEnd = $month->copy()->endOfMonth();

            if ($periodEnd->greaterThan($endDate)) {
                $periodEnd = $endDate->copy();
            }

            $daysInScope = max(1, (int) $periodStart->diffInDays($periodEnd) + 1);
            $ordersInMonth = self::HISTORICAL_BASE_MONTHLY_ORDERS + (($monthIndex % 5) * 4);

            for ($index = 1; $index <= $ordersInMonth; $index++) {
                $createdAt = $sequence === 1
                    ? $startDate->copy()->setTime(9, 0)
                    : $periodStart->copy()
                        ->addDays((($index * 7) + ($monthIndex * 3)) % $daysInScope)
                        ->setTime(
                            8 + (($index + $monthIndex) % 12),
                            (($index * 11) + ($monthIndex * 5)) % 60,
                        );

                $orders[] = $this->orderDefinition(
                    orderNumber: sprintf('OMS-DEMO-HIST-%s-%04d', $createdAt->format('Ymd'), $sequence),
                    customerEmail: $this->customerEmailFor($sequence),
                    status: $this->historicalStatusFor($sequence),
                    createdAt: $createdAt,
                    sequence: $sequence,
                );

                $sequence++;
            }

            $month = $month->copy()->addMonthNoOverflow()->startOfMonth();
            $monthIndex++;
        }

        return $orders;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentOrders(): array
    {
        $orders = [];
        $todayStart = now()->startOfDay();
        $latestToday = now()->subMinutes(30);
        $yesterdayStart = now()->subDay()->startOfDay();

        if ($latestToday->lessThan($todayStart)) {
            $latestToday = now();
        }

        $todayWindowMinutes = max(1, (int) $todayStart->diffInMinutes($latestToday));
        $yesterdayWindowMinutes = 1439;

        for ($index = 1; $index <= self::TODAY_ORDER_COUNT; $index++) {
            $createdAt = $todayStart->copy()->addMinutes(
                (int) floor(($todayWindowMinutes / (self::TODAY_ORDER_COUNT + 1)) * $index),
            );

            $orders[] = $this->orderDefinition(
                orderNumber: sprintf('OMS-DEMO-TODAY-%02d', $index),
                customerEmail: $this->customerEmailFor($index),
                status: $this->recentStatusFor($createdAt, $index),
                createdAt: $createdAt,
                sequence: 2000 + $index,
            );
        }

        for ($index = 1; $index <= self::YESTERDAY_ORDER_COUNT; $index++) {
            $createdAt = $yesterdayStart->copy()->addMinutes(
                (int) floor(($yesterdayWindowMinutes / (self::YESTERDAY_ORDER_COUNT + 1)) * $index),
            );

            $orders[] = $this->orderDefinition(
                orderNumber: sprintf('OMS-DEMO-YDAY-%02d', $index),
                customerEmail: $this->customerEmailFor($index + self::TODAY_ORDER_COUNT),
                status: $this->recentStatusFor($createdAt, $index + self::TODAY_ORDER_COUNT),
                createdAt: $createdAt,
                sequence: 3000 + $index,
            );
        }

        return $orders;
    }

    protected function historicalStatusFor(int $sequence): OrderStatus
    {
        $statuses = [
            OrderStatus::Completed,
            OrderStatus::Delivered,
            OrderStatus::Completed,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Completed,
            OrderStatus::RefundPending,
            OrderStatus::Refunded,
            OrderStatus::Cancelled,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Packed,
            OrderStatus::Processing,
            OrderStatus::Confirmed,
        ];

        return $statuses[($sequence - 1) % count($statuses)];
    }

    protected function recentStatusFor(mixed $createdAt, int $sequence): OrderStatus
    {
        $ageMinutes = (int) $createdAt->diffInMinutes(now());
        $statuses = match (true) {
            $ageMinutes < 80 => [OrderStatus::Pending],
            $ageMinutes < 185 => [OrderStatus::Pending, OrderStatus::Confirmed],
            $ageMinutes < 250 => [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::CancellationRequested],
            $ageMinutes < 500 => [
                OrderStatus::Pending,
                OrderStatus::Confirmed,
                OrderStatus::Processing,
                OrderStatus::CancellationRequested,
            ],
            default => [
                OrderStatus::Pending,
                OrderStatus::Confirmed,
                OrderStatus::Processing,
                OrderStatus::CancellationRequested,
                OrderStatus::PartiallyCancelled,
                OrderStatus::Cancelled,
            ],
        };

        return $statuses[($sequence - 1) % count($statuses)];
    }

    protected function orderDefinition(
        string $orderNumber,
        string $customerEmail,
        OrderStatus $status,
        mixed $createdAt,
        int $sequence,
    ): array {
        $items = $this->itemsFor($status, $sequence);
        $definition = [
            'order_number' => $orderNumber,
            'customer_email' => $customerEmail,
            'status' => $status,
            'created_at' => $createdAt,
            'items' => $items,
            'customer_note' => $sequence % 9 === 0
                ? 'Customer requested careful packaging because the shoebox will be kept for display.'
                : null,
        ];

        if (in_array($status, [
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Cancelled,
        ], true)) {
            $definition['cancellation_reason'] = $this->cancellationReasonFor($sequence);
        }

        if ($status === OrderStatus::Shipped || $status === OrderStatus::Delivered || $status === OrderStatus::Completed) {
            $definition['carrier'] = $this->carrierFor($sequence);
            $definition['tracking_number'] = sprintf('%s-DEMO-%04d', strtoupper($definition['carrier']), $sequence);
        }

        if ($status === OrderStatus::RefundPending || $status === OrderStatus::Refunded) {
            $definition['refund_reason'] = $this->refundReasonFor($sequence);
            $definition['refund_amount'] = $status === OrderStatus::Refunded ? 108.00 : 245.00;
            $definition['stock_disposition'] = $status === OrderStatus::Refunded
                ? RefundStockDisposition::GoodStock->value
                : RefundStockDisposition::PendingReview->value;
        }

        return $definition;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function itemsFor(OrderStatus $status, int $sequence): array
    {
        if ($status === OrderStatus::RefundPending) {
            return [['sku' => 'SNEAK-LTD-001', 'quantity' => 1]];
        }

        if ($status === OrderStatus::Refunded) {
            return [['sku' => 'SNEAK-RET-002', 'quantity' => 1, 'refunded_quantity' => 1]];
        }

        if ($status === OrderStatus::PartiallyCancelled) {
            return [
                ['sku' => 'SNEAK-BSK-001', 'quantity' => 2, 'cancelled_quantity' => 1],
                ['sku' => 'SNEAK-LFS-001', 'quantity' => 1],
            ];
        }

        if ($status === OrderStatus::Cancelled) {
            return [['sku' => 'SNEAK-TRN-002', 'quantity' => 1, 'cancelled_quantity' => 1]];
        }

        $skus = [
            'SNEAK-RUN-001',
            'SNEAK-RUN-002',
            'SNEAK-RUN-004',
            'SNEAK-BSK-001',
            'SNEAK-LFS-001',
            'SNEAK-LFS-002',
            'SNEAK-TRN-001',
            'SNEAK-TRL-001',
            'SNEAK-LTD-003',
            'SNEAK-RET-001',
        ];
        $primarySku = $skus[($sequence - 1) % count($skus)];
        $items = [
            ['sku' => $primarySku, 'quantity' => $sequence % 5 === 0 ? 2 : 1],
        ];

        if ($sequence % 7 === 0) {
            $items[] = ['sku' => $skus[$sequence % count($skus)], 'quantity' => 1];
        }

        return $items;
    }

    protected function customerEmailFor(int $sequence): string
    {
        $customers = [
            'emma.carter@mydemo.com',
            'noah.bennett@mydemo.com',
            'ava.patel@mydemo.com',
            'lucas.kim@mydemo.com',
            'mia.thompson@mydemo.com',
            'jordan.rivera@mydemo.com',
        ];

        return $customers[($sequence - 1) % count($customers)];
    }

    protected function carrierFor(int $sequence): string
    {
        return ['UPS', 'FedEx', 'USPS', 'DHL'][($sequence - 1) % 4];
    }

    protected function cancellationReasonFor(int $sequence): string
    {
        return [
            'Customer requested a size change before fulfillment could be completed.',
            'Warehouse quality check found scuffed suede on one pair.',
            'Customer cancelled after accidentally selecting the wrong colorway.',
            'Limited edition pair failed final box condition inspection.',
        ][($sequence - 1) % 4];
    }

    protected function refundReasonFor(int $sequence): string
    {
        return [
            'Customer reported an unworn size mismatch after delivery.',
            'Collector box arrived with visible transit damage.',
            'Customer returned the pair after receiving a duplicate gift.',
            'Returned limited edition pair passed intake review for refund processing.',
        ][($sequence - 1) % 4];
    }

    /**
     * @param  Collection<string, User>  $admins
     * @param  Collection<string, User>  $customers
     * @param  Collection<string, Product>  $products
     */
    protected function createDemoOrder(array $definition, Collection $admins, Collection $customers, Collection $products): void
    {
        $definition = $this->withRemarks($definition);
        $customer = $customers->get($definition['customer_email']);
        $admin = $admins->get('basty@mydemo.com') ?? $admins->first();
        $supportAdmin = $admins->get('sofia.nguyen@mydemo.com') ?? $admin;
        $address = $this->addressFor($customer);
        $createdAt = $definition['created_at']->toImmutable();
        $definition['created_at'] = $createdAt;
        /** @var Collection<int, array<string, mixed>> $items */
        $items = collect($definition['items'])->map(function (array $item) use ($products): array {
            $product = $products->get($item['sku']);
            $quantity = (int) $item['quantity'];

            return [
                ...$item,
                'product' => $product,
                'quantity' => $quantity,
                'line_total' => round((float) $product->price * $quantity, 2),
            ];
        })->values();
        $lastActivityAt = $this->lastActivityAt($definition);
        $order = Order::create([
            'user_id' => $customer->id,
            'user_address_id' => $address->id,
            'order_number' => $definition['order_number'],
            'status' => $definition['status'],
            'total_amount' => round((float) $items->sum('line_total'), 2),
            'shipping_address_line_1' => $address->address_line_1,
            'shipping_address_line_2' => $address->address_line_2,
            'shipping_city' => $address->city,
            'shipping_country' => $address->country,
            'shipping_post_code' => $address->post_code,
            'shipping_full_address' => $address->full_address,
            'cancellation_reason' => $definition['cancellation_reason'] ?? null,
            'cancelled_by_user_id' => $definition['status'] === OrderStatus::Cancelled ? $admin->id : null,
            'cancelled_by_role' => $definition['status'] === OrderStatus::Cancelled ? $admin->role : null,
            'confirmed_at' => $this->isInventoryReserved($definition['status']) ? $createdAt->addHours(1) : null,
            'cancelled_at' => $definition['status'] === OrderStatus::Cancelled ? $createdAt->addHours(8) : null,
            'refunded_at' => $definition['status'] === OrderStatus::Refunded ? $createdAt->addDays(5) : null,
        ]);

        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $lastActivityAt,
        ])->save();

        $orderItems = $this->createItems($order, $items, $createdAt);
        $cancellation = $this->createCancellation($definition, $order, $customer, $admin);
        $refund = $this->createRefund($definition, $order, $customer, $supportAdmin);

        $this->createInventoryLogs($definition, $order, $orderItems, $admin);
        $this->createActivities($definition, $order, $customer, $admin, $supportAdmin, $cancellation, $refund);
    }

    /**
     * @return array<string, mixed>
     */
    protected function withRemarks(array $definition): array
    {
        $status = $definition['status'];
        $remarks = collect($definition['remarks'] ?? [])
            ->map(fn (mixed $remark): array => is_array($remark) ? $remark : ['description' => (string) $remark])
            ->filter(fn (array $remark): bool => filled($remark['description'] ?? null))
            ->values()
            ->all();

        if (filled($definition['customer_note'] ?? null)) {
            $remarks[] = [
                'actor' => 'customer',
                'description' => $definition['customer_note'],
                'type' => 'customer_note',
            ];
        }

        if ($this->requiresCancellationRemark($status)) {
            $remarks[] = [
                'actor' => $status === OrderStatus::PartiallyCancelled ? 'admin' : 'customer',
                'description' => $this->cancellationRemarkFor($status, $definition['cancellation_reason']),
                'type' => 'cancellation',
            ];
        }

        if ($remarks === []) {
            $remarks[] = [
                'actor' => $this->defaultRemarkActorFor($status),
                'description' => $this->statusRemarkFor($status),
                'type' => 'operations',
            ];
        }

        $definition['remarks'] = $remarks;

        return $definition;
    }

    protected function requiresCancellationRemark(OrderStatus $status): bool
    {
        return in_array($status, [
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Cancelled,
        ], true);
    }

    protected function statusRemarkFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'Customer service note: monitor payment confirmation before releasing this pair for fulfillment.',
            OrderStatus::Confirmed => 'Operations note: payment and inventory checks are clear for warehouse assignment.',
            OrderStatus::Processing => 'Warehouse note: verify size label and colorway before picking the sneakers.',
            OrderStatus::Packed => 'Packaging note: keep the collector shoebox free of tape and exterior labels.',
            OrderStatus::Shipped => 'Support note: tracking details were sent to the customer after carrier pickup.',
            OrderStatus::Delivered => 'Delivery note: carrier marked the package delivered to the customer address.',
            OrderStatus::Completed => 'Operations note: order completed after the delivery review window closed.',
            OrderStatus::RefundPending => 'Support note: return intake is pending review before refund completion.',
            OrderStatus::Refunded => 'Refund note: customer refund was processed and item disposition was recorded.',
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Cancelled => 'Cancellation note: cancellation reason must be reviewed and recorded.',
        };
    }

    protected function cancellationRemarkFor(OrderStatus $status, string $reason): string
    {
        return match ($status) {
            OrderStatus::CancellationRequested => "Cancellation request remark: review customer request before fulfillment continues. Reason: {$reason}",
            OrderStatus::PartiallyCancelled => "Partial cancellation remark: document the cancelled quantity and continue fulfillable items. Reason: {$reason}",
            OrderStatus::Cancelled => "Cancellation completion remark: close the order after approval and inventory reconciliation. Reason: {$reason}",
            default => "Cancellation remark: {$reason}",
        };
    }

    protected function defaultRemarkActorFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending,
            OrderStatus::CancellationRequested => 'customer',
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Completed,
            OrderStatus::RefundPending,
            OrderStatus::Refunded => 'support',
            default => 'admin',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<string, OrderItem>
     */
    protected function createItems(Order $order, Collection $items, mixed $createdAt): Collection
    {
        return $items->mapWithKeys(function (array $item) use ($order, $createdAt): array {
            $product = $item['product'];
            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $item['quantity'],
                'cancelled_quantity' => $item['cancelled_quantity'] ?? 0,
                'refunded_quantity' => $item['refunded_quantity'] ?? 0,
                'unit_price' => $product->price,
                'line_total' => $item['line_total'],
            ]);

            $orderItem->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
            $orderItem->setRelation('product', $product);

            return [$product->sku => $orderItem];
        });
    }

    /**
     * @param  Collection<string, OrderItem>  $orderItems
     */
    protected function createInventoryLogs(array $definition, Order $order, Collection $orderItems, User $admin): void
    {
        $createdAt = $definition['created_at'];

        if ($this->isInventoryReserved($definition['status'])) {
            foreach ($definition['items'] as $item) {
                $this->recordInventoryMovement(
                    $order,
                    $orderItems->get($item['sku']),
                    $admin,
                    InventoryChangeType::Deduction,
                    -((int) $item['quantity']),
                    'Inventory reserved when the order was confirmed.',
                    $createdAt->addHour(),
                );
            }
        }

        if ($definition['status'] === OrderStatus::PartiallyCancelled) {
            foreach ($definition['items'] as $item) {
                $cancelledQuantity = (int) ($item['cancelled_quantity'] ?? 0);

                if ($cancelledQuantity > 0) {
                    $this->recordInventoryMovement(
                        $order,
                        $orderItems->get($item['sku']),
                        $admin,
                        InventoryChangeType::Restore,
                        $cancelledQuantity,
                        $definition['cancellation_reason'],
                        $createdAt->addHours(8),
                    );
                }
            }
        }

        if ($definition['status'] === OrderStatus::Cancelled) {
            foreach ($definition['items'] as $item) {
                $this->recordInventoryMovement(
                    $order,
                    $orderItems->get($item['sku']),
                    $admin,
                    InventoryChangeType::Restore,
                    (int) $item['quantity'],
                    $definition['cancellation_reason'],
                    $createdAt->addHours(8),
                );
            }
        }

        if (($definition['stock_disposition'] ?? null) === RefundStockDisposition::GoodStock->value) {
            foreach ($definition['items'] as $item) {
                $refundedQuantity = (int) ($item['refunded_quantity'] ?? 0);

                if ($refundedQuantity > 0) {
                    $this->recordInventoryMovement(
                        $order,
                        $orderItems->get($item['sku']),
                        $admin,
                        InventoryChangeType::Restore,
                        $refundedQuantity,
                        $definition['refund_reason'],
                        $createdAt->addDays(5),
                        [RefundStockDisposition::MetadataKey => RefundStockDisposition::GoodStock->value],
                    );
                }
            }
        }
    }

    protected function recordInventoryMovement(
        Order $order,
        OrderItem $item,
        User $actor,
        InventoryChangeType $type,
        int $quantityChange,
        string $reason,
        mixed $createdAt,
        array $metadata = [],
    ): void {
        $product = $item->product;
        $stockBefore = $this->stockCursor[$product->sku];
        $stockAfter = $stockBefore + $quantityChange;
        $this->stockCursor[$product->sku] = $stockAfter;

        $log = InventoryLog::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'changed_by_user_id' => $actor->id,
            'change_type' => $type,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $log->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();
    }

    protected function createCancellation(array $definition, Order $order, User $customer, User $admin): ?OrderCancellation
    {
        if (! in_array($definition['status'], [
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
            OrderStatus::Cancelled,
        ], true)) {
            return null;
        }

        $createdAt = $definition['created_at']->addHours(3);
        $isRequested = $definition['status'] === OrderStatus::CancellationRequested;
        $requestedBy = $definition['status'] === OrderStatus::PartiallyCancelled ? $admin : $customer;
        $cancellation = OrderCancellation::create([
            'order_id' => $order->id,
            'requested_by_user_id' => $requestedBy->id,
            'requested_by_role' => $requestedBy->role,
            'reason' => $definition['cancellation_reason'],
            'status' => $isRequested ? CancellationStatus::Requested : CancellationStatus::Completed,
            'admin_note' => $isRequested ? null : 'Demo cancellation reviewed and recorded for operations training.',
            'approved_by_user_id' => $isRequested ? null : $admin->id,
            'approved_at' => $isRequested ? null : $createdAt->addHours(2),
            'completed_at' => $isRequested ? null : $createdAt->addHours(5),
        ]);

        $cancellation->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $isRequested ? $createdAt : $createdAt->addHours(5),
        ])->save();

        return $cancellation;
    }

    protected function createRefund(array $definition, Order $order, User $customer, User $admin): ?OrderRefund
    {
        if (! in_array($definition['status'], [OrderStatus::RefundPending, OrderStatus::Refunded], true)) {
            return null;
        }

        $createdAt = $definition['created_at']->addDays(4);
        $isCompleted = $definition['status'] === OrderStatus::Refunded;
        $refund = OrderRefund::create([
            'order_id' => $order->id,
            'requested_by_user_id' => $customer->id,
            'processed_by_user_id' => $isCompleted ? $admin->id : null,
            'status' => $isCompleted ? RefundStatus::Completed : RefundStatus::Pending,
            'amount' => $definition['refund_amount'],
            'reason' => $definition['refund_reason'],
            'metadata' => [
                RefundStockDisposition::MetadataKey => $definition['stock_disposition'] ?? RefundStockDisposition::PendingReview->value,
            ],
            'processed_at' => $isCompleted ? $createdAt->addDay() : null,
        ]);

        $refund->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $isCompleted ? $createdAt->addDay() : $createdAt,
        ])->save();

        return $refund;
    }

    protected function createActivities(
        array $definition,
        Order $order,
        User $customer,
        User $admin,
        User $supportAdmin,
        ?OrderCancellation $cancellation,
        ?OrderRefund $refund,
    ): void {
        $createdAt = $definition['created_at'];

        $this->activity($order, OrderActivityEvent::OrderCreated, $createdAt->addMinutes(5), [
            'actor' => $customer,
            'to_status' => OrderStatus::Pending,
            'description' => 'Order placed from the demo storefront.',
        ]);

        foreach ($definition['remarks'] as $index => $remark) {
            $this->activity($order, OrderActivityEvent::RemarkAdded, $createdAt->addMinutes(20 + ($index * 8)), [
                'actor' => $this->remarkActor($remark['actor'] ?? 'admin', $customer, $admin, $supportAdmin),
                'description' => $remark['description'],
                'metadata' => ['remark_type' => $remark['type'] ?? 'operations'],
            ]);
        }

        if (! $this->isInventoryReserved($definition['status'])) {
            return;
        }

        $this->activity($order, OrderActivityEvent::InventoryDeducted, $createdAt->addHour(), [
            'actor' => $admin,
            'description' => 'Inventory was reserved for fulfillment.',
            'metadata' => ['item_count' => count($definition['items'])],
        ]);

        $this->activity($order, OrderActivityEvent::OrderConfirmed, $createdAt->addHours(1)->addMinutes(10), [
            'actor' => $admin,
            'from_status' => OrderStatus::Pending,
            'to_status' => OrderStatus::Confirmed,
            'description' => 'Payment and inventory checks passed.',
        ]);

        if ($definition['status'] === OrderStatus::Confirmed) {
            return;
        }

        if ($definition['status'] === OrderStatus::CancellationRequested) {
            $this->activity($order, OrderActivityEvent::CancellationRequested, $createdAt->addHours(3), [
                'actor' => $customer,
                'from_status' => OrderStatus::Confirmed,
                'to_status' => OrderStatus::CancellationRequested,
                'description' => $definition['cancellation_reason'],
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);

            return;
        }

        $this->activity($order, OrderActivityEvent::OrderProcessingStarted, $createdAt->addHours(4), [
            'actor' => $admin,
            'from_status' => OrderStatus::Confirmed,
            'to_status' => OrderStatus::Processing,
            'description' => 'Warehouse team started picking the order.',
        ]);

        if ($definition['status'] === OrderStatus::Processing) {
            return;
        }

        if ($definition['status'] === OrderStatus::PartiallyCancelled) {
            $this->activity($order, OrderActivityEvent::InventoryRestored, $createdAt->addHours(8), [
                'actor' => $admin,
                'description' => 'Cancelled quantity was restored to available stock.',
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);
            $this->activity($order, OrderActivityEvent::OrderPartiallyCancelled, $createdAt->addHours(8)->addMinutes(5), [
                'actor' => $admin,
                'from_status' => OrderStatus::Processing,
                'to_status' => OrderStatus::PartiallyCancelled,
                'description' => $definition['cancellation_reason'],
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);

            return;
        }

        if ($definition['status'] === OrderStatus::Cancelled) {
            $this->activity($order, OrderActivityEvent::CancellationRequested, $createdAt->addHours(3), [
                'actor' => $customer,
                'from_status' => OrderStatus::Confirmed,
                'to_status' => OrderStatus::CancellationRequested,
                'description' => $definition['cancellation_reason'],
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);
            $this->activity($order, OrderActivityEvent::InventoryRestored, $createdAt->addHours(8), [
                'actor' => $admin,
                'description' => 'Reserved inventory was returned after cancellation approval.',
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);
            $this->activity($order, OrderActivityEvent::OrderCancelled, $createdAt->addHours(8)->addMinutes(10), [
                'actor' => $admin,
                'from_status' => OrderStatus::CancellationRequested,
                'to_status' => OrderStatus::Cancelled,
                'description' => $definition['cancellation_reason'],
                'metadata' => ['cancellation_id' => $cancellation?->id],
            ]);

            return;
        }

        $this->activity($order, OrderActivityEvent::OrderPacked, $createdAt->addDay(), [
            'actor' => $admin,
            'from_status' => OrderStatus::Processing,
            'to_status' => OrderStatus::Packed,
            'description' => 'Items were boxed, label checked, and moved to outbound staging.',
        ]);

        if ($definition['status'] === OrderStatus::Packed) {
            return;
        }

        $this->activity($order, OrderActivityEvent::OrderShipped, $createdAt->addDays(2), [
            'actor' => $supportAdmin,
            'from_status' => OrderStatus::Packed,
            'to_status' => OrderStatus::Shipped,
            'description' => 'Carrier pickup completed.',
            'metadata' => [
                'carrier' => $definition['carrier'] ?? 'UPS',
                'tracking_number' => $definition['tracking_number'] ?? null,
            ],
        ]);

        if ($definition['status'] === OrderStatus::Shipped) {
            return;
        }

        $this->activity($order, OrderActivityEvent::OrderDelivered, $createdAt->addDays(4), [
            'actor' => null,
            'from_status' => OrderStatus::Shipped,
            'to_status' => OrderStatus::Delivered,
            'description' => 'Carrier marked the package delivered.',
            'metadata' => [
                'carrier' => $definition['carrier'] ?? 'UPS',
                'tracking_number' => $definition['tracking_number'] ?? null,
            ],
        ]);

        if ($definition['status'] === OrderStatus::Delivered) {
            return;
        }

        if ($definition['status'] === OrderStatus::Completed) {
            $this->activity($order, OrderActivityEvent::OrderCompleted, $createdAt->addDays(7), [
                'actor' => null,
                'from_status' => OrderStatus::Delivered,
                'to_status' => OrderStatus::Completed,
                'description' => 'Order auto-completed after the delivery review window.',
            ]);

            return;
        }

        $this->activity($order, OrderActivityEvent::RefundRequested, $createdAt->addDays(4)->addHours(2), [
            'actor' => $customer,
            'from_status' => OrderStatus::Delivered,
            'to_status' => OrderStatus::RefundPending,
            'description' => $definition['refund_reason'],
            'metadata' => ['refund_id' => $refund?->id],
        ]);

        if ($definition['status'] === OrderStatus::RefundPending) {
            return;
        }

        $this->activity($order, OrderActivityEvent::RefundProcessing, $createdAt->addDays(4)->addHours(8), [
            'actor' => $supportAdmin,
            'description' => 'Refund was reviewed and marked for completion.',
            'metadata' => ['refund_id' => $refund?->id],
        ]);

        if (($definition['stock_disposition'] ?? null) === RefundStockDisposition::GoodStock->value) {
            $this->activity($order, OrderActivityEvent::InventoryRestored, $createdAt->addDays(5), [
                'actor' => $admin,
                'description' => 'Returned pair passed inspection and was restored to sellable stock.',
                'metadata' => ['refund_id' => $refund?->id, RefundStockDisposition::MetadataKey => RefundStockDisposition::GoodStock->value],
            ]);
        }

        $this->activity($order, OrderActivityEvent::RefundCompleted, $createdAt->addDays(5)->addMinutes(15), [
            'actor' => $supportAdmin,
            'from_status' => OrderStatus::RefundPending,
            'to_status' => OrderStatus::Refunded,
            'description' => $definition['refund_reason'],
            'metadata' => ['refund_id' => $refund?->id, RefundStockDisposition::MetadataKey => $definition['stock_disposition'] ?? RefundStockDisposition::BadStock->value],
        ]);
    }

    protected function remarkActor(string $actor, User $customer, User $admin, User $supportAdmin): User
    {
        return match ($actor) {
            'customer' => $customer,
            'support' => $supportAdmin,
            default => $admin,
        };
    }

    protected function activity(Order $order, OrderActivityEvent $event, mixed $createdAt, array $data = []): void
    {
        $actor = $data['actor'] ?? null;
        $activity = OrderActivity::create([
            'order_id' => $order->id,
            'actor_id' => $actor instanceof User ? $actor->id : null,
            'actor_role' => $actor instanceof User ? $actor->role : null,
            'event' => $event,
            'title' => $data['title'] ?? str($event->value)->replace('_', ' ')->headline()->toString(),
            'description' => $data['description'] ?? null,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $activity->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();
    }

    protected function addressFor(User $user): UserAddress
    {
        $address = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();

        if ($address) {
            return $address;
        }

        return UserAddress::create([
            'user_id' => $user->id,
            'address_line_1' => '100 Demo Sneaker Lane',
            'address_line_2' => null,
            'city' => 'New York',
            'country' => 'United States',
            'post_code' => '10001',
            'is_default' => true,
        ]);
    }

    protected function isInventoryReserved(OrderStatus $status): bool
    {
        return $status !== OrderStatus::Pending;
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     * @return array<int, string>
     */
    protected function productSkus(array $orders): array
    {
        return collect($orders)
            ->flatMap(fn (array $order): array => collect($order['items'])->pluck('sku')->all())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<string, Product>  $products
     * @param  array<int, array<string, mixed>>  $orders
     * @return array<string, int>
     */
    protected function startingStock(Collection $products, array $orders): array
    {
        $movements = [];

        foreach ($orders as $order) {
            foreach ($this->inventoryQuantityChanges($order) as $sku => $quantityChange) {
                $movements[$sku] = ($movements[$sku] ?? 0) + $quantityChange;
            }
        }

        return $products
            ->mapWithKeys(fn (Product $product, string $sku): array => [
                $sku => $product->stock_quantity - ($movements[$sku] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function inventoryQuantityChanges(array $definition): array
    {
        $changes = [];

        if ($this->isInventoryReserved($definition['status'])) {
            foreach ($definition['items'] as $item) {
                $changes[$item['sku']] = ($changes[$item['sku']] ?? 0) - (int) $item['quantity'];
            }
        }

        if ($definition['status'] === OrderStatus::PartiallyCancelled) {
            foreach ($definition['items'] as $item) {
                $cancelledQuantity = (int) ($item['cancelled_quantity'] ?? 0);
                $changes[$item['sku']] = ($changes[$item['sku']] ?? 0) + $cancelledQuantity;
            }
        }

        if ($definition['status'] === OrderStatus::Cancelled) {
            foreach ($definition['items'] as $item) {
                $changes[$item['sku']] = ($changes[$item['sku']] ?? 0) + (int) $item['quantity'];
            }
        }

        if (($definition['stock_disposition'] ?? null) === RefundStockDisposition::GoodStock->value) {
            foreach ($definition['items'] as $item) {
                $refundedQuantity = (int) ($item['refunded_quantity'] ?? 0);
                $changes[$item['sku']] = ($changes[$item['sku']] ?? 0) + $refundedQuantity;
            }
        }

        return $changes;
    }

    protected function lastActivityAt(array $definition): mixed
    {
        $status = $definition['status'];

        if (! $status instanceof OrderStatus) {
            return $definition['created_at'];
        }

        return match ($status) {
            OrderStatus::Pending => $definition['created_at']->addMinutes(20),
            OrderStatus::Confirmed => $definition['created_at']->addHours(1)->addMinutes(10),
            OrderStatus::Processing => $definition['created_at']->addHours(4),
            OrderStatus::Packed => $definition['created_at']->addDay(),
            OrderStatus::Shipped => $definition['created_at']->addDays(2),
            OrderStatus::Delivered, OrderStatus::RefundPending => $definition['created_at']->addDays(4)->addHours(2),
            OrderStatus::Completed => $definition['created_at']->addDays(7),
            OrderStatus::CancellationRequested => $definition['created_at']->addHours(3),
            OrderStatus::PartiallyCancelled, OrderStatus::Cancelled => $definition['created_at']->addHours(8)->addMinutes(10),
            OrderStatus::Refunded => $definition['created_at']->addDays(5)->addMinutes(15),
        };
    }

    protected function deleteExistingDemoOrders(): void
    {
        $orderIds = Order::withTrashed()
            ->where('order_number', 'like', 'OMS-DEMO-%')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return;
        }

        InventoryLog::query()->whereIn('order_id', $orderIds)->delete();
        OrderActivity::query()->whereIn('order_id', $orderIds)->delete();
        OrderCancellation::query()->whereIn('order_id', $orderIds)->delete();
        OrderRefund::query()->whereIn('order_id', $orderIds)->delete();
        OrderItem::query()->whereIn('order_id', $orderIds)->delete();
        Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();
    }
}
