<?php

namespace App\Services;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRefundService
{
    public function __construct(
        protected OrderActivityService $activities,
        protected OrderStatusTransitionService $transitions,
        protected ReportService $reports,
    ) {}

    public function createRefund(Order $order, User $actor, array $data): OrderRefund
    {
        return DB::transaction(function () use ($order, $actor, $data): OrderRefund {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Refunded || $order->refunded_at !== null) {
                throw ValidationException::withMessages([
                    'status' => 'This order has already been refunded.',
                ]);
            }

            if (! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::PartiallyCancelled], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only cancelled or partially cancelled orders can be refunded.',
                ]);
            }

            $amount = (float) ($data['amount'] ?? 0);

            if ($amount <= 0 || $amount > (float) $order->total_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount must be greater than zero and cannot exceed the order total.',
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
                'description' => $data['reason'] ?? null,
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
                    'status' => 'Only pending refunds can be marked as processing.',
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

    public function markCompleted(OrderRefund $refund, User $actor): OrderRefund
    {
        return DB::transaction(function () use ($refund, $actor): OrderRefund {
            $refund = OrderRefund::with('order')
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($refund->status === RefundStatus::Completed || $refund->order->status === OrderStatus::Refunded) {
                throw ValidationException::withMessages([
                    'status' => 'This refund is already completed.',
                ]);
            }

            $refund->forceFill([
                'status' => RefundStatus::Completed,
                'processed_by_user_id' => $actor->id,
                'processed_at' => now(),
            ])->save();

            $this->transitions->transition($refund->order, OrderStatus::Refunded, $actor, [
                'metadata' => ['refund_id' => $refund->id],
            ]);

            $this->reports->invalidate();

            return $refund->refresh();
        });
    }
}
