<?php

namespace App\Mail;

class OrderDeliveredMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Delivered';
    }

    protected function message(): string
    {
        return 'Your order has been delivered.';
    }
}
