<?php

namespace App\Models;

use App\Enums\CancellationStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'requested_by_user_id',
    'requested_by_role',
    'reason',
    'status',
    'admin_note',
    'approved_by_user_id',
    'approved_at',
    'completed_at',
])]
class OrderCancellation extends Model
{
    use HasUlids;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'requested_by_role' => UserRole::class,
            'status' => CancellationStatus::class,
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
