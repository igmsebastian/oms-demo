<?php

namespace App\Mail;

class OrderShippedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Shipped';
    }

    protected function message(): string
    {
        return 'Your order has shipped.';
    }
}
