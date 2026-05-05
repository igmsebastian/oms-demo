<?php

namespace App\Mail;

class OrderCancelledMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Cancelled';
    }

    protected function message(): string
    {
        return 'Your order has been cancelled.';
    }
}
