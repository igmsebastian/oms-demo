<?php

namespace App\Services;

use App\Enums\CancellationStatus;
use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function __construct(
        protected InventoryService $inventory,
        protected OrderActivityService $activities,
        protected OrderStatusTransitionService $transitions,
        protected ReportService $reports,
    ) {}

    public function requestCancellation(Order $order, User $actor, string $reason): OrderCancellation
    {
        return DB::transaction(function () use ($order, $actor, $reason): OrderCancellation {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'This order is already cancelled. No action is needed.',
                ]);
            }

            if ($order->status === OrderStatus::CancellationRequested) {
                throw ValidationException::withMessages([
                    'status' => 'A cancellation request already exists for this order.',
                ]);
            }

            $cancellation = OrderCancellation::create([
                'order_id' => $order->id,
                'requested_by_user_id' => $actor->id,
                'requested_by_role' => $actor->role,
                'reason' => $reason,
                'status' => CancellationStatus::Requested,
            ]);

            $this->transitions->transition($order, OrderStatus::CancellationRequested, $actor, [
                'description' => $reason,
            ]);

            return $cancellation;
        });
    }

    public function cancelOrder(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason): Order {
            $order = Order::with('items')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'This order is already cancelled. No action is needed.',
                ]);
            }

            foreach ($order->items as $item) {
                $remainingQuantity = $item->quantity - $item->cancelled_quantity;

                if ($remainingQuantity < 1) {
                    continue;
                }

                if ($order->confirmed_at !== null) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                    $this->inventory->restoreStock($product, $remainingQuantity, [
                        'order' => $order,
                        'order_item' => $item,
                        'actor' => $actor,
                        'reason' => $reason,
                    ]);
                }

                $item->forceFill(['cancelled_quantity' => $item->quantity])->save();
            }

            OrderCancellation::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'requested_by_user_id' => $actor->id,
                    'status' => CancellationStatus::Requested,
                ],
                [
                    'requested_by_role' => $actor->role,
                    'reason' => $reason,
                    'status' => CancellationStatus::Completed,
                    'approved_by_user_id' => $actor->id,
                    'approved_at' => now(),
                    'completed_at' => now(),
                ],
            );

            $order->forceFill([
                'cancellation_reason' => $reason,
                'cancelled_by_user_id' => $actor->id,
                'cancelled_by_role' => $actor->role,
            ])->save();

            return $this->transitions->transition($order, OrderStatus::Cancelled, $actor, [
                'cancellation_reason' => $reason,
                'description' => $reason,
            ]);
        });
    }

    public function partiallyCancelItem(OrderItem $item, int $quantity, User $actor, string $reason): OrderItem
    {
        return DB::transaction(function () use ($item, $quantity, $actor, $reason): OrderItem {
            $item = OrderItem::with('order')
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::whereKey($item->order_id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::PartiallyCancelled], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This item can only be partially cancelled while the order is confirmed or processing.',
                ]);
            }

            $availableQuantity = $item->quantity - $item->cancelled_quantity;

            if ($quantity < 1 || $quantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Enter a cancellation quantity that is available for this item.',
                ]);
            }

            $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
            $this->inventory->restoreStock($product, $quantity, [
                'order' => $order,
                'order_item' => $item,
                'actor' => $actor,
                'reason' => $reason,
            ]);

            $item->cancelled_quantity += $quantity;
            $item->save();

            $order->load('items');
            $allItemsCancelled = $order->items->every(
                fn (OrderItem $orderItem): bool => $orderItem->cancelled_quantity >= $orderItem->quantity,
            );

            $targetStatus = $allItemsCancelled ? OrderStatus::Cancelled : OrderStatus::PartiallyCancelled;

            if ($order->status !== $targetStatus) {
                $this->transitions->transition($order, $targetStatus, $actor, [
                    'description' => $reason,
                ]);
            } else {
                $this->activities->record($order, OrderActivityEvent::OrderPartiallyCancelled, [
                    'actor' => $actor,
                    'description' => $reason,
                    'metadata' => [
                        'order_item_id' => $item->id,
                        'quantity' => $quantity,
                    ],
                ]);
            }

            $this->reports->invalidate();

            return $item->refresh();
        });
    }
}
