<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;

return [
    'allowed' => [
        OrderStatus::Pending->value => [
            OrderStatus::Confirmed->value,
            OrderStatus::CancellationRequested->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Confirmed->value => [
            OrderStatus::Processing->value,
            OrderStatus::CancellationRequested->value,
            OrderStatus::PartiallyCancelled->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Processing->value => [
            OrderStatus::Packed->value,
            OrderStatus::CancellationRequested->value,
            OrderStatus::PartiallyCancelled->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Packed->value => [
            OrderStatus::Shipped->value,
        ],
        OrderStatus::Shipped->value => [
            OrderStatus::Delivered->value,
        ],
        OrderStatus::Delivered->value => [
            OrderStatus::Completed->value,
        ],
        OrderStatus::Completed->value => [],
        OrderStatus::CancellationRequested->value => [
            OrderStatus::Processing->value,
            OrderStatus::PartiallyCancelled->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::PartiallyCancelled->value => [
            OrderStatus::Processing->value,
            OrderStatus::Cancelled->value,
            OrderStatus::RefundPending->value,
        ],
        OrderStatus::Cancelled->value => [
            OrderStatus::RefundPending->value,
        ],
        OrderStatus::RefundPending->value => [
            OrderStatus::Refunded->value,
        ],
        OrderStatus::Refunded->value => [],
    ],

    'events' => [
        OrderStatus::Confirmed->value => OrderActivityEvent::OrderConfirmed->value,
        OrderStatus::Processing->value => OrderActivityEvent::OrderProcessingStarted->value,
        OrderStatus::Packed->value => OrderActivityEvent::OrderPacked->value,
        OrderStatus::Shipped->value => OrderActivityEvent::OrderShipped->value,
        OrderStatus::Delivered->value => OrderActivityEvent::OrderDelivered->value,
        OrderStatus::Completed->value => OrderActivityEvent::OrderCompleted->value,
        OrderStatus::CancellationRequested->value => OrderActivityEvent::CancellationRequested->value,
        OrderStatus::PartiallyCancelled->value => OrderActivityEvent::OrderPartiallyCancelled->value,
        OrderStatus::Cancelled->value => OrderActivityEvent::OrderCancelled->value,
        OrderStatus::RefundPending->value => OrderActivityEvent::RefundRequested->value,
        OrderStatus::Refunded->value => OrderActivityEvent::RefundCompleted->value,
    ],

    'mail_keys' => [
        OrderStatus::Confirmed->value => 'order_confirmed',
        OrderStatus::Processing->value => 'order_processing',
        OrderStatus::Packed->value => 'order_packed',
        OrderStatus::Shipped->value => 'order_shipped',
        OrderStatus::Delivered->value => 'order_delivered',
        OrderStatus::CancellationRequested->value => 'order_cancellation_requested',
        OrderStatus::PartiallyCancelled->value => 'order_partially_cancelled',
        OrderStatus::Cancelled->value => 'order_cancelled',
        OrderStatus::RefundPending->value => 'order_refund_pending',
        OrderStatus::Refunded->value => 'order_refunded',
    ],
];
