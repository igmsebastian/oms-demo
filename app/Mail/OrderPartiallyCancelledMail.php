<?php

namespace App\Mail;

class OrderPartiallyCancelledMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Partially Cancelled';
    }

    protected function message(): string
    {
        return 'Part of your order has been cancelled.';
    }
}
