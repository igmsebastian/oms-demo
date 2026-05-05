<?php

namespace App\Services;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    public function __construct(
        protected OrderActivityService $activities,
    ) {}

    public function queueOrderEmail(Order $order, string $key, ?User $actor = null): void
    {
        $mailClass = config("order_emails.{$key}.class");

        if (! is_string($mailClass) || ! class_exists($mailClass)) {
            return;
        }

        $order->loadMissing('user');
        $mailable = new $mailClass($order);

        if (method_exists($mailable, 'afterCommit')) {
            $mailable->afterCommit();
        }

        Mail::to($order->user)->queue($mailable);

        $this->activities->record($order, OrderActivityEvent::EmailQueued, [
            'actor' => $actor,
            'title' => 'Email queued',
            'description' => config("order_emails.{$key}.name", $key),
            'metadata' => ['email_key' => $key],
        ]);
    }

    public function queueForStatus(Order $order, OrderStatus $status, ?User $actor = null): void
    {
        $key = config("order_status_transitions.mail_keys.{$status->value}");

        if (is_string($key)) {
            $this->queueOrderEmail($order, $key, $actor);
        }
    }

    public function queueLowStockAlert(Product $product): void
    {
        $jobClass = 'App\\Jobs\\SendLowStockAlertJob';

        if (class_exists($jobClass)) {
            dispatch(new $jobClass($product))->afterCommit();
        }
    }
}
