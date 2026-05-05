<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum OrderStatus: int
{
    case Pending = 1;
    case Confirmed = 2;
    case Processing = 3;
    case Packed = 4;
    case Shipped = 5;
    case Delivered = 6;
    case Completed = 7;
    case CancellationRequested = 8;
    case PartiallyCancelled = 9;
    case Cancelled = 10;
    case RefundPending = 11;
    case Refunded = 12;

    public function nameValue(): string
    {
        return Str::snake($this->name);
    }

    public function label(): string
    {
        return Str::headline($this->nameValue());
    }
}
