<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendLowStockAlertJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(
        public Product $product,
        public string $emailKey = 'low_stock_alert',
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $mailClass = config("order_emails.{$this->emailKey}.class");
        $recipients = config("order_emails.{$this->emailKey}.recipients", ['admins']);

        if (! is_string($mailClass) || ! class_exists($mailClass)) {
            Log::channel('mailing')->error('Configured low stock email class is missing.', [
                'email_key' => $this->emailKey,
                'product_id' => $this->product->id,
            ]);

            return;
        }

        if (! is_array($recipients) || ! in_array('admins', $recipients, true)) {
            Log::channel('mailing')->warning('Low stock alert email has no configured admin recipients.', [
                'email_key' => $this->emailKey,
                'product_id' => $this->product->id,
            ]);

            return;
        }

        User::where('role', UserRole::Admin->value)
            ->cursor()
            ->each(function (User $admin) use ($mailClass): void {
                try {
                    Mail::to($admin)->sendNow(new $mailClass($this->product));
                } catch (Throwable $exception) {
                    Log::channel('mailing')->error('Low stock alert email failed.', [
                        'email_key' => $this->emailKey,
                        'product_id' => $this->product->id,
                        'recipient_id' => $admin->id,
                        'recipient_email' => $admin->email,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('mailing')->error('Low stock alert job failed.', [
            'email_key' => $this->emailKey,
            'product_id' => $this->product->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
