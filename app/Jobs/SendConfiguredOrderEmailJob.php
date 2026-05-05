<?php

namespace App\Jobs;

use App\Enums\OrderActivityEvent;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderActivityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConfiguredOrderEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public Order $order,
        public User $recipient,
        public string $emailKey,
        public ?User $actor = null,
    ) {
        $this->afterCommit();
    }

    public function handle(OrderActivityService $activities): void
    {
        $mailClass = config("order_emails.{$this->emailKey}.class");

        if (! is_string($mailClass) || ! class_exists($mailClass)) {
            $this->logFailure('Configured order email class is missing.', null);
            $this->recordFailure($activities, 'Configured order email class is missing.');

            return;
        }

        $order = $this->order->loadMissing('user', 'items');

        Mail::to($this->recipient)->sendNow(new $mailClass($order));

        $activities->record($order, OrderActivityEvent::EmailSent, [
            'actor' => $this->actor,
            'title' => 'Email sent',
            'description' => config("order_emails.{$this->emailKey}.name", $this->emailKey),
            'metadata' => $this->metadata(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->logFailure('Configured order email job failed.', $exception);

        try {
            $this->recordFailure(
                app(OrderActivityService::class),
                $exception?->getMessage() ?? 'Email could not be sent.',
            );
        } catch (Throwable $recordingException) {
            Log::channel('mailing')->error('Unable to record failed order email activity.', [
                ...$this->metadata(),
                'order_id' => $this->order->id,
                'error' => $recordingException->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadata(): array
    {
        return [
            'email_key' => $this->emailKey,
            'recipient_id' => $this->recipient->id,
            'recipient_email' => $this->recipient->email,
        ];
    }

    protected function recordFailure(OrderActivityService $activities, string $description): void
    {
        $activities->record($this->order, OrderActivityEvent::EmailFailed, [
            'actor' => $this->actor,
            'title' => 'Email failed',
            'description' => $description,
            'metadata' => $this->metadata(),
        ]);
    }

    protected function logFailure(string $message, ?Throwable $exception): void
    {
        Log::channel('mailing')->error($message, [
            ...$this->metadata(),
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
