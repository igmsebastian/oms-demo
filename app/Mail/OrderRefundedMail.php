<?php

namespace App\Mail;

class OrderRefundedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Refunded';
    }

    protected function message(): string
    {
        return 'Your order refund has been completed.';
    }
}
