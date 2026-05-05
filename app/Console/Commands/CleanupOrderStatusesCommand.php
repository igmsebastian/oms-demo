<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderStatusTransitionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:cleanup-statuses')]
#[Description('Clean up stale OMS order statuses.')]
class CleanupOrderStatusesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OrderStatusTransitionService $transitions): int
    {
        $completed = 0;
        $cancelled = 0;
        $autoCompleteBefore = now()->subDays((int) config('orders.cleanup.delivered_auto_complete_days', 7));

        Order::query()
            ->where('status', OrderStatus::Delivered->value)
            ->where('updated_at', '<=', $autoCompleteBefore)
            ->chunkById(100, function ($orders) use ($transitions, &$completed): void {
                foreach ($orders as $order) {
                    $transitions->transition($order, OrderStatus::Completed, null, [
                        'description' => 'System auto-completed delivered order after configured waiting period.',
                        'metadata' => ['system' => true],
                    ]);
                    $completed++;
                }
            });

        Order::query()
            ->with('items')
            ->where('status', OrderStatus::PartiallyCancelled->value)
            ->chunkById(100, function ($orders) use ($transitions, &$cancelled): void {
                foreach ($orders as $order) {
                    $allItemsCancelled = $order->items->every(
                        fn (OrderItem $item): bool => $item->cancelled_quantity >= $item->quantity,
                    );

                    if (! $allItemsCancelled) {
                        continue;
                    }

                    $transitions->transition($order, OrderStatus::Cancelled, null, [
                        'description' => 'System moved fully cancelled partial order to cancelled.',
                        'metadata' => ['system' => true],
                    ]);
                    $cancelled++;
                }
            });

        $this->info("Completed {$completed} delivered orders and cancelled {$cancelled} fully cancelled partial orders.");

        return self::SUCCESS;
    }
}
