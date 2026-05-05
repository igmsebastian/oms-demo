<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Filters\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\PartialCancelOrderItemRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request, OrderFilter $filter, OrderService $orders): AnonymousResourceCollection
    {
        $paginator = $request->user()->isAdmin()
            ? $orders->getPaginatedOrders($filter)
            : $orders->getPaginatedOrdersForUser($filter, $request->user());

        return OrderResource::collection($paginator);
    }

    public function store(StoreOrderRequest $request, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->createOrder($request->user(), $request->validated()));
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['user', 'items.product', 'activities.actor', 'refunds']));
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->confirmOrder($order, $request->user()));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->updateStatus(
            $order,
            OrderStatus::from((int) $request->validated('status')),
            $request->user(),
        ));
    }

    public function requestCancellation(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): OrderResource
    {
        $cancellations->requestCancellation($order, $request->user(), $request->validated('reason'));

        return new OrderResource($order->refresh()->load(['items', 'activities']));
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($cancellations->cancelOrder($order, $request->user(), $request->validated('reason')));
    }

    public function partialCancel(PartialCancelOrderItemRequest $request, OrderItem $orderItem, OrderCancellationService $cancellations): OrderResource
    {
        $item = $cancellations->partiallyCancelItem(
            $orderItem,
            (int) $request->validated('quantity'),
            $request->user(),
            $request->validated('reason'),
        );

        return new OrderResource($item->order->refresh()->load(['items', 'activities']));
    }

    public function storeRefund(StoreRefundRequest $request, Order $order, OrderRefundService $refunds): OrderResource
    {
        $refunds->createRefund($order, $request->user(), $request->validated());

        return new OrderResource($order->refresh()->load(['refunds', 'activities']));
    }

    public function markRefundProcessing(Request $request, OrderRefund $refund, OrderRefundService $refunds): OrderResource
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markProcessing($refund, $request->user());

        return new OrderResource($refund->order->refresh()->load(['refunds', 'activities']));
    }

    public function markRefundCompleted(Request $request, OrderRefund $refund, OrderRefundService $refunds): OrderResource
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markCompleted($refund, $request->user());

        return new OrderResource($refund->order->refresh()->load(['refunds', 'activities']));
    }
}
