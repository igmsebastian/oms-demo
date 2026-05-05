<?php

namespace App\Mail;

class OrderCancellationRequestedMail extends OrderMail
{
    protected function title(): string
    {
        return 'Order Cancellation Requested';
    }

    protected function message(): string
    {
        return 'A cancellation request has been recorded for this order.';
    }
}
