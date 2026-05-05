<?php

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Jobs\SendConfiguredOrderEmailJob;
use App\Jobs\SendConfiguredUserEmailJob;
use App\Jobs\SendLowStockAlertJob;
use App\Mail\LowStockAlertMail;
use App\Mail\OrderCreatedMail;
use App\Mail\WelcomeCustomerMail;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderActivityService;
use App\Services\OrderNotificationService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    Bus::fake();
    Mail::fake();
});

test('notification service resolves configured order mail jobs and records email queued activity', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    app(OrderNotificationService::class)->queueOrderEmail($order, 'order_created', $admin);
    app(OrderNotificationService::class)->queueForStatus($order, OrderStatus::Shipped, $admin);
    app(OrderNotificationService::class)->queueOrderEmail($order, 'unknown_key', $admin);

    Bus::assertDispatched(SendConfiguredOrderEmailJob::class, fn (SendConfiguredOrderEmailJob $job): bool => $job->emailKey === 'order_created'
        && $job->recipient->is($user));
    Bus::assertDispatched(SendConfiguredOrderEmailJob::class, fn (SendConfiguredOrderEmailJob $job): bool => $job->emailKey === 'order_shipped'
        && $job->recipient->is($user));

    expect(OrderActivity::where('event', OrderActivityEvent::EmailQueued->value)->count())->toBe(3)
        ->and(OrderActivity::where('event', OrderActivityEvent::EmailFailed->value)->where('description', 'Email configuration is missing for [unknown_key].')->exists())->toBeTrue()
        ->and(OrderActivity::where('description', 'Order Created')->exists())->toBeTrue();
});

test('configured order email job sends mail and records sent activity', function () {
    $user = User::factory()->create();
    $order = createOmsOrder($user, [['product' => createOmsProduct()]]);

    (new SendConfiguredOrderEmailJob($order, $user, 'order_created'))
        ->handle(app(OrderActivityService::class));

    Mail::assertSent(OrderCreatedMail::class, fn (OrderCreatedMail $mail): bool => $mail->hasTo($user->email));

    expect(OrderActivity::where('event', OrderActivityEvent::EmailSent->value)
        ->where('order_id', $order->id)
        ->whereJsonContains('metadata->email_key', 'order_created')
        ->exists())->toBeTrue();
});

test('configured user email job sends welcome email', function () {
    $user = User::factory()->create();

    (new SendConfiguredUserEmailJob($user, 'welcome_customer'))->handle();

    Mail::assertSent(WelcomeCustomerMail::class, fn (WelcomeCustomerMail $mail): bool => $mail->hasTo($user->email));
});

test('low stock alert job is queued after commit capable', function () {
    $product = createOmsProduct(['stock_quantity' => 1, 'low_stock_threshold' => 3]);
    $job = new SendLowStockAlertJob($product);

    expect(class_implements(SendLowStockAlertJob::class))->toContain(ShouldQueue::class)
        ->and($job->afterCommit)->toBeTrue()
        ->and($job->tries)->toBe(3)
        ->and($job->product->is($product))->toBeTrue();
});

test('low stock alert job sends configured alert to admins only', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();
    $product = createOmsProduct(['stock_quantity' => 1, 'low_stock_threshold' => 3]);

    (new SendLowStockAlertJob($product))->handle();

    Mail::assertSent(LowStockAlertMail::class, fn (LowStockAlertMail $mail): bool => $mail->hasTo($admin->email));
    Mail::assertSent(LowStockAlertMail::class, 1);
});

test('order email config documents expected recipients', function () {
    expect(config('order_emails.order_created.recipients'))->toBe(['customer'])
        ->and(config('order_emails.order_cancellation_requested.recipients'))->toBe(['customer', 'admins'])
        ->and(config('order_emails.order_refund_pending.recipients'))->toBe(['customer', 'admins'])
        ->and(config('order_emails.low_stock_alert.recipients'))->toBe(['admins']);
});
