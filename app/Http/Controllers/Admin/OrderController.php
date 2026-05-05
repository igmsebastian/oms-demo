<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Filters\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
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

        return Inertia::render('admin/orders/index', [
            'orders' => OrderResource::collection($orders->getPaginatedOrders($filter)),
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        return Inertia::render('admin/orders/show', [
            'order' => new OrderResource($order->load(['user', 'items.product', 'activities.actor', 'refunds'])),
        ]);
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->confirmOrder($order, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order confirmed.')]);

        return to_route('admin.orders.show', $order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->updateStatus($order, OrderStatus::from((int) $request->validated('status')), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order status updated.')]);

        return to_route('admin.orders.show', $order);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        $cancellations->cancelOrder($order, $request->user(), $request->validated('reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order item cancelled.')]);

        return to_route('admin.orders.show', $orderItem->order);
    }

    public function storeRefund(StoreRefundRequest $request, Order $order, OrderRefundService $refunds): RedirectResponse
    {
        $refunds->createRefund($order, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund created.')]);

        return to_route('admin.orders.show', $order);
    }

    public function markRefundProcessing(Request $request, OrderRefund $refund, OrderRefundService $refunds): RedirectResponse
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markProcessing($refund, $request->user());

        return to_route('admin.orders.show', $refund->order);
    }

    public function markRefundCompleted(Request $request, OrderRefund $refund, OrderRefundService $refunds): RedirectResponse
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markCompleted($refund, $request->user());

        return to_route('admin.orders.show', $refund->order);
    }
}
