<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Filters\OrderFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CompleteRefundRequest;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\PartialCancelOrderItemRequest;
use App\Http\Requests\StoreOrderRemarkRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreRefundRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\OpenApi\ApiErrorResponses;
use App\Services\OrderActivityService;
use App\Services\OrderCancellationService;
use App\Services\OrderRefundService;
use App\Services\OrderService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Orders', 'Order workflow endpoints for customers and administrators.')]
class OrderController extends Controller
{
    #[Endpoint(
        operationId: 'orders.index',
        title: 'List orders',
        description: 'Returns paginated orders. Administrators receive all orders; customers receive only their own orders.',
    )]
    #[Response(status: 200, description: 'Paginated order list.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for supplied query parameters.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function index(Request $request, OrderFilter $filter, OrderService $orders): AnonymousResourceCollection
    {
        $paginator = $request->user()->isAdmin()
            ? $orders->getPaginatedOrders($filter)
            : $orders->getPaginatedOrdersForUser($filter, $request->user());

        return OrderResource::collection($paginator);
    }

    #[Endpoint(
        operationId: 'orders.store',
        title: 'Create order',
        description: 'Creates a pending order from active products and stores product and shipping snapshots.',
    )]
    #[Response(status: 200, description: 'Order created.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for order data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function store(StoreOrderRequest $request, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->createOrder($request->user(), $request->validated()));
    }

    #[Endpoint(
        operationId: 'orders.show',
        title: 'Show order',
        description: 'Returns order details including customer, items, activities, refunds, and allowed actions.',
    )]
    #[Response(status: 200, description: 'Order details.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for order lookup data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['user', 'items.product', 'activities.actor', 'refunds']));
    }

    #[Endpoint(
        operationId: 'orders.confirm',
        title: 'Confirm order',
        description: 'Confirms a pending order and deducts inventory for each order item.',
    )]
    #[Response(status: 200, description: 'Order confirmed successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the order cannot be confirmed.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function confirm(ConfirmOrderRequest $request, Order $order, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->confirmOrder($order, $request->user(), $request->validated('note')));
    }

    #[Endpoint(
        operationId: 'orders.fulfill',
        title: 'Start fulfillment',
        description: 'Confirms the order, deducts inventory, and moves the order into processing.',
    )]
    #[Response(status: 200, description: 'Fulfillment started successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or fulfillment cannot be started.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function fulfill(ConfirmOrderRequest $request, Order $order, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->fulfillOrder($order, $request->user(), $request->validated('note')));
    }

    #[Endpoint(
        operationId: 'orders.status.update',
        title: 'Update order status',
        description: 'Moves an order to a configured next status and records the transition activity.',
        method: 'PATCH',
    )]
    #[Response(status: 200, description: 'Order status updated successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the transition is not allowed.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orders): OrderResource
    {
        return new OrderResource($orders->updateStatus(
            $order,
            OrderStatus::from((int) $request->validated('status')),
            $request->user(),
            $request->validated('note'),
        ));
    }

    #[Endpoint(
        operationId: 'orders.remarks.store',
        title: 'Add order remark',
        description: 'Adds a customer or administrator remark to the order activity feed.',
    )]
    #[Response(status: 200, description: 'Remark added and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed for remark data.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function remark(StoreOrderRemarkRequest $request, Order $order, OrderActivityService $activities): OrderResource
    {
        $activities->record($order, OrderActivityEvent::RemarkAdded, [
            'actor' => $request->user(),
            'title' => 'Remark added',
            'description' => $request->validated('note'),
        ]);

        return new OrderResource($order->refresh()->load(['user', 'items.product', 'activities.actor', 'refunds']));
    }

    #[Endpoint(
        operationId: 'orders.cancellationRequests.store',
        title: 'Request cancellation',
        description: 'Creates a cancellation request for an eligible order and records the reason.',
    )]
    #[Response(status: 200, description: 'Cancellation requested and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the order cannot be cancelled.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function requestCancellation(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): OrderResource
    {
        $cancellations->requestCancellation($order, $request->user(), $request->validated('reason'));

        return new OrderResource($order->refresh()->load(['items', 'activities']));
    }

    #[Endpoint(
        operationId: 'orders.cancel',
        title: 'Cancel order',
        description: 'Administratively cancels an eligible order and restores inventory when stock had been deducted.',
    )]
    #[Response(status: 200, description: 'Order cancelled successfully.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the order cannot be cancelled.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function cancel(CancelOrderRequest $request, Order $order, OrderCancellationService $cancellations): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($cancellations->cancelOrder($order, $request->user(), $request->validated('reason')));
    }

    #[Endpoint(
        operationId: 'orderItems.partialCancel',
        title: 'Partially cancel order item',
        description: 'Cancels a quantity from one order item and restores only the cancelled quantity when applicable.',
    )]
    #[Response(status: 200, description: 'Order item partially cancelled and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the item quantity cannot be cancelled.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
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

    #[Endpoint(
        operationId: 'orders.refunds.store',
        title: 'Request refund',
        description: 'Creates a refund request for an eligible order and moves the order to refund pending.',
    )]
    #[Response(status: 200, description: 'Refund requested and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the order cannot be refunded.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function storeRefund(StoreRefundRequest $request, Order $order, OrderRefundService $refunds): OrderResource
    {
        $refunds->createRefund($order, $request->user(), $request->validated());

        return new OrderResource($order->refresh()->load(['refunds', 'activities']));
    }

    #[Endpoint(
        operationId: 'refunds.processing',
        title: 'Mark refund processing',
        description: 'Marks a pending refund as processing.',
        method: 'PATCH',
    )]
    #[Response(status: 200, description: 'Refund marked as processing and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the refund cannot be processed.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function markRefundProcessing(Request $request, OrderRefund $refund, OrderRefundService $refunds): OrderResource
    {
        Gate::authorize('refund', $refund->order);

        $refunds->markProcessing($refund, $request->user());

        return new OrderResource($refund->order->refresh()->load(['refunds', 'activities']));
    }

    #[Endpoint(
        operationId: 'refunds.completed',
        title: 'Complete refund',
        description: 'Completes a refund and optionally restores eligible quantities to good stock.',
        method: 'PATCH',
    )]
    #[Response(status: 200, description: 'Refund completed and order details returned.')]
    #[Response(status: 401, description: 'Unauthenticated.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::Unauthenticated)]
    #[Response(status: 405, description: 'HTTP method is not allowed for this endpoint.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::MethodNotAllowed)]
    #[Response(status: 422, description: 'Validation failed or the refund cannot be completed.', type: ApiErrorResponses::ValidationError, examples: ApiErrorResponses::ValidationFailed)]
    #[Response(status: 500, description: 'Unexpected server error.', type: ApiErrorResponses::Error, examples: ApiErrorResponses::ServerError)]
    public function markRefundCompleted(CompleteRefundRequest $request, OrderRefund $refund, OrderRefundService $refunds): OrderResource
    {
        $refunds->markCompleted(
            $refund,
            $request->user(),
            RefundStockDisposition::from($request->validated('stock_disposition')),
            $request->validated('note'),
        );

        return new OrderResource($refund->order->refresh()->load(['refunds', 'activities']));
    }
}
