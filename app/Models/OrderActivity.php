<?php

namespace App\Models;

use App\Enums\OrderActivityEvent;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'actor_id',
    'actor_role',
    'event',
    'title',
    'description',
    'from_status',
    'to_status',
    'metadata',
])]
class OrderActivity extends Model
{
    use HasUlids;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'actor_role' => UserRole::class,
            'event' => OrderActivityEvent::class,
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'metadata' => 'array',
        ];
    }
}
