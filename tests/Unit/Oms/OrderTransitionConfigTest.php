<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;

test('configured order transition map allows only expected workflow moves', function () {
    $allowed = config('order_status_transitions.allowed');

    expect($allowed[OrderStatus::Pending->value])->toContain(OrderStatus::Confirmed->value)
        ->and($allowed[OrderStatus::Processing->value])->toContain(OrderStatus::Packed->value, OrderStatus::Shipped->value)
        ->and($allowed[OrderStatus::Packed->value])->toContain(OrderStatus::Shipped->value)
        ->and($allowed[OrderStatus::Shipped->value])->toContain(OrderStatus::Delivered->value)
        ->and($allowed[OrderStatus::Delivered->value])->toContain(OrderStatus::Completed->value, OrderStatus::RefundPending->value)
        ->and($allowed[OrderStatus::Pending->value])->not->toContain(OrderStatus::Shipped->value)
        ->and($allowed[OrderStatus::Completed->value])->toBe([])
        ->and($allowed[OrderStatus::Cancelled->value])->not->toContain(OrderStatus::Processing->value);
});

test('configured transition events map to real activity enum cases', function () {
    $events = config('order_status_transitions.events');

    foreach (OrderStatus::cases() as $status) {
        if ($status === OrderStatus::Pending) {
            continue;
        }

        expect(OrderActivityEvent::tryFrom($events[$status->value] ?? ''))->not->toBeNull();
    }
});
