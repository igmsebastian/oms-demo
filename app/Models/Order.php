<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'user_address_id',
    'order_number',
    'status',
    'total_amount',
    'shipping_address_line_1',
    'shipping_address_line_2',
    'shipping_city',
    'shipping_country',
    'shipping_post_code',
    'shipping_full_address',
    'cancellation_reason',
    'cancelled_by_user_id',
    'cancelled_by_role',
    'confirmed_at',
    'cancelled_at',
    'refunded_at',
])]
class Order extends Model
{
    use HasUlids, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function statusReference(): BelongsTo
    {
        return $this->belongsTo(OrderStatusReference::class, 'status', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(OrderCancellation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function inventoryLogs(): HasManyThrough
    {
        return $this->hasManyThrough(InventoryLog::class, OrderItem::class);
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
            'cancelled_by_role' => UserRole::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
