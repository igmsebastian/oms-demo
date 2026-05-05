<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;
use RuntimeException;

class OrderObserver
{
    public function creating(Order $order): void
    {
        if (filled($order->order_number)) {
            return;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $orderNumber = sprintf('ORD-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6)));

            if (! Order::where('order_number', $orderNumber)->exists()) {
                $order->order_number = $orderNumber;

                return;
            }
        }

        throw new RuntimeException('Unable to generate a unique order number.');
    }
}
