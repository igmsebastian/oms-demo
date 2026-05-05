<?php

namespace App\Mail;

class OrderProcessingMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Processing';
    }

    protected function message(): string
    {
        return 'Your order is now being processed.';
    }
}
