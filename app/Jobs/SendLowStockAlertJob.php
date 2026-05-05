<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\LowStockAlertMail;
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

    public bool $afterCommit = true;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(
        public Product $product,
    ) {}

    public function handle(): void
    {
        User::where('role', UserRole::Admin->value)
            ->cursor()
            ->each(fn (User $admin): mixed => Mail::to($admin)->queue(new LowStockAlertMail($this->product)));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Low stock alert job failed.', [
            'product_id' => $this->product->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
