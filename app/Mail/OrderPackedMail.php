<?php

namespace App\Mail;

class OrderPackedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Packed';
    }

    protected function message(): string
    {
        return 'Your order has been packed.';
    }
}
