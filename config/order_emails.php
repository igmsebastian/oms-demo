<?php

use App\Mail\LowStockAlertMail;
use App\Mail\OrderCancellationRequestedMail;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderCreatedMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderPackedMail;
use App\Mail\OrderPartiallyCancelledMail;
use App\Mail\OrderProcessingMail;
use App\Mail\OrderRefundedMail;
use App\Mail\OrderRefundPendingMail;
use App\Mail\OrderShippedMail;
use App\Mail\WelcomeCustomerMail;

return [
    'welcome_customer' => [
        'name' => 'Welcome Customer',
        'class' => WelcomeCustomerMail::class,
    ],
    'order_created' => [
        'name' => 'Order Created',
        'class' => OrderCreatedMail::class,
    ],
    'order_confirmed' => [
        'name' => 'Order Confirmed',
        'class' => OrderConfirmedMail::class,
    ],
    'order_processing' => [
        'name' => 'Order Processing',
        'class' => OrderProcessingMail::class,
    ],
    'order_packed' => [
        'name' => 'Order Packed',
        'class' => OrderPackedMail::class,
    ],
    'order_shipped' => [
        'name' => 'Order Shipped',
        'class' => OrderShippedMail::class,
    ],
    'order_delivered' => [
        'name' => 'Order Delivered',
        'class' => OrderDeliveredMail::class,
    ],
    'order_cancelled' => [
        'name' => 'Order Cancelled',
        'class' => OrderCancelledMail::class,
    ],
    'order_partially_cancelled' => [
        'name' => 'Order Partially Cancelled',
        'class' => OrderPartiallyCancelledMail::class,
    ],
    'order_cancellation_requested' => [
        'name' => 'Order Cancellation Requested',
        'class' => OrderCancellationRequestedMail::class,
    ],
    'order_refund_pending' => [
        'name' => 'Order Refund Pending',
        'class' => OrderRefundPendingMail::class,
    ],
    'order_refunded' => [
        'name' => 'Order Refunded',
        'class' => OrderRefundedMail::class,
    ],
    'low_stock_alert' => [
        'name' => 'Low Stock Alert',
        'class' => LowStockAlertMail::class,
    ],
];
