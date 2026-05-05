<?php

namespace App\Mail;

class OrderRefundPendingMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Refund Pending';
    }

    protected function message(): string
    {
        return 'A refund is pending for this order.';
    }
}
