<?php

use App\Enums\OrderStatus;
use App\Enums\RefundStockDisposition;
use App\Enums\UserRole;

test('order statuses expose stable integer values and labels', function () {
    expect(OrderStatus::cases())->toHaveCount(12)
        ->and(OrderStatus::Pending->value)->toBe(1)
        ->and(OrderStatus::Processing->value)->toBe(3)
        ->and(OrderStatus::Packed->value)->toBe(4)
        ->and(OrderStatus::Shipped->value)->toBe(5)
        ->and(OrderStatus::Delivered->value)->toBe(6)
        ->and(OrderStatus::Completed->value)->toBe(7)
        ->and(OrderStatus::CancellationRequested->nameValue())->toBe('cancellation_requested')
        ->and(OrderStatus::CancellationRequested->label())->toBe('Cancellation Requested')
        ->and(collect(OrderStatus::cases())->every(fn (OrderStatus $status): bool => is_int($status->value)))->toBeTrue();
});

test('user role labels stay stable', function () {
    expect(UserRole::User->value)->toBe(1)
        ->and(UserRole::Admin->value)->toBe(2)
        ->and(UserRole::User->label())->toBe('User')
        ->and(UserRole::Admin->label())->toBe('Admin');
});

test('refund stock disposition exposes completion values only', function () {
    expect(RefundStockDisposition::GoodStock->value)->toBe('good_stock')
        ->and(RefundStockDisposition::BadStock->value)->toBe('bad_stock')
        ->and(RefundStockDisposition::MetadataKey)->toBe('stock_disposition')
        ->and(RefundStockDisposition::completionValues())->toBe(['good_stock', 'bad_stock']);
});
