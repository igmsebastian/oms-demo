<?php

namespace App\Mail;

class OrderConfirmedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Confirmed';
    }

    protected function message(): string
    {
        return 'Your order has been confirmed and inventory has been allocated.';
    }
}
