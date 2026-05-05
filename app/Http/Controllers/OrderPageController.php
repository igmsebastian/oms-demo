<?php

namespace App\Http\Controllers;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Filters\OrderFilter;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CompleteRefundRequest;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\PartialCancelOrderItemRequest;
use App\Http\Requests\StoreOrderRemarkRequest;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Services\OrderActivityService;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderPageController extends Controller
{
    public function index(Request $request, OrderFilter $filter, OrderService $orders): Response
    {
        $paginator = $request->user()->isAdmin()
            ? $orders->getPaginatedOrders($filter)
            : $orders->getPaginatedOrdersForUser($filter, $request->user());

        return Inertia::render('Orders/index', [
            'orders' => OrderResource::collection($paginator),
            'filters' => $request->query('filters', []),
            'sorts' => $request->query('sorts', ['created_at' => 'desc']),
            'status_counts' => $this->statusCounts($request),
            'status_options' => $this->statusOptions(),
            'is_admin' => $request->user()->isAdmin(),
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->load([
            'user',
            'items.product.category',
            'items.product.brand',
            'items.product.unit',
            'items.product.size',
            'items.product.color',
            'items.product.tags',
            'activities.actor',
            'refunds',
        ])->loadCount('activities');

        return Inertia::render('OrderDetails/index', [
            'order' => OrderResource::make($order)->resolve(),
            'status_options' => $this->statusOptions(),
        ]);
    }

    public function remark(StoreOrderRemarkRequest $request, Order $order, OrderActivityService $activities): RedirectResponse
    {
        $activities->record($order, OrderActivityEvent::RemarkAdded, [
            'actor' => $request->user(),
            'title' => 'Remark added',
            'description' => $request->validated('note'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Remark added successfully.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function fulfill(ConfirmOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->fulfillOrder($order, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fulfillment started successfully.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->confirmOrder($order, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order confirmed successfully.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->updateStatus(
            $order,
            OrderStatus::from((int) $request->validated('status')),
            $request->user(),
            $request->validated('note'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order status updated successfully.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function requestCancellation(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        $cancellations->requestCancellation($order, $request->user(), $request->validated('reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cancellation request submitted.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        $cancellations->cancelOrder($order, $request->user(), $request->validated('reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled successfully.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function partialCancel(PartialCancelOrderItemRequest $request, OrderItem $orderItem, OrderCancellationService $cancellations): RedirectResponse
    {
        $cancellations->partiallyCancelItem(
            $orderItem,
            (int) $request->validated('quantity'),
            $request->user(),
            $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order item cancelled successfully.')]);

        return to_route('orders.show', ['order' => $orderItem->order->order_number]);
    }

    public function storeRefund(StoreRefundRequest $request, Order $order, OrderRefundService $refunds): RedirectResponse
    {
        $refunds->createRefund($order, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request submitted.')]);

        return to_route('orders.show', ['order' => $order->order_number]);
    }

    public function markRefundProcessing(Request $request, OrderRefund $refund, OrderRefundService $refunds): RedirectResponse
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markProcessing($refund, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund moved to processing.')]);

        return to_route('orders.show', ['order' => $refund->order->order_number]);
    }

    public function markRefundCompleted(CompleteRefundRequest $request, OrderRefund $refund, OrderRefundService $refunds): RedirectResponse
    {
        $refunds->markCompleted(
            $refund,
            $request->user(),
            RefundStockDisposition::from($request->validated('stock_disposition')),
            $request->validated('note'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund completed successfully.')]);

        return to_route('orders.show', ['order' => $refund->order->order_number]);
    }

    /**
     * @return array<string, int>
     */
    protected function statusCounts(Request $request): array
    {
        $counts = Order::query()
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->whereBelongsTo($request->user()))
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->nameValue() => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function statusOptions(): array
    {
        return collect(OrderStatus::cases())->map(fn (OrderStatus $status): array => [
            'id' => $status->value,
            'name' => $status->nameValue(),
            'label' => $status->label(),
        ])->all();
    }
}
