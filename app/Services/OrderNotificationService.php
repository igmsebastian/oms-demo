<?php

namespace App\Services;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Jobs\SendConfiguredOrderEmailJob;
use App\Jobs\SendConfiguredUserEmailJob;
use App\Jobs\SendLowStockAlertJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    public function __construct(
        protected OrderActivityService $activities,
    ) {}

    public function queueOrderEmail(Order $order, string $key, ?User $actor = null): void
    {
        $config = config("order_emails.{$key}");

        if (! $this->isConfigured($config)) {
            $this->logConfigurationIssue('Order email was not queued because it is not configured.', $key, [
                'order_id' => $order->id,
            ]);
            $this->activities->record($order, OrderActivityEvent::EmailFailed, [
                'actor' => $actor,
                'title' => 'Email not queued',
                'description' => "Email configuration is missing for [{$key}].",
                'metadata' => ['email_key' => $key],
            ]);

            return;
        }

        $order->loadMissing('user');
        $recipients = $this->orderRecipients($order, $config, $actor);

        if ($recipients->isEmpty()) {
            $this->logConfigurationIssue('Order email was not queued because no recipient was resolved.', $key, [
                'order_id' => $order->id,
            ]);

            return;
        }

        $recipients->each(function (User $recipient) use ($order, $key, $actor): void {
            try {
                SendConfiguredOrderEmailJob::dispatch($order, $recipient, $key, $actor)->afterCommit();
            } catch (\Throwable $exception) {
                $this->logConfigurationIssue('Order email job could not be queued.', $key, [
                    'order_id' => $order->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);
                $this->activities->record($order, OrderActivityEvent::EmailFailed, [
                    'actor' => $actor,
                    'title' => 'Email not queued',
                    'description' => 'Email job could not be queued.',
                    'metadata' => [
                        'email_key' => $key,
                        'recipient_id' => $recipient->id,
                        'recipient_email' => $recipient->email,
                    ],
                ]);

                return;
            }

            $this->activities->record($order, OrderActivityEvent::EmailQueued, [
                'actor' => $actor,
                'title' => 'Email queued',
                'description' => config("order_emails.{$key}.name", $key),
                'metadata' => [
                    'email_key' => $key,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                ],
            ]);
        });
    }

    public function queueUserEmail(User $user, string $key): void
    {
        $config = config("order_emails.{$key}");

        if (! $this->isConfigured($config)) {
            $this->logConfigurationIssue('User email was not queued because it is not configured.', $key, [
                'recipient_id' => $user->id,
                'recipient_email' => $user->email,
            ]);

            return;
        }

        try {
            SendConfiguredUserEmailJob::dispatch($user, $key)->afterCommit();
        } catch (\Throwable $exception) {
            $this->logConfigurationIssue('User email job could not be queued.', $key, [
                'recipient_id' => $user->id,
                'recipient_email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
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
        try {
            SendLowStockAlertJob::dispatch($product)->afterCommit();
        } catch (\Throwable $exception) {
            $this->logConfigurationIssue('Low stock email job could not be queued.', 'low_stock_alert', [
                'product_id' => $product->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function isConfigured(mixed $config): bool
    {
        return is_array($config)
            && isset($config['class'])
            && is_string($config['class'])
            && class_exists($config['class']);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return Collection<int, User>
     */
    protected function orderRecipients(Order $order, array $config, ?User $actor): Collection
    {
        return collect($config['recipients'] ?? ['customer'])
            ->flatMap(fn (mixed $recipient): array|Collection => match ($recipient) {
                'customer' => $order->user ? [$order->user] : [],
                'actor' => $actor ? [$actor] : [],
                'admins' => User::query()
                    ->where('role', UserRole::Admin->value)
                    ->get(),
                default => [],
            })
            ->filter(fn (mixed $recipient): bool => $recipient instanceof User && filled($recipient->email))
            ->unique('id')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logConfigurationIssue(string $message, string $key, array $context = []): void
    {
        Log::channel('mailing')->warning($message, [
            'email_key' => $key,
            ...$context,
        ]);
    }
}
