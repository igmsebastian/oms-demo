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
    ) {}

    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            $items = collect($data['items'] ?? []);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'An order must contain at least one item.',
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
                        "items.{$index}.product_id" => 'The selected product is invalid or inactive.',
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Quantity must be at least 1.',
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

            return $order;
        });
    }

    public function confirmOrder(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            $order = Order::with('items')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending orders can be confirmed.',
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

            return $this->transitions->transition($order, OrderStatus::Confirmed, $actor);
        })->load(['user', 'items.product', 'activities']);
    }

    public function updateStatus(Order $order, OrderStatus $status, User $actor): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->transitions->transition($order, $status, $actor);
        });
    }

    public function getPaginatedOrders(OrderFilter $filter): LengthAwarePaginator
    {
        return $this->orders->paginate($filter);
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
                    $field => 'Shipping address details are required.',
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
}
