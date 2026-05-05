<?php

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Filters\OrderFilter;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orders,
        protected InventoryService $inventory,
        protected OrderActivityService $activities,
        protected OrderNotificationService $notifications,
        protected OrderStatusTransitionService $transitions,
        protected ReportService $reports,
        protected OmsCacheService $cache,
    ) {}

    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            $items = collect($data['items'] ?? []);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Add at least one product to the order.',
                ]);
            }

            $products = Product::active()
                ->whereIn('id', $items->pluck('product_id')->unique()->values())
                ->get()
                ->keyBy('id');

            $addressSnapshot = $this->shippingSnapshot($user, $data);
            $orderItems = [];
            $totalAmount = 0.0;

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id'] ?? null);
                $quantity = (int) ($item['quantity'] ?? 0);

                if (! $product) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'Choose an active product for this item.',
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Enter a quantity of at least 1.',
                    ]);
                }

                $lineTotal = round((float) $product->price * $quantity, 2);
                $totalAmount += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ];
            }

            $order = $this->orders->create([
                'user_id' => $user->id,
                'user_address_id' => $addressSnapshot['user_address_id'],
                'status' => OrderStatus::Pending,
                'total_amount' => round($totalAmount, 2),
                'shipping_address_line_1' => $addressSnapshot['shipping_address_line_1'],
                'shipping_address_line_2' => $addressSnapshot['shipping_address_line_2'],
                'shipping_city' => $addressSnapshot['shipping_city'],
                'shipping_country' => $addressSnapshot['shipping_country'],
                'shipping_post_code' => $addressSnapshot['shipping_post_code'],
                'shipping_full_address' => $addressSnapshot['shipping_full_address'],
            ]);

            $order->items()->createMany($orderItems);
            $order->load(['user', 'items.product']);

            $this->activities->record($order, OrderActivityEvent::OrderCreated, [
                'actor' => $user,
                'to_status' => OrderStatus::Pending,
            ]);

            $this->notifications->queueOrderEmail($order, 'order_created', $user);
            $this->invalidateOrderReads();

            return $order;
        });
    }

    public function confirmOrder(Order $order, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $note): Order {
            $order = Order::with('items')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'This order must be pending before it can be confirmed.',
                ]);
            }

            foreach ($order->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                $this->inventory->deductStock($product, $item->quantity, [
                    'order' => $order,
                    'order_item' => $item,
                    'actor' => $actor,
                    'reason' => 'Order confirmed',
                ]);
            }

            return $this->transitions->transition($order, OrderStatus::Confirmed, $actor, [
                'description' => $note,
            ]);
        })->load(['user', 'items.product', 'activities']);
    }

    public function fulfillOrder(Order $order, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $note): Order {
            $order = $this->confirmOrder($order, $actor, $note);

            return $this->transitions->transition($order, OrderStatus::Processing, $actor, [
                'description' => $note,
            ]);
        })->load(['user', 'items.product', 'activities']);
    }

    public function updateStatus(Order $order, OrderStatus $status, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $status, $actor, $note): Order {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->transitions->transition($order, $status, $actor, [
                'description' => $note,
            ]);
        });
    }

    public function getPaginatedOrders(OrderFilter $filter): LengthAwarePaginator
    {
        $payload = $this->cache->remember(
            OmsCacheService::ORDERS_VERSION_KEY,
            'orders.index.admin',
            $filter->cacheFingerprint(15),
            now()->addMinutes(5),
            fn (): array => $this->cache->paginatorPayload($this->orders->paginate($filter)),
        );

        return $this->cache->restorePaginator(
            $payload,
            $this->orders->findManyForListing($payload['ids']),
        );
    }

    public function getPaginatedOrdersForUser(OrderFilter $filter, User $user): LengthAwarePaginator
    {
        $payload = $this->cache->remember(
            OmsCacheService::ORDERS_VERSION_KEY,
            'orders.index.user',
            [
                'user_id' => $user->id,
                ...$filter->cacheFingerprint(15),
            ],
            now()->addMinutes(5),
            fn (): array => $this->cache->paginatorPayload($this->orders->paginateForUser($filter, $user)),
        );

        return $this->cache->restorePaginator(
            $payload,
            $this->orders->findManyForListing($payload['ids']),
        );
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(?User $user = null): array
    {
        return $this->cache->remember(
            OmsCacheService::ORDERS_VERSION_KEY,
            'orders.status_counts',
            [
                'user_id' => $user?->id,
                'is_admin' => $user?->isAdmin() ?? true,
            ],
            now()->addMinutes(5),
            function () use ($user): array {
                $counts = Order::query()
                    ->when($user && ! $user->isAdmin(), fn ($query) => $query->whereBelongsTo($user))
                    ->selectRaw('status, count(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status');

                return collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [
                    $status->nameValue() => (int) ($counts[$status->value] ?? 0),
                ])->all();
            },
        );
    }

    protected function shippingSnapshot(User $user, array $data): array
    {
        if (filled($data['user_address_id'] ?? null)) {
            $address = UserAddress::whereBelongsTo($user)->findOrFail($data['user_address_id']);

            return [
                'user_address_id' => $address->id,
                'shipping_address_line_1' => $address->address_line_1,
                'shipping_address_line_2' => $address->address_line_2,
                'shipping_city' => $address->city,
                'shipping_country' => $address->country,
                'shipping_post_code' => $address->post_code,
                'shipping_full_address' => $address->full_address,
            ];
        }

        $required = [
            'shipping_address_line_1',
            'shipping_city',
            'shipping_country',
            'shipping_post_code',
        ];

        foreach ($required as $field) {
            if (blank($data[$field] ?? null)) {
                throw ValidationException::withMessages([
                    $field => 'Enter the shipping address or choose a saved address.',
                ]);
            }
        }

        $parts = collect([
            $data['shipping_address_line_1'],
            $data['shipping_address_line_2'] ?? null,
            $data['shipping_city'],
            $data['shipping_country'],
            $data['shipping_post_code'],
        ])->filter();

        return [
            'user_address_id' => null,
            'shipping_address_line_1' => $data['shipping_address_line_1'],
            'shipping_address_line_2' => $data['shipping_address_line_2'] ?? null,
            'shipping_city' => $data['shipping_city'],
            'shipping_country' => $data['shipping_country'],
            'shipping_post_code' => $data['shipping_post_code'],
            'shipping_full_address' => $data['shipping_full_address'] ?? $parts->implode(', '),
        ];
    }

    protected function invalidateOrderReads(): void
    {
        $this->reports->invalidate();
        $this->cache->invalidateOrders();
    }
}
