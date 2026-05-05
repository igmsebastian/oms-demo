<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundStockDisposition;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_address_id' => $this->user_address_id,
            'order_number' => $this->order_number,
            'status' => [
                'id' => $this->status->value,
                'name' => $this->status->nameValue(),
                'label' => $this->status->label(),
            ],
            'total_amount' => $this->total_amount,
            'shipping_address_line_1' => $this->shipping_address_line_1,
            'shipping_address_line_2' => $this->shipping_address_line_2,
            'shipping_city' => $this->shipping_city,
            'shipping_country' => $this->shipping_country,
            'shipping_post_code' => $this->shipping_post_code,
            'shipping_full_address' => $this->shipping_full_address,
            'cancellation_reason' => $this->cancellation_reason,
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'refunded_at' => $this->refunded_at,
            'customer' => $this->customerPayload(),
            'user' => $this->whenLoaded('user'),
            'items' => $this->whenLoaded(
                'items',
                fn (): array => OrderItemResource::collection($this->items)->resolve($request),
                [],
            ),
            'activities' => $this->whenLoaded(
                'activities',
                fn (): array => OrderActivityResource::collection($this->activities)->resolve($request),
                [],
            ),
            'refunds' => $this->whenLoaded('refunds', fn (): array => $this->refunds->map(fn ($refund): array => [
                'id' => $refund->id,
                'status' => [
                    'name' => $refund->status->value,
                    'label' => str($refund->status->value)->replace('_', ' ')->headline()->toString(),
                ],
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'metadata' => $refund->metadata,
                'stock_disposition' => $refund->metadata[RefundStockDisposition::MetadataKey] ?? null,
                'processed_at' => $refund->processed_at,
                'created_at' => $refund->created_at,
            ])->values()->all(), []),
            'allowed_actions' => $this->allowedActions($request),
            'available_statuses' => $this->availableStatuses($request),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function customerPayload(): ?array
    {
        if (! $this->relationLoaded('user') || ! $this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedActions(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        $actions = [];
        $status = $this->status;
        $latestRefund = $this->relationLoaded('refunds') ? $this->refunds->sortByDesc('created_at')->first() : null;

        if ($user->can('confirm', $this->resource) && $status === OrderStatus::Pending) {
            $actions[] = 'fulfill';
            $actions[] = 'confirm';
        }

        if ($user->can('updateStatus', $this->resource)) {
            $actions = [
                ...$actions,
                ...collect($this->availableStatuses($request))->map(fn (array $status): string => match ($status['name']) {
                    'processing' => 'start_processing',
                    'packed' => 'mark_packed',
                    'shipped' => 'mark_shipped',
                    'delivered' => 'mark_delivered',
                    'completed' => 'mark_completed',
                    default => 'update_status',
                })->unique()->values()->all(),
            ];
        }

        if ($user->can('cancel', $this->resource) && in_array($status, [
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Processing,
            OrderStatus::CancellationRequested,
            OrderStatus::PartiallyCancelled,
        ], true)) {
            $actions[] = 'cancel';
        }

        if (! $user->isAdmin() && $user->can('requestCancellation', $this->resource) && in_array($status, [
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Processing,
        ], true)) {
            $actions[] = 'request_cancellation';
        }

        if ($user->can('refund', $this->resource) && in_array($status, [
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
            OrderStatus::PartiallyCancelled,
        ], true)) {
            $actions[] = 'request_refund';
        }

        if ($user->isAdmin() && $latestRefund && $latestRefund->status === RefundStatus::Pending) {
            $actions[] = 'mark_refund_processing';
        }

        if ($user->isAdmin() && $latestRefund && in_array($latestRefund->status, [RefundStatus::Pending, RefundStatus::Processing], true)) {
            $actions[] = 'mark_refunded';
        }

        return array_values(array_unique($actions));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function availableStatuses(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->can('updateStatus', $this->resource)) {
            return [];
        }

        $allowed = config("order_status_transitions.allowed.{$this->status->value}", []);

        return collect($allowed)
            ->map(fn (int $status): OrderStatus => OrderStatus::from($status))
            ->map(fn (OrderStatus $status): array => [
                'id' => $status->value,
                'name' => $status->nameValue(),
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }
}
