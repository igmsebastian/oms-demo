<?php

namespace App\Enums;

enum OrderActivityEvent: string
{
    case OrderCreated = 'order_created';
    case OrderConfirmed = 'order_confirmed';
    case InventoryDeducted = 'inventory_deducted';
    case OrderProcessingStarted = 'order_processing_started';
    case OrderPacked = 'order_packed';
    case OrderShipped = 'order_shipped';
    case OrderDelivered = 'order_delivered';
    case OrderCompleted = 'order_completed';
    case CancellationRequested = 'cancellation_requested';
    case OrderPartiallyCancelled = 'order_partially_cancelled';
    case OrderCancelled = 'order_cancelled';
    case InventoryRestored = 'inventory_restored';
    case RefundRequested = 'refund_requested';
    case RefundProcessing = 'refund_processing';
    case RefundCompleted = 'refund_completed';
    case EmailQueued = 'email_queued';
    case EmailSent = 'email_sent';
    case EmailFailed = 'email_failed';
    case RemarkAdded = 'remark_added';
}
