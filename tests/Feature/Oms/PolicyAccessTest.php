<?php

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

test('order policy enforces ownership and admin workflow permissions', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = createOmsOrder($owner, [['product' => createOmsProduct()]]);

    expect(Gate::forUser($admin)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $order))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('updateStatus', $order))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('updateStatus', $order))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('cancel', $order))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('cancel', $order))->toBeFalse();

    $order->forceFill(['status' => OrderStatus::Delivered])->save();

    expect(Gate::forUser($owner)->allows('refund', $order))->toBeTrue()
        ->and(Gate::forUser($other)->allows('refund', $order))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('refund', $order))->toBeTrue();
});

test('product and report policies restrict management to admins', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $active = createOmsProduct(['is_active' => true]);
    $inactive = createOmsProduct(['is_active' => false]);

    expect(Gate::forUser($admin)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Product::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $active))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $active))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $active))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $inactive))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewReports'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewReports'))->toBeFalse();
});
