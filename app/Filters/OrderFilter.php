<?php

namespace App\Filters;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderFilter extends QueryFilter
{
    public function keyword(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value): void {
            $query->where('order_number', 'like', "%{$value}%")
                ->orWhereHas('user', function (Builder $query) use ($value): void {
                    $query->where('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                });
        });
    }

    public function status(string|int $value): Builder
    {
        return $this->statuses($value);
    }

    /**
     * @param  array<int, string|int>|string|int  $value
     */
    public function statuses(array|string|int $value): Builder
    {
        $statuses = collect(Arr::wrap($value))
            ->map(function (mixed $item): ?OrderStatus {
                if (! is_string($item) && ! is_int($item)) {
                    return null;
                }

                return $this->resolveStatus($item);
            })
            ->filter()
            ->map(fn (OrderStatus $status): int => $status->value)
            ->unique()
            ->values()
            ->all();

        if ($statuses === []) {
            return $this->builder;
        }

        return $this->builder->whereIn('status', $statuses, 'and', false);
    }

    public function user_id(string $value): Builder
    {
        return $this->builder->where('user_id', $value);
    }

    public function order_number(string $value): Builder
    {
        return $this->builder->where('order_number', 'like', "%{$value}%");
    }

    public function date_from(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '>=', $value);
    }

    public function date_to(string $value): Builder
    {
        return $this->builder->whereDate('created_at', '<=', $value);
    }

    protected function resolveStatus(string|int $value): ?OrderStatus
    {
        if (is_numeric($value)) {
            return OrderStatus::tryFrom((int) $value);
        }

        $normalized = Str::of($value)->snake()->toString();

        foreach (OrderStatus::cases() as $status) {
            if ($status->nameValue() === $normalized) {
                return $status;
            }
        }

        return null;
    }
}
