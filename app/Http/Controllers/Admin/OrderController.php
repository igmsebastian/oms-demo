<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Filters\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CompleteRefundRequest;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\PartialCancelOrderItemRequest;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(OrderFilter $filter, OrderService $orders): Response
    {
        Gate::authorize('viewAny', Order::class);

        return Inertia::render('Orders/index', [
            'orders' => OrderResource::collection($orders->getPaginatedOrders($filter)),
            'filters' => request()->query('filters', []),
            'sorts' => request()->query('sorts', ['created_at' => 'desc']),
            'status_counts' => $orders->statusCounts(),
            'status_options' => collect(OrderStatus::cases())->map(fn (OrderStatus $status): array => [
                'id' => $status->value,
                'name' => $status->nameValue(),
                'label' => $status->label(),
            ])->all(),
            'is_admin' => true,
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
        ]);
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->confirmOrder($order, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order confirmed successfully.')]);

        return to_route('admin.orders.show', $order);
    }

    public function fulfill(ConfirmOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->fulfillOrder($order, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fulfillment started successfully.')]);

        return to_route('admin.orders.show', $order);
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

        return to_route('admin.orders.show', $order);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        $cancellations->cancelOrder($order, $request->user(), $request->validated('reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled successfully.')]);

        return to_route('admin.orders.show', $order);
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

        return to_route('admin.orders.show', $orderItem->order);
    }

    public function storeRefund(StoreRefundRequest $request, Order $order, OrderRefundService $refunds): RedirectResponse
    {
        $refunds->createRefund($order, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund request created successfully.')]);

        return to_route('admin.orders.show', $order);
    }

    public function markRefundProcessing(Request $request, OrderRefund $refund, OrderRefundService $refunds): RedirectResponse
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markProcessing($refund, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund moved to processing.')]);

        return to_route('admin.orders.show', $refund->order);
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

        return to_route('admin.orders.show', $refund->order);
    }
}
