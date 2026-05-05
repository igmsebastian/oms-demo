<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConfiguredUserEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public User $user,
        public string $emailKey,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $mailClass = config("order_emails.{$this->emailKey}.class");

        if (! is_string($mailClass) || ! class_exists($mailClass)) {
            $this->logFailure('Configured user email class is missing.', null);

            return;
        }

        Mail::to($this->user)->sendNow(new $mailClass($this->user));
    }

    public function failed(?Throwable $exception): void
    {
        $this->logFailure('Configured user email job failed.', $exception);
    }

    protected function logFailure(string $message, ?Throwable $exception): void
    {
        Log::channel('mailing')->error($message, [
            'email_key' => $this->emailKey,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'error' => $exception?->getMessage(),
        ]);
    }
}
