<?php

namespace App\Filters;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class OrderFilter extends QueryFilter
{
    public function status(string|int $value): Builder
    {
        $status = $this->resolveStatus($value);

        if (! $status) {
            return $this->builder;
        }

        return $this->builder->where('status', $status->value);
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
