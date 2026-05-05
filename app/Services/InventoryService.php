<?php

namespace App\Services;

use App\Contracts\Repositories\InventoryLogRepositoryInterface;
use App\Enums\InventoryChangeType;
use App\Enums\OrderActivityEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected InventoryLogRepositoryInterface $inventoryLogs,
        protected OrderActivityService $activities,
        protected OrderNotificationService $notifications,
        protected ReportService $reports,
    ) {}

    public function deductStock(Product $product, int $quantity, array $context): Product
    {
        return DB::transaction(function () use ($product, $quantity, $context): Product {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->ensurePositiveQuantity($quantity);

            if ($product->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Insufficient stock for {$product->name}.",
                ]);
            }

            $stockBefore = $product->stock_quantity;
            $product->stock_quantity -= $quantity;
            $product->save();

            $this->recordInventoryChange($product, InventoryChangeType::Deduction, -$quantity, $stockBefore, $context);
            $this->reports->invalidate();

            if ($product->isLowStock()) {
                $this->notifications->queueLowStockAlert($product);
            }

            return $product;
        });
    }

    public function restoreStock(Product $product, int $quantity, array $context): Product
    {
        return DB::transaction(function () use ($product, $quantity, $context): Product {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->ensurePositiveQuantity($quantity);

            $stockBefore = $product->stock_quantity;
            $product->stock_quantity += $quantity;
            $product->save();

            $this->recordInventoryChange($product, InventoryChangeType::Restore, $quantity, $stockBefore, $context);
            $this->reports->invalidate();

            return $product;
        });
    }

    public function adjustStock(Product $product, int $quantity, string $reason, User $actor): Product
    {
        return DB::transaction(function () use ($product, $quantity, $reason, $actor): Product {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $stockBefore = $product->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'stock_quantity' => 'Inventory adjustments cannot make stock negative.',
                ]);
            }

            $product->stock_quantity = $stockAfter;
            $product->save();

            $this->recordInventoryChange($product, InventoryChangeType::Adjustment, $quantity, $stockBefore, [
                'actor' => $actor,
                'reason' => $reason,
            ]);

            $this->reports->invalidate();

            if ($product->isLowStock()) {
                $this->notifications->queueLowStockAlert($product);
            }

            return $product;
        });
    }

    protected function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }
    }

    protected function recordInventoryChange(
        Product $product,
        InventoryChangeType $type,
        int $quantityChange,
        int $stockBefore,
        array $context,
    ): void {
        $order = $context['order'] ?? null;
        $orderItem = $context['order_item'] ?? null;
        $actor = $context['actor'] ?? null;
        $stockAfter = $product->stock_quantity;

        $this->inventoryLogs->create([
            'product_id' => $product->id,
            'order_id' => $order instanceof Order ? $order->id : ($context['order_id'] ?? null),
            'order_item_id' => $orderItem instanceof OrderItem ? $orderItem->id : ($context['order_item_id'] ?? null),
            'changed_by_user_id' => $actor instanceof User ? $actor->id : ($context['changed_by_user_id'] ?? null),
            'change_type' => $type,
            'quantity_change' => $quantityChange,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $context['reason'] ?? $type->value,
            'metadata' => $context['metadata'] ?? null,
        ]);

        if ($order instanceof Order) {
            $this->activities->record($order, $type === InventoryChangeType::Restore
                ? OrderActivityEvent::InventoryRestored
                : OrderActivityEvent::InventoryDeducted, [
                    'actor' => $actor instanceof User ? $actor : null,
                    'metadata' => [
                        'product_id' => $product->id,
                        'quantity_change' => $quantityChange,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ],
                ]);
        }
    }
}
