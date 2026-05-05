<?php

namespace App\Mail;

class OrderCreatedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Created';
    }

    protected function message(): string
    {
        return 'Your order has been created and is waiting for confirmation.';
    }
}
