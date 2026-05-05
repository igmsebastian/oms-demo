<?php

namespace App\Services;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OrderStatusTransitionService
{
    public function __construct(
        protected OrderActivityService $activities,
        protected OrderNotificationService $notifications,
        protected ReportService $reports,
        protected OmsCacheService $cache,
    ) {}

    public function transition(Order $order, OrderStatus $status, ?User $actor, array $data = []): Order
    {
        $fromStatus = $order->status;

        if ($fromStatus === $status) {
            return $order;
        }

        $this->ensureTransitionIsAllowed($fromStatus, $status);

        $order->forceFill([
            'status' => $status,
            ...$this->timestampUpdates($status),
        ]);

        if (array_key_exists('cancellation_reason', $data)) {
            $order->cancellation_reason = $data['cancellation_reason'];
        }

        $order->save();
        $order->refresh();

        $this->activities->record($order, $this->eventFor($status), [
            'actor' => $actor,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->notifications->queueForStatus($order, $status, $actor);
        $this->reports->invalidate();
        $this->cache->invalidateOrders();

        return $order;
    }

    protected function ensureTransitionIsAllowed(OrderStatus $fromStatus, OrderStatus $toStatus): void
    {
        $allowed = config("order_status_transitions.allowed.{$fromStatus->value}", []);

        if (! in_array($toStatus->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "This order cannot move from {$fromStatus->label()} to {$toStatus->label()}.",
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function timestampUpdates(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::Confirmed => ['confirmed_at' => now()],
            OrderStatus::Cancelled => ['cancelled_at' => now()],
            OrderStatus::Refunded => ['refunded_at' => now()],
            default => [],
        };
    }

    protected function eventFor(OrderStatus $status): OrderActivityEvent
    {
        $event = config("order_status_transitions.events.{$status->value}");

        return OrderActivityEvent::from($event);
    }
}
