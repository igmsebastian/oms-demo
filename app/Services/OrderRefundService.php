<?php

namespace App\Services;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundStockDisposition;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRefundService
{
    public function __construct(
        protected OrderActivityService $activities,
        protected OrderStatusTransitionService $transitions,
        protected InventoryService $inventory,
        protected ReportService $reports,
    ) {}

    public function createRefund(Order $order, User $actor, array $data): OrderRefund
    {
        return DB::transaction(function () use ($order, $actor, $data): OrderRefund {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Refunded || $order->refunded_at !== null) {
                throw ValidationException::withMessages([
                    'status' => 'This order is already refunded. No action is needed.',
                ]);
            }

            if (! in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::PartiallyCancelled], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Refunds are only available for delivered, cancelled, or partially cancelled orders.',
                ]);
            }

            $amount = (float) ($data['amount'] ?? 0);

            if ($amount <= 0 || $amount > (float) $order->total_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter a refund amount greater than 0 and not higher than the order total.',
                ]);
            }

            $refund = OrderRefund::create([
                'order_id' => $order->id,
                'requested_by_user_id' => $actor->id,
                'status' => RefundStatus::Pending,
                'amount' => $amount,
                'reason' => $data['reason'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->transitions->transition($order, OrderStatus::RefundPending, $actor, [
                'description' => $data['note'] ?? $data['reason'] ?? null,
                'metadata' => ['refund_id' => $refund->id],
            ]);

            return $refund;
        });
    }

    public function markProcessing(OrderRefund $refund, User $actor): OrderRefund
    {
        return DB::transaction(function () use ($refund, $actor): OrderRefund {
            $refund = OrderRefund::with('order')
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($refund->status !== RefundStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending refunds can be moved to processing.',
                ]);
            }

            $refund->forceFill([
                'status' => RefundStatus::Processing,
                'processed_by_user_id' => $actor->id,
            ])->save();

            $this->activities->record($refund->order, OrderActivityEvent::RefundProcessing, [
                'actor' => $actor,
                'metadata' => ['refund_id' => $refund->id],
            ]);

            return $refund->refresh();
        });
    }

    public function markCompleted(
        OrderRefund $refund,
        User $actor,
        RefundStockDisposition $stockDisposition = RefundStockDisposition::BadStock,
        ?string $note = null,
    ): OrderRefund {
        return DB::transaction(function () use ($refund, $actor, $stockDisposition, $note): OrderRefund {
            $refund = OrderRefund::with('order.items')
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($refund->status === RefundStatus::Completed || $refund->order->status === OrderStatus::Refunded) {
                throw ValidationException::withMessages([
                    'status' => 'This refund is already completed. No action is needed.',
                ]);
            }

            foreach ($refund->order->items as $item) {
                $eligibleQuantity = $item->quantity - $item->cancelled_quantity - $item->refunded_quantity;

                if ($eligibleQuantity < 1) {
                    continue;
                }

                if ($stockDisposition === RefundStockDisposition::GoodStock) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                    $this->inventory->restoreStock($product, $eligibleQuantity, [
                        'order' => $refund->order,
                        'order_item' => $item,
                        'actor' => $actor,
                        'reason' => $note ?? 'Refund returned to good stock',
                        'metadata' => ['refund_id' => $refund->id, RefundStockDisposition::MetadataKey => $stockDisposition->value],
                    ]);
                }

                $item->forceFill([
                    'refunded_quantity' => $item->refunded_quantity + $eligibleQuantity,
                ])->save();
            }

            $refund->forceFill([
                'status' => RefundStatus::Completed,
                'processed_by_user_id' => $actor->id,
                'metadata' => [
                    ...($refund->metadata ?? []),
                    RefundStockDisposition::MetadataKey => $stockDisposition->value,
                ],
                'processed_at' => now(),
            ])->save();

            $this->transitions->transition($refund->order, OrderStatus::Refunded, $actor, [
                'description' => $note,
                'metadata' => ['refund_id' => $refund->id, RefundStockDisposition::MetadataKey => $stockDisposition->value],
            ]);

            $this->reports->invalidate();

            return $refund->refresh();
        });
    }
}
